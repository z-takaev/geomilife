<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\PaymentMethodEnum;
use App\Models\City;
use App\Models\Setting;
use App\Services\Orders\CheckoutService;
use Illuminate\Validation\Rule;
use Livewire\Form;

final class CheckoutForm extends Form
{
    public string $first_name = '';

    public string $last_name = '';

    public string $phone = '';

    public string $email = '';

    public ?int $city_id = null;

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

    public string $delivery_point_code = '';

    public function orderData(): array
    {
        return $this->only([
            'first_name', 'last_name', 'phone', 'email', 'city_id', 'delivery_method', 'payment_method', 'address_id',
            'street', 'house', 'building', 'apartment', 'entrance', 'floor', 'intercom', 'delivery_to_door', 'comment', 'delivery_point_code',
        ]);
    }

    public function selectedCity(): ?City
    {
        return $this->city_id === null ? null : City::query()->find($this->city_id);
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
            'delivery_method' => ['required', Rule::in(array_keys(app(CheckoutService::class)->availableDeliveryMethods($this->selectedCity())))],
            'payment_method' => ['required', Rule::enum(PaymentMethodEnum::class)->only($this->delivery_method === DeliveryMethodEnum::PICKUP->value ? PaymentMethodEnum::cases() : [PaymentMethodEnum::ONLINE])],
            'agreement' => Setting::cached()?->privacy_policy ? ['accepted'] : ['boolean'],
            'delivery_point_code' => [Rule::requiredIf($this->delivery_method === DeliveryMethodEnum::CDEK->value), 'string', 'max:255'],
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
        $noDeliveryMethods = $city !== null && app(CheckoutService::class)->availableDeliveryMethods($city) === [];

        return [
            'city_id.required' => __('Выберите город для доставки.'),
            'delivery_method.required' => $noDeliveryMethods ? __('В этом городе нет доступных способов доставки.') : __('Выберите способ доставки.'),
            'delivery_method.in' => $noDeliveryMethods ? __('В этом городе нет доступных способов доставки.') : __('Выбранный способ доставки недоступен.'),
            'delivery_point_code.required' => __('Выберите пункт выдачи.'),
            'agreement.accepted' => __('Необходимо согласиться с политикой обработки персональных данных.'),
        ];
    }
}
