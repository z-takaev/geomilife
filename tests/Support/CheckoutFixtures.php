<?php

declare(strict_types=1);

namespace Tests\Support;

use App\DTO\Cdek\CdekDeliveryPointResponseDto;
use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\PaymentMethodEnum;
use App\Enums\Products\ProductUnitEnum;
use App\Livewire\Checkout;
use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Support\Values\Money;
use Database\Seeders\SettingSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

final class CheckoutFixtures
{
    public static function privacyPolicy(): Setting
    {
        $path = 'documents/privacy-policy/policy.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4 тестовый документ');

        return Setting::query()->updateOrCreate([], [
            'privacy_policy' => $path,
            'benefits' => SettingSeeder::DEFAULT_BENEFITS,
        ]);
    }

    public static function city(array $attributes = []): City
    {
        return City::query()->create(array_replace([
            'cdek_code' => random_int(10000, 999999),
            'name' => 'Владикавказ',
            'is_active' => true,
            'is_pickup_available' => true,
            'is_courier_available' => true,
            'is_cdek_excluded' => true,
            'courier_delivery_price' => Money::fromRubles(300),
            'door_delivery_price' => Money::fromRubles(100),
            'floor_lifting_price' => Money::fromRubles(50),
            'pickup_name' => 'Точка выдачи',
            'pickup_address' => 'улица Тестовая, 15',
            'pickup_working_hours' => 'Пн–Пт 10:00–18:00',
            'pickup_phone' => '+79991234567',
        ], $attributes));
    }

    public static function user(array $attributes = []): User
    {
        $user = User::query()->create(array_replace([
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'phone' => '+7999'.random_int(1000000, 9999999),
            'email' => Str::uuid().'@example.com',
            'password' => 'Password123',
        ], $attributes));
        $user->markEmailAsVerified();

        return $user;
    }

    public static function variant(array $attributes = []): ProductVariant
    {
        $category = Category::query()->firstOrCreate(['slug' => 'checkout'], ['name' => 'Для оформления', 'is_active' => true]);
        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'Масло для заказа',
            'slug' => (string) Str::uuid(),
            'is_active' => true,
        ]);

        return $product->variants()->create(array_replace([
            'sku' => (string) Str::uuid(),
            'unit' => ProductUnitEnum::MILLILITER,
            'value' => 250,
            'price' => Money::fromRubles(890),
            'is_active' => true,
            'is_preorder' => false,
        ], $attributes));
    }

    public static function address(City $city, array $attributes = []): array
    {
        return array_replace([
            'city_id' => $city->getKey(),
            'street' => 'Мира',
            'house' => '10',
            'building' => null,
            'apartment' => null,
            'entrance' => null,
            'floor' => null,
            'intercom' => null,
        ], $attributes);
    }

    public static function form(City $city, array $attributes = []): Testable
    {
        $component = Livewire::test(Checkout::class);
        $values = array_replace([
            'first_name' => 'Анна',
            'last_name' => 'Петрова',
            'phone' => '+79991234567',
            'email' => 'recipient@example.com',
            'city_id' => $city->getKey(),
            'delivery_method' => DeliveryMethodEnum::PICKUP->value,
            'payment_method' => PaymentMethodEnum::PICKUP->value,
            'agreement' => true,
        ], $attributes);

        foreach ($values as $key => $value) {
            $component->set('form.'.$key, $value);
        }

        return $component;
    }

    public static function point(City $city, string $code = 'VLZ100'): CdekDeliveryPointResponseDto
    {
        return new CdekDeliveryPointResponseDto(
            code: $code,
            type: 'PVZ',
            cityCode: $city->cdek_code,
            address: 'Пункт на Мира, 25',
            latitude: 43.03,
            longitude: 44.68,
            workTime: '10:00–20:00',
        );
    }

    public static function order(?User $user = null, array $attributes = []): Order
    {
        return Order::query()->create(array_replace([
            'user_id' => $user?->getKey(),
            'status' => 'NEW',
            'payment_status' => 'UNPAID',
            'payment_method' => PaymentMethodEnum::PICKUP->value,
            'delivery_method' => DeliveryMethodEnum::PICKUP->value,
            'delivery_price' => Money::fromRubles(0),
            'subtotal' => Money::fromRubles(1200),
            'total' => Money::fromRubles(1200),
            'customer_name' => 'Получатель заказа',
            'customer_phone' => '+79991234567',
            'customer_email' => $user?->email ?? 'guest@example.com',
            'delivery_address' => ['city' => 'Владикавказ', 'address' => 'Мира, 15'],
            'delivery_details' => [],
            'idempotency_key' => (string) Str::uuid(),
            'is_preorder' => false,
        ], $attributes));
    }

    public static function fakeBank(): void
    {
        config([
            'tbank.base_url' => 'https://tbank.checkout.test/v2',
            'tbank.terminal_key' => 'checkout-terminal',
            'tbank.password' => 'checkout-secret',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'tbank.checkout.test/*' => static fn (Request $request) => Http::response([
                'Success' => true,
                'ErrorCode' => '0',
                'Status' => 'NEW',
                'PaymentId' => '731000001',
                'OrderId' => $request['OrderId'],
                'Amount' => $request['Amount'],
                'PaymentURL' => 'https://tbank.checkout.test/payment/731000001',
            ]),
        ]);
    }

    public static function fakeCdek(City $city, int $deliveryMinor, ?\Closure $beforeCalculation = null): void
    {
        config([
            'services.cdek.base_url' => 'https://cdek.checkout.test',
            'services.cdek.client_id' => 'checkout-client',
            'services.cdek.client_secret' => 'checkout-secret',
        ]);
        Http::fake([
            'cdek.checkout.test/v2/oauth/token*' => Http::response(['access_token' => 'checkout-token', 'expires_in' => 3600]),
            'cdek.checkout.test/v2/deliverypoints*' => Http::response([[
                'code' => 'MSK100', 'type' => 'PVZ', 'work_time' => '09:00–21:00',
                'location' => ['city_code' => $city->cdek_code, 'address_full' => 'Москва, улица Тестовая, 25', 'latitude' => 55.75, 'longitude' => 37.61],
                'weight_min' => 0, 'weight_max' => 30,
            ]]),
            'cdek.checkout.test/v2/calculator/tariff' => static function () use ($deliveryMinor, $beforeCalculation) {
                $beforeCalculation?->__invoke();

                return Http::response([
                    'delivery_sum' => $deliveryMinor / 100, 'total_sum' => $deliveryMinor / 100,
                    'currency' => 'RUB', 'period_min' => 2, 'period_max' => 4, 'weight_calc' => 600,
                ]);
            },
        ]);
    }
}
