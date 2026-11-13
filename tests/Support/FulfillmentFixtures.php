<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\PaymentMethodEnum;
use App\Models\FilamentUser;
use App\Models\Order;
use App\Support\Values\Money;
use Illuminate\Support\Str;

function fulfillmentEmployee(FilamentUserRoleEnum $role = FilamentUserRoleEnum::MANAGER): FilamentUser
{
    return FilamentUser::query()->create([
        'name' => 'Менеджер склада', 'email' => Str::uuid().'@example.test',
        'password' => 'password', 'role' => $role,
    ]);
}

function fulfillmentOrder(array $attributes = []): Order
{
    return Order::query()->create(array_replace([
        'status' => 'NEW', 'payment_status' => 'PAID',
        'payment_method' => PaymentMethodEnum::ONLINE->value, 'delivery_method' => DeliveryMethodEnum::CDEK->value,
        'subtotal' => Money::fromRubles(1200), 'delivery_price' => Money::fromRubles(350),
        'total' => Money::fromRubles(1550), 'customer_name' => 'Покупатель из снимка',
        'customer_phone' => '+7 (999) 123-45-67', 'customer_email' => 'snapshot@example.test',
        'delivery_address' => [
            'city' => 'Москва', 'city_code' => 44, 'address' => 'ул. Тестовая, 10',
            'code' => 'MSK1', 'type' => 'PVZ',
        ],
        'packages' => [[
            'box_id' => null, 'number' => '1', 'length' => 260, 'width' => 75, 'height' => 70,
            'inner_length' => 250, 'inner_width' => 65, 'inner_height' => 60, 'weight' => 600,
            'items' => [[
                'product_variant_id' => 1, 'quantity' => 1, 'weight' => 500,
                'length' => 200, 'width' => 50, 'height' => 50,
            ]],
        ]],
        'is_preorder' => false, 'comment' => 'Позвонить перед выдачей',
    ], $attributes));
}

function fulfillmentOrderItem(Order $order, array $attributes = []): void
{
    $order->items()->create(array_replace([
        'product_id' => 1, 'product_variant_id' => 1, 'product_name' => 'Масло тмина',
        'variant_name' => '250 мл', 'sku' => 'FULFILLMENT-250', 'price' => Money::fromRubles(1200),
        'quantity' => 1, 'total' => Money::fromRubles(1200),
    ], $attributes));
}

function fulfillmentValue(mixed $value): mixed
{
    return $value instanceof BackedEnum ? $value->value : $value;
}

function fulfillmentCdekResponse(string $uuid, string $status = 'CREATED', string $state = 'SUCCESSFUL'): array
{
    return [
        'entity' => [
            'uuid' => $uuid, 'cdek_number' => '0000123456',
            'statuses' => [['code' => $status, 'name' => $status, 'date_time' => '2026-11-07T12:00:00+03:00']],
        ],
        'requests' => [[
            'type' => 'CREATE', 'state' => $state, 'date_time' => '2026-11-07T12:00:00+03:00',
            'errors' => [], 'warnings' => [],
        ]],
    ];
}
