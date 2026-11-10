<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\PaymentMethodEnum;
use App\Models\Address;
use App\Models\City;
use App\Models\Setting;
use App\Services\Cart\CartService;
use App\Services\Cdek\Exceptions\CdekApiException;
use App\Services\Cities\CurrentCityService;
use App\Services\Orders\CheckoutService;
use App\Services\Orders\OrderNotificationService;
use App\Services\Orders\OrderPaymentService;
use App\Services\Tbank\Exceptions\TbankApiException;
use App\Support\Values\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class Checkout extends Component
{
    private const string CDEK_QUOTE_ERROR = 'Не удалось рассчитать СДЭК. Повторите попытку или выберите другой способ получения.';

    public string $first_name = '';

    public string $last_name = '';

    public string $phone = '';

    public string $email = '';

    public ?int $city_id = null;

    public string $city_search = '';

    public string $delivery_method = '';

    public string $payment_method = PaymentMethodEnum::ONLINE->value;

    public ?int $address_id = null;

    public string $street = '';

    public string $house = '';

    public ?string $building = null;

    public ?string $apartment = null;

    public ?string $entrance = null;

    public ?string $floor = null;

    public ?string $intercom = null;

    public bool $delivery_to_door = false;

    public string $comment = '';

    public bool $agreement = false;

    public bool $checkout_attempted = false;

    public bool $other_recipient = false;

    public array $other_recipient_contacts = [];

    public string $idempotency_key = '';

    public ?int $quoted_total_minor = null;

    public string $delivery_point_code = '';

    public string $delivery_point_search = '';

    public ?int $saved_delivery_point_id = null;

    protected $listeners = ['city-selected' => 'selectCity', 'city-detected' => 'useDetectedCity'];

    public function mount(CurrentCityService $cities): void
    {
        $this->idempotency_key = (string) Str::uuid();
        $user = auth()->user();
        $this->first_name = $user?->first_name ?? '';
        $this->last_name = $user?->last_name ?? '';
        $this->phone = $user?->phone ?? '';
        $this->email = $user?->email ?? '';
        $point = $user?->savedDeliveryPoints()->where('is_default', true)
            ->whereHas('city', static fn ($query) => $query->where('is_active', true)->where('is_cdek_excluded', false)->where('cdek_code', '>', 0))
            ->first();

        if ($point !== null) {
            $this->selectSavedDeliveryPoint($point->id);

            return;
        }

        $this->city_id = $cities->current()?->id;
        $city = $this->selectedCity();
        $this->city_search = $city?->name ?? '';
        $this->delivery_method = (string) array_key_first(app(CheckoutService::class)->deliveryMethods($city));
        $this->updated('delivery_method');
    }

    public function updated(string $property): void
    {
        if ($property === 'other_recipient') {
            $this->switchRecipient();
        }

        if ($property === 'city_search' && $this->city_id !== null && $this->city_search !== $this->selectedCity()?->name) {
            $this->city_id = null;
            $this->updated('city_id');
        }

        if ($property === 'city_id') {
            $this->handleCityChange();
        }

        if ($property === 'delivery_method') {
            $this->handleDeliveryChange();
        }

        if (in_array($property, ['city_id', 'delivery_method', 'payment_method', 'street', 'house', 'building', 'apartment', 'entrance', 'floor', 'intercom', 'delivery_to_door'], true)) {
            $this->recalculate();
        }
    }

    public function selectCity(int $id, ?string $name = null): void
    {
        $this->city_id = City::query()
            ->where('is_active', true)
            ->findOrFail($id)->id;
        $this->updated('city_id');
    }

    public function useDetectedCity(int $id, ?string $name = null): void
    {
        if ($this->city_id === null) {
            $this->selectCity($id);
        }
    }

    public function selectAddress(int $addressId): void
    {
        abort_unless(auth()->check(), 403);
        $address = auth()->user()->addresses()->with('city')->findOrFail($addressId);
        $this->fillAddress($address);
        $this->city_id = $address->city_id;
        $this->city_search = $address->city?->name ?? '';
        $this->delivery_method = DeliveryMethodEnum::COURIER->value;
        $this->payment_method = PaymentMethodEnum::ONLINE->value;
        $this->recalculate();
    }

    public function clearAddress(): void
    {
        $this->resetAddress();
        $this->recalculate();
    }

    public function selectDeliveryPoint(string $code): void
    {
        $this->delivery_point_code = $code;
        $this->saved_delivery_point_id = null;
        $this->recalculate();
    }

    public function clearDeliveryPoint(): void
    {
        $this->resetDeliveryPoint();
        $this->recalculate();
    }

    public function chooseAnotherDeliveryPoint(CurrentCityService $cities): void
    {
        $city = $cities->current() ?? $cities->detect();
        $this->city_id = $city?->id;
        $this->city_search = $city?->name ?? '';
        $this->updated('city_id');
    }

    public function selectSavedDeliveryPoint(int $pointId): void
    {
        abort_unless(auth()->check(), 403);
        $point = auth()->user()->savedDeliveryPoints()
            ->findOrFail($pointId);
        $this->city_id = $point->city_id;
        $this->city_search = $point->city?->name ?? '';
        $this->delivery_method = DeliveryMethodEnum::CDEK->value;
        $this->payment_method = PaymentMethodEnum::ONLINE->value;
        $this->delivery_point_code = $point->code;
        $this->saved_delivery_point_id = $point->id;
        $this->delivery_point_search = '';
        $this->recalculate();
    }

    public function recalculate(): void
    {
        if (! Str::isUuid($this->idempotency_key)) {
            $this->addError('idempotency_key', __('Обновите страницу оформления.'));

            return;
        }

        $this->resetValidation(['cart', 'quote', 'quoted_total_minor', 'delivery_point_code']);

        try {
            $calculation = app(CheckoutService::class)->calculate($this->formData());
            $this->quoted_total_minor = $calculation['total']->minorAmount();
            session()->put('checkout.quotes.'.$this->idempotency_key, [
                'fingerprint' => $calculation['fingerprint'],
                'total_minor' => $this->quoted_total_minor,
            ]);
            session()->put('checkout.presentation.'.$this->idempotency_key, $this->calculationData($calculation));
        } catch (ValidationException $exception) {
            $this->invalidateQuote();

            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        } catch (CdekApiException $exception) {
            $this->invalidateQuote();
            $this->addError('quote', __(self::CDEK_QUOTE_ERROR));
            app(OrderNotificationService::class)->notifyQuoteError($this->city_id ?? 0);
            report($exception);
        }
    }

    public function placeOrder(): void
    {
        $this->checkout_attempted = true;
        $service = app(CheckoutService::class);
        $existing = $service->findOrder($this->idempotency_key);

        if ($existing === null) {
            $this->validate();

            try {
                $existing = $service->placeOrder($this->formData(), $this->idempotency_key, $this->quoted_total_minor ?? -1);
            } catch (CdekApiException $exception) {
                report($exception);

                throw ValidationException::withMessages(['quote' => __(self::CDEK_QUOTE_ERROR)]);
            }

            $this->dispatch('cart-updated', count: app(CartService::class)->count());
        }

        if ($existing->wasRecentlyCreated && $existing->payment_method->isOnline()) {
            try {
                $payment = app(OrderPaymentService::class)->start($existing);

                if ($payment->payment_url !== null) {
                    $this->redirect($payment->payment_url);

                    return;
                }
            } catch (TbankApiException|ValidationException $exception) {
                report($exception);
                session()->flash('order_message', __('Заказ сохранён, но открыть оплату не удалось. Свяжитесь с нами для уточнения оплаты.'));
            }
        }

        if ($existing->user_id !== null) {
            $this->redirect(route('orders.show', $existing));

            return;
        }

        if (! session()->has('order_message')) {
            session()->flash('order_message', __('Ваш заказ принят. Подтверждение отправлено на указанный email.'));
        }

        $this->redirect(route('home'));
    }

    public function render(): View
    {
        $checkoutData = session('checkout.presentation.'.$this->idempotency_key, []);
        $items = $checkoutData['items'] ?? [];
        $subtotal = Money::fromMinor($checkoutData['subtotal_minor'] ?? 0);
        $deliveryPrice = Money::fromMinor($checkoutData['delivery_minor'] ?? 0);
        $total = Money::fromMinor($checkoutData['total_minor'] ?? 0);
        $isPreorder = $checkoutData['is_preorder'] ?? false;
        $deliveryReady = ($checkoutData['delivery_ready'] ?? false) && ! $this->getErrorBag()->has('quote') && ! $this->getErrorBag()->has('delivery_point_code');
        $points = collect($checkoutData['points'] ?? []);
        $selectedDeliveryPoint = $points->firstWhere('code', $this->delivery_point_code);
        $deliveryPoints = $this->pointSuggestions($points);
        $city = $this->selectedCity();
        $availableDeliveryMethods = app(CheckoutService::class)->deliveryMethods($city);
        $cities = $this->citySuggestions();
        $addresses = auth()->user()?->addresses()->with('city')->orderByDesc('is_default')->get() ?? collect();
        $contactFields = [
            ['name' => 'first_name', 'label' => __('Имя'), 'type' => 'text', 'autocomplete' => 'given-name', 'placeholder' => __('Введите имя')],
            ['name' => 'last_name', 'label' => __('Фамилия'), 'type' => 'text', 'autocomplete' => 'family-name', 'placeholder' => __('Введите фамилию')],
            ['name' => 'phone', 'label' => __('Телефон'), 'type' => 'tel', 'autocomplete' => 'tel', 'placeholder' => __('+7 (___) ___-__-__')],
            ['name' => 'email', 'label' => __('Email'), 'type' => 'email', 'autocomplete' => 'email', 'placeholder' => __('mail@example.ru')],
        ];
        $addressFields = [
            ['name' => 'street', 'label' => __('Улица'), 'required' => true],
            ['name' => 'house', 'label' => __('Дом'), 'required' => true],
            ['name' => 'building', 'label' => __('Корпус / строение'), 'required' => false],
            ['name' => 'entrance', 'label' => __('Подъезд'), 'required' => false],
        ];
        $doorFields = [
            ['name' => 'floor', 'label' => __('Этаж'), 'required' => true],
            ['name' => 'apartment', 'label' => __('Квартира / офис'), 'required' => true],
            ['name' => 'intercom', 'label' => __('Домофон'), 'required' => false],
        ];
        $savedPoints = auth()->user()?->savedDeliveryPoints()->with('city')->orderByDesc('is_default')->get() ?? collect();

        $doorDeliveryPrice = ($city?->door_delivery_price ?? Money::fromMinor(0))->format();
        $floorLiftingPrice = ($city?->floor_lifting_price ?? Money::fromMinor(0))->format();
        $privacyPolicyUrl = Setting::cached()?->privacyPolicyUrl() ?? '';

        return view('livewire.checkout', compact('doorDeliveryPrice', 'floorLiftingPrice', 'privacyPolicyUrl', 'items', 'subtotal', 'deliveryPrice', 'total', 'isPreorder', 'deliveryReady', 'deliveryPoints', 'selectedDeliveryPoint', 'availableDeliveryMethods', 'cities', 'city', 'addresses', 'savedPoints', 'contactFields', 'addressFields', 'doorFields'));
    }

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! preg_match('/^\+7\d{10}$/', app(CheckoutService::class)->normalizePhone((string) $value))) {
                    $fail(__('Укажите корректный телефон в формате +7XXXXXXXXXX.'));
                }
            }],
            'email' => ['required', 'email', 'max:255'],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where('is_active', true)],
            'delivery_method' => ['required', Rule::in(array_keys(app(CheckoutService::class)->deliveryMethods($this->selectedCity())))],
            'payment_method' => ['required', Rule::enum(PaymentMethodEnum::class)->only($this->delivery_method === DeliveryMethodEnum::PICKUP->value ? PaymentMethodEnum::cases() : [PaymentMethodEnum::ONLINE])],
            'agreement' => Setting::cached()?->privacy_policy ? ['accepted'] : ['boolean'],
            'delivery_point_code' => [Rule::requiredIf($this->delivery_method === DeliveryMethodEnum::CDEK->value), 'string', 'max:255'],
            'idempotency_key' => ['required', 'uuid'],
            'address_id' => ['nullable', Rule::exists('addresses', 'id')->where('user_id', auth()->id() ?? 0)],
            'street' => [Rule::requiredIf($this->delivery_method === DeliveryMethodEnum::COURIER->value), 'nullable', 'string', 'max:255'],
            'house' => [Rule::requiredIf($this->delivery_method === DeliveryMethodEnum::COURIER->value), 'nullable', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:100'],
            'entrance' => ['nullable', 'string', 'max:100'],
            'floor' => $this->delivery_method === DeliveryMethodEnum::COURIER->value && $this->delivery_to_door ? ['required', 'integer', 'min:1', 'max:200'] : ['nullable'],
            'apartment' => [Rule::requiredIf($this->delivery_method === DeliveryMethodEnum::COURIER->value && $this->delivery_to_door), 'nullable', 'string', 'max:100'],
            'intercom' => ['nullable', 'string', 'max:100'],
            'delivery_to_door' => ['boolean'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function messages(): array
    {
        $city = $this->selectedCity();
        $noDeliveryMethods = $city !== null && app(CheckoutService::class)->deliveryMethods($city) === [];

        return [
            'city_id.required' => __('Выберите город для доставки.'),
            'delivery_method.required' => $noDeliveryMethods ? __('В этом городе нет доступных способов доставки.') : __('Выберите способ доставки.'),
            'delivery_method.in' => $noDeliveryMethods ? __('В этом городе нет доступных способов доставки.') : __('Выбранный способ доставки недоступен.'),
            'delivery_point_code.required' => __('Выберите пункт выдачи.'),
            'agreement.accepted' => __('Необходимо согласиться с политикой обработки персональных данных.'),
        ];
    }

    private function switchRecipient(): void
    {
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        foreach (['first_name', 'last_name', 'phone', 'email'] as $field) {
            if ($this->other_recipient) {
                $this->{$field} = $this->other_recipient_contacts[$field] ?? '';
            } else {
                $this->other_recipient_contacts[$field] = $this->{$field};
                $this->{$field} = $user->{$field} ?? '';
            }
        }

        $this->resetValidation(['first_name', 'last_name', 'phone', 'email']);
    }

    private function calculationData(array $calculation): array
    {
        $points = [];

        if ($this->delivery_method === DeliveryMethodEnum::CDEK->value && array_key_exists(DeliveryMethodEnum::CDEK->value, app(CheckoutService::class)->deliveryMethods($calculation['city']))) {
            foreach (app(CheckoutService::class)->deliveryPoints($calculation['city'], $calculation['packages']) as $point) {
                $points[] = ['code' => $point->code, 'address' => $point->address, 'work_time' => $point->workTime, 'type' => $point->type];
            }
        }

        return [
            'items' => $calculation['items'],
            'subtotal_minor' => $calculation['subtotal']->minorAmount(),
            'delivery_minor' => $calculation['delivery']->minorAmount(),
            'total_minor' => $calculation['total']->minorAmount(),
            'details' => $calculation['details'],
            'is_preorder' => $calculation['is_preorder'],
            'points' => $points,
            'delivery_ready' => $calculation['city'] !== null
                && array_key_exists($this->delivery_method, app(CheckoutService::class)->deliveryMethods($calculation['city']))
                && ($this->delivery_method !== DeliveryMethodEnum::CDEK->value || $calculation['point'] !== null),
        ];
    }

    private function formData(): array
    {
        return array_intersect_key(get_object_vars($this), array_flip([
            'first_name', 'last_name', 'phone', 'email', 'city_id', 'delivery_method', 'payment_method', 'address_id',
            'street', 'house', 'building', 'apartment', 'entrance', 'floor', 'intercom', 'delivery_to_door', 'comment', 'delivery_point_code',
        ]));
    }

    private function selectedCity(): ?City
    {
        return $this->city_id === null ? null : City::query()->find($this->city_id);
    }

    private function fillAddress(Address $address): void
    {
        $this->address_id = $address->id;

        foreach (['street', 'house', 'building', 'apartment', 'entrance', 'floor', 'intercom'] as $field) {
            $this->{$field} = $address->{$field} ?? (in_array($field, ['street', 'house'], true) ? '' : null);
        }
    }

    private function resetAddress(): void
    {
        $this->reset('address_id', 'street', 'house', 'building', 'apartment', 'entrance', 'floor', 'intercom', 'delivery_to_door');
    }

    private function resetDeliveryPoint(): void
    {
        $this->reset('delivery_point_code', 'saved_delivery_point_id', 'delivery_point_search');
    }

    private function invalidateQuote(): void
    {
        session()->forget('checkout.quotes.'.$this->idempotency_key);
        session()->put('checkout.presentation.'.$this->idempotency_key.'.delivery_ready', false);
    }

    private function handleCityChange(): void
    {
        $city = $this->selectedCity();

        if ($city !== null) {
            $this->city_search = $city->name;
        }

        $this->resetDeliveryPoint();
        $this->resetAddress();
        $methods = app(CheckoutService::class)->deliveryMethods($this->selectedCity());

        if (! array_key_exists($this->delivery_method, $methods)) {
            $this->delivery_method = (string) array_key_first($methods);
        }
    }

    private function handleDeliveryChange(): void
    {
        if ($this->delivery_method !== DeliveryMethodEnum::PICKUP->value) {
            $this->payment_method = PaymentMethodEnum::ONLINE->value;
        }

        if ($this->delivery_method === DeliveryMethodEnum::COURIER->value && auth()->check()) {
            $address = auth()->user()->addresses()->where('is_default', true)->where('city_id', $this->city_id)->first();

            if ($address !== null) {
                $this->fillAddress($address);
            }
        }

        if ($this->delivery_method === DeliveryMethodEnum::CDEK->value && auth()->check()) {
            $point = auth()->user()->savedDeliveryPoints()
                ->where('is_default', true)
                ->where('city_id', $this->city_id)
                ->first();

            if ($point !== null) {
                $this->delivery_point_code = $point->code;
                $this->saved_delivery_point_id = $point->id;
            }
        }
    }

    private function citySuggestions(): Collection
    {
        $citySearch = trim($this->city_search);

        return mb_strlen($citySearch) < 3 ? collect() : City::query()->where('is_active', true)
            ->whereLike('name', '%'.addcslashes($citySearch, '%_\\').'%')
            ->orderByDesc('is_suggested')->orderBy('name')->get();
    }

    private function pointSuggestions(Collection $points): Collection
    {
        $pointSearch = trim($this->delivery_point_search);

        return $pointSearch === '' ? collect() : $points->filter(
            static fn (array $point): bool => mb_stripos($point['code'].' '.$point['address'], $pointSearch) !== false,
        );
    }
}
