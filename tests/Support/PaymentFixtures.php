<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\PaymentMethodEnum;
use App\Models\FilamentUser;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Tbank\TbankSignatureService;
use App\Support\Values\Money;
use Illuminate\Support\Str;

final class PaymentFixtures
{
    public const string SHIPMENT_UUID = 'f359a4b2-b37f-4939-94af-b7315cf47d18';

    public static function user(): User
    {
        $user = User::query()->create([
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'email' => Str::uuid().'@example.test',
            'password' => 'Password123',
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    public static function manager(): FilamentUser
    {
        return FilamentUser::query()->create([
            'name' => 'Менеджер',
            'email' => Str::uuid().'@example.test',
            'password' => 'Password123',
            'role' => FilamentUserRoleEnum::MANAGER,
        ]);
    }

    public static function order(array $attributes = []): Order
    {
        return Order::query()->create(array_replace([
            'user_id' => self::user()->id,
            'number' => 'TEST-'.Str::uuid(),
            'status' => 'NEW',
            'payment_status' => 'UNPAID',
            'payment_method' => PaymentMethodEnum::ONLINE->value,
            'delivery_method' => DeliveryMethodEnum::PICKUP->value,
            'delivery_price' => Money::fromRubles(350),
            'total' => Money::fromRubles(1350),
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+79991234567',
            'customer_email' => 'buyer@example.test',
            'delivery_address' => ['city' => 'Владикавказ', 'address' => 'Тестовая, 1'],
            'payment_expires_at' => now()->addHour(),
        ], $attributes));
    }

    public static function payment(Order $order, array $attributes = []): Payment
    {
        return $order->payments()->create(array_replace([
            'provider' => 'TBANK',
            'transaction_id' => '100001',
            'bank_order_id' => $order->number.'-1',
            'payment_url' => 'https://bank.test/pay/100001',
            'amount' => $order->total,
            'status' => 'PENDING',
            'provider_status' => 'NEW',
        ], $attributes));
    }

    public static function state(Payment $payment, string $status, array $overrides = []): array
    {
        return array_replace([
            'Success' => true,
            'ErrorCode' => '0',
            'TerminalKey' => 'TEST_TERMINAL',
            'PaymentId' => $payment->transaction_id,
            'OrderId' => $payment->bank_order_id,
            'Amount' => $payment->amount->minorAmount(),
            'Status' => $status,
        ], $overrides);
    }

    public static function notification(Payment $payment, string $status, array $overrides = []): array
    {
        $data = self::state($payment, $status, $overrides);
        $data['Token'] = app(TbankSignatureService::class)->generate($data);

        return $data;
    }

    public static function value(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    public static function shipment(string $status = 'CREATED', string $operation = 'CREATE'): array
    {
        return [
            'entity' => [
                'uuid' => self::SHIPMENT_UUID,
                'cdek_number' => '1234567890',
                'statuses' => [[
                    'code' => $status,
                    'name' => $status,
                    'date_time' => now()->toIso8601String(),
                ]],
            ],
            'requests' => [[
                'request_uuid' => '275dd974-20c9-4980-94e2-0e7b15891c72',
                'type' => $operation,
                'date_time' => now()->toIso8601String(),
                'state' => 'SUCCESSFUL',
                'errors' => [],
                'warnings' => [],
            ]],
        ];
    }
}
