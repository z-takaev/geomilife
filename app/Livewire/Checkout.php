<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Cities\GetCitySuggestionsAction;
use App\Actions\Orders\CalculateCheckoutQuoteAction;
use App\DTO\Orders\CheckoutPresentationDto;
use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\PaymentMethodEnum;
use App\Livewire\Forms\CheckoutForm;
use App\Models\Address;
use App\Models\City;
use App\Models\SavedDeliveryPoint;
use App\Models\Setting;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Cdek\Exceptions\CdekApiException;
use App\Services\Cities\CurrentCityService;
use App\Services\Orders\CheckoutService;
use App\Services\Orders\CheckoutStateService;
use App\Services\Orders\OrderNotificationService;
use App\Services\Orders\OrderPaymentService;
use App\Services\Tbank\Exceptions\TbankApiException;
use App\Support\Email\EmailNormalizer;
use App\Support\Phone\PhoneFormat;
use App\Support\Values\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class Checkout extends Component
{
    private const string CDEK_CALCULATION_ERROR_MESSAGE = 'Не удалось рассчитать СДЭК. Повторите попытку или выберите другой способ получения.';

    private const array QUOTE_DEPENDENT_FIELDS = [
        'city_id',
        'delivery_method',
        'payment_method',
        'street',
        'house',
        'building',
        'apartment',
        'entrance',
        'floor',
        'intercom',
        'delivery_to_door',
    ];

    public CheckoutForm $form;

    public string $city_search = '';

    public bool $checkout_attempted = false;

    public bool $other_recipient = false;

    /** @var array{first_name?: string, last_name?: string, phone?: string, email?: string} */
    public array $other_recipient_contacts = [];

    public string $idempotency_key = '';

    /** Подтверждённая пользователем сумма последнего серверного расчёта. */
    public ?int $quoted_total_minor = null;

    public string $delivery_point_search = '';

    public ?int $saved_delivery_point_id = null;

    private ?EloquentCollection $cachedAddresses = null;

    private ?EloquentCollection $cachedSavedDeliveryPoints = null;

    private CalculateCheckoutQuoteAction $calculateQuote;

    private CheckoutService $checkout;

    private CheckoutStateService $checkoutState;

    private OrderNotificationService $orderNotifications;

    protected $listeners = ['city-selected' => 'selectCity', 'city-detected' => 'useDetectedCity'];

    public function boot(
        CalculateCheckoutQuoteAction $calculateQuote,
        CheckoutService $checkout,
        CheckoutStateService $checkoutState,
        OrderNotificationService $orderNotifications,
    ): void {
        $this->calculateQuote = $calculateQuote;
        $this->checkout = $checkout;
        $this->checkoutState = $checkoutState;
        $this->orderNotifications = $orderNotifications;
    }

    public function mount(CurrentCityService $currentCityService): void
    {
        session()->forget('checkout');

        $this->idempotency_key = (string) Str::uuid();

        $user = Auth::user();

        $this->form->first_name = $user?->first_name ?? '';
        $this->form->last_name = $user?->last_name ?? '';
        $this->form->phone = PhoneFormat::format((string) $user?->phone);
        $this->form->email = $user?->email ?? '';

        $point = $this->savedDeliveryPoints()->first(
            fn (SavedDeliveryPoint $point): bool => $point->is_default
                && array_key_exists(
                    DeliveryMethodEnum::CDEK->value,
                    $this->checkout->availableDeliveryMethods($point->city),
                ),
        );

        if ($point !== null) {
            $this->selectSavedDeliveryPoint($point->id);

            return;
        }

        $this->form->city_id = $currentCityService->current()?->id;
        $city = $this->form->selectedCity();
        $this->city_search = $city?->optionLabel() ?? '';
        $this->form->delivery_method = (string) array_key_first($this->checkout->availableDeliveryMethods($city));

        $this->handleFormChange('delivery_method');
    }

    public function updatedOtherRecipient(): void
    {
        $this->switchRecipient();
    }

    public function updatedCitySearch(): void
    {
        if ($this->form->city_id !== null && $this->city_search !== $this->form->selectedCity()?->optionLabel()) {
            $this->form->city_id = null;
            $this->handleFormChange('city_id');
        }
    }

    public function updatedForm(mixed $value, ?string $field): void
    {
        if ($field !== null) {
            $this->handleFormChange($field);
        }
    }

    public function selectCity(int $id, ?string $name = null): void
    {
        $city = City::cachedById($id);

        abort_if($city === null, 404);

        $this->form->city_id = $city->id;
        $this->city_search = $city->optionLabel();

        $this->handleFormChange('city_id');
    }

    public function useDetectedCity(int $id, ?string $name = null): void
    {
        if ($this->form->city_id === null) {
            $this->selectCity($id);
        }
    }

    public function selectAddress(int $addressId): void
    {
        abort_unless(Auth::check(), 403);

        $address = $this->addresses()->firstWhere('id', $addressId);

        abort_if($address === null, 404);

        $this->form->fillAddress($address);
        $this->form->city_id = $address->city_id;
        $this->city_search = $address->city?->optionLabel() ?? '';
        $this->form->delivery_method = DeliveryMethodEnum::COURIER->value;
        $this->form->payment_method = PaymentMethodEnum::ONLINE->value;

        $this->recalculate();
    }

    public function clearAddress(): void
    {
        $this->form->resetAddress();
        $this->recalculate();
    }

    public function selectDeliveryPoint(string $code): void
    {
        $this->form->delivery_point_code = $code;
        $this->saved_delivery_point_id = null;

        $this->recalculate();
    }

    public function clearDeliveryPoint(): void
    {
        $this->resetDeliveryPoint();
        $this->recalculate();
    }

    public function chooseAnotherDeliveryPoint(CurrentCityService $currentCityService): void
    {
        $city = $currentCityService->current() ?? $currentCityService->getOrDetect();

        $this->form->city_id = $city?->id;
        $this->city_search = $city?->optionLabel() ?? '';

        $this->handleFormChange('city_id');
    }

    public function selectSavedDeliveryPoint(int $pointId): void
    {
        abort_unless(Auth::check(), 403);

        $point = $this->savedDeliveryPoints()->firstWhere('id', $pointId);

        abort_if($point === null, 404);

        $this->form->city_id = $point->city_id;
        $this->city_search = $point->city?->optionLabel() ?? '';
        $this->form->delivery_method = DeliveryMethodEnum::CDEK->value;
        $this->form->payment_method = PaymentMethodEnum::ONLINE->value;
        $this->form->delivery_point_code = $point->code;
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

        $this->resetValidation(['cart', 'quote', 'quoted_total_minor', 'form.delivery_point_code']);

        try {
            $presentation = $this->calculateQuote->execute($this->form->orderData(), $this->idempotency_key);

            $this->quoted_total_minor = $presentation->total->minorAmount();
        } catch (ValidationException $exception) {
            $this->invalidateQuote();

            foreach ($this->orderErrors($exception) as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        } catch (CdekApiException $exception) {
            $this->invalidateQuote();
            $this->addError('quote', __(self::CDEK_CALCULATION_ERROR_MESSAGE));

            $this->orderNotifications->notifyQuoteError($this->form->city_id ?? 0);

            report($exception);
        }
    }

    public function placeOrder(CartService $cart, OrderPaymentService $orderPayment): void
    {
        $this->checkout_attempted = true;
        $this->validateOnly('idempotency_key');

        $order = $this->checkout->findPlacedOrder($this->idempotency_key);

        if ($order === null) {
            $this->form->email = EmailNormalizer::normalize($this->form->email) ?? '';
            $this->validate();

            try {
                $order = $this->checkout->placeOrder($this->form->orderData(), $this->idempotency_key, $this->quoted_total_minor ?? -1);
            } catch (ValidationException $exception) {
                throw ValidationException::withMessages($this->orderErrors($exception));
            } catch (CdekApiException $exception) {
                report($exception);

                throw ValidationException::withMessages(['quote' => __(self::CDEK_CALCULATION_ERROR_MESSAGE)]);
            }

            $this->dispatch('cart-updated', hasItems: $cart->hasItems());
        }

        $this->checkoutState->forget($this->idempotency_key);

        if ($order->wasRecentlyCreated && $order->payment_method->isOnline()) {
            try {
                $payment = $orderPayment->startOnlinePayment($order);

                if ($payment->payment_url !== null) {
                    $this->redirect($payment->payment_url);

                    return;
                }
            } catch (TbankApiException|ValidationException $exception) {
                report($exception);

                session()->flash('order_message', __('Заказ сохранён, но открыть оплату не удалось. Свяжитесь с нами для уточнения оплаты.'));

                $this->redirect(route('payment.fail'));

                return;
            }
        }

        if ($order->user_id !== null) {
            $this->redirect(route('orders.show', $order));

            return;
        }

        $this->redirect(route('checkout.success'));
    }

    public function render(GetCitySuggestionsAction $getCitySuggestions): View
    {
        $presentation = $this->checkoutState->getPresentation($this->idempotency_key)
            ?? CheckoutPresentationDto::empty();

        $items = $presentation->items;
        $subtotal = $presentation->subtotal;
        $deliveryPrice = $presentation->delivery;
        $total = $presentation->total;

        $isPreorder = $presentation->isPreorder;
        $deliveryReady = $this->isDeliveryReady($presentation);

        $city = $this->form->selectedCity();
        $points = $this->deliveryPoints($city, $presentation->packages);
        $selectedDeliveryPoint = $points->firstWhere('code', $this->form->delivery_point_code);
        $deliveryPoints = $this->filterDeliveryPoints($points);
        $availableDeliveryMethods = $this->checkout->availableDeliveryMethods($city);

        $cities = $this->form->city_id !== null && $this->city_search === $city?->optionLabel()
            ? collect()
            : $getCitySuggestions->run($this->city_search);
        $addresses = $this->addresses();

        $savedPoints = $this->savedDeliveryPoints();

        $doorDeliveryPrice = ($city?->door_delivery_price ?? Money::fromMinor(0))->format();
        $floorLiftingPrice = ($city?->floor_lifting_price ?? Money::fromMinor(0))->format();
        $privacyPolicyUrl = Setting::cached()?->privacyPolicyUrl() ?? '';

        return view('livewire.checkout', compact('doorDeliveryPrice', 'floorLiftingPrice', 'privacyPolicyUrl', 'items', 'subtotal', 'deliveryPrice', 'total', 'isPreorder', 'deliveryReady', 'deliveryPoints', 'selectedDeliveryPoint', 'availableDeliveryMethods', 'cities', 'city', 'addresses', 'savedPoints'));
    }

    protected function rules(): array
    {
        return ['idempotency_key' => ['required', 'uuid']];
    }

    private function handleFormChange(string $field): void
    {
        if ($field === 'city_id') {
            $this->handleCityChange();
        }

        if ($field === 'delivery_method') {
            $this->handleDeliveryChange();
        }

        if (in_array($field, self::QUOTE_DEPENDENT_FIELDS, true)) {
            $this->recalculate();
        }
    }

    private function orderErrors(ValidationException $exception): array
    {
        $formFields = $this->form->all();
        $errors = [];

        foreach ($exception->errors() as $field => $messages) {
            $errors[array_key_exists($field, $formFields) ? 'form.'.$field : $field] = $messages;
        }

        return $errors;
    }

    private function switchRecipient(): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        foreach (['first_name', 'last_name', 'phone', 'email'] as $field) {
            if ($this->other_recipient) {
                $this->form->{$field} = $this->other_recipient_contacts[$field] ?? '';
            } else {
                $this->other_recipient_contacts[$field] = $this->form->{$field};

                $this->form->{$field} = $field === 'phone'
                    ? PhoneFormat::format((string) $user->{$field})
                    : ($user->{$field} ?? '');
            }
        }

        $this->form->resetValidation(['first_name', 'last_name', 'phone', 'email']);
    }

    private function resetDeliveryPoint(): void
    {
        $this->form->resetDeliveryPoint();
        $this->reset('saved_delivery_point_id', 'delivery_point_search');
    }

    private function invalidateQuote(): void
    {
        $this->checkoutState->invalidateQuote($this->idempotency_key);
    }

    private function handleCityChange(): void
    {
        $city = $this->form->selectedCity();

        if ($city !== null) {
            $this->city_search = $city->optionLabel();
        }

        $this->resetDeliveryPoint();
        $this->form->resetAddress();

        $methods = $this->checkout->availableDeliveryMethods($city);

        if (! array_key_exists($this->form->delivery_method, $methods)) {
            $this->form->delivery_method = (string) array_key_first($methods);
        }
    }

    private function handleDeliveryChange(): void
    {
        if ($this->form->delivery_method !== DeliveryMethodEnum::PICKUP->value) {
            $this->form->payment_method = PaymentMethodEnum::ONLINE->value;
        }

        if ($this->form->delivery_method === DeliveryMethodEnum::COURIER->value && Auth::check()) {
            $address = $this->addresses()->first(
                fn (Address $address): bool => $address->is_default && $address->city_id === $this->form->city_id,
            );

            if ($address !== null) {
                $this->form->fillAddress($address);
            }
        }

        if ($this->form->delivery_method === DeliveryMethodEnum::CDEK->value && Auth::check()) {
            $point = $this->savedDeliveryPoints()->first(
                fn (SavedDeliveryPoint $point): bool => $point->is_default && $point->city_id === $this->form->city_id,
            );

            if ($point !== null) {
                $this->form->delivery_point_code = $point->code;
                $this->saved_delivery_point_id = $point->id;
            }
        }
    }

    private function deliveryPoints(?City $city, array $packages): Collection
    {
        if (
            $city === null
            || $this->form->delivery_method !== DeliveryMethodEnum::CDEK->value
            || ! array_key_exists(DeliveryMethodEnum::CDEK->value, $this->checkout->availableDeliveryMethods($city))
        ) {
            return new Collection;
        }

        return collect($this->checkout->deliveryPoints($city, $packages))
            ->map(static fn ($point): array => [
                'code' => $point->code,
                'address' => $point->address,
                'work_time' => $point->workTime,
                'type' => $point->type,
            ]);
    }

    private function filterDeliveryPoints(Collection $points): Collection
    {
        $pointSearch = trim($this->delivery_point_search);

        return $pointSearch === '' ? new Collection : $points->filter(
            static fn (array $point): bool => mb_stripos($point['code'].' '.$point['address'], $pointSearch) !== false,
        );
    }

    private function isDeliveryReady(CheckoutPresentationDto $presentation): bool
    {
        return $presentation->deliveryReady
            && ! $this->getErrorBag()->has('quote')
            && ! $this->getErrorBag()->has('form.delivery_point_code');
    }

    private function addresses(): EloquentCollection
    {
        /** @var User */
        $user = Auth::user();

        return $this->cachedAddresses ??= $user?->addresses()
            ->with('city')
            ->orderByDesc('is_default')
            ->get() ?? new EloquentCollection;
    }

    private function savedDeliveryPoints(): EloquentCollection
    {
        /** @var User */
        $user = Auth::user();

        return $this->cachedSavedDeliveryPoints ??= $user?->savedDeliveryPoints()
            ->with('city')
            ->orderByDesc('is_default')
            ->orderByDesc('last_used_at')
            ->get() ?? new EloquentCollection;
    }
}
