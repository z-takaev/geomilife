<?php

declare(strict_types=1);

use App\Enums\Cdek\CdekPrintStateEnum;
use App\Enums\Cdek\CdekRequestStateEnum;
use App\Enums\Filament\FilamentUserRoleEnum;
use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentMethodEnum;
use App\Enums\Orders\PaymentStatusEnum;
use App\Enums\Tbank\TbankPaymentStatusEnum;

it('сравнивает все варианты одного enum через is', function (string $enumClass): void {
    foreach ($enumClass::cases() as $actual) {
        foreach ($enumClass::cases() as $expected) {
            expect($actual->is($expected))->toBe($actual === $expected);
        }
    }
})->with([
    [OrderStatusEnum::class],
    [PaymentStatusEnum::class],
    [DeliveryMethodEnum::class],
    [PaymentMethodEnum::class],
    [FilamentUserRoleEnum::class],
    [CdekRequestStateEnum::class],
    [CdekPrintStateEnum::class],
    [TbankPaymentStatusEnum::class],
]);

it('отклоняет enum другого типа и строковое значение', function (string $enumClass): void {
    $case = $enumClass::cases()[0];
    $other = $case instanceof OrderStatusEnum ? TbankPaymentStatusEnum::NEW : OrderStatusEnum::NEW;

    expect(fn () => $case->is($other))->toThrow(TypeError::class)
        ->and(fn () => $case->is($case->value))->toThrow(TypeError::class);
})->with([
    [OrderStatusEnum::class],
    [PaymentStatusEnum::class],
    [DeliveryMethodEnum::class],
    [PaymentMethodEnum::class],
    [FilamentUserRoleEnum::class],
    [CdekRequestStateEnum::class],
    [CdekPrintStateEnum::class],
    [TbankPaymentStatusEnum::class],
]);

it('проверяет конкретный вариант именованным методом', function (BackedEnum $expected, string $method): void {
    foreach ($expected::cases() as $actual) {
        expect($actual->{$method}())->toBe($actual === $expected);
    }
})->with([
    'OrderStatusEnum::NEW' => [OrderStatusEnum::NEW, 'isNew'],
    'OrderStatusEnum::READY_FOR_PICKUP' => [OrderStatusEnum::READY_FOR_PICKUP, 'isReadyForPickup'],
    'OrderStatusEnum::READY_TO_SHIP' => [OrderStatusEnum::READY_TO_SHIP, 'isReadyToShip'],
    'OrderStatusEnum::DELIVERED' => [OrderStatusEnum::DELIVERED, 'isDelivered'],
    'OrderStatusEnum::CANCELLED' => [OrderStatusEnum::CANCELLED, 'isCancelled'],
    'PaymentStatusEnum::PAID' => [PaymentStatusEnum::PAID, 'isPaid'],
    'PaymentStatusEnum::AUTHORIZED' => [PaymentStatusEnum::AUTHORIZED, 'isAuthorized'],
    'PaymentStatusEnum::CANCELLED' => [PaymentStatusEnum::CANCELLED, 'isCancelled'],
    'PaymentStatusEnum::REFUND_PENDING' => [PaymentStatusEnum::REFUND_PENDING, 'isRefundPending'],
    'PaymentStatusEnum::REFUNDED' => [PaymentStatusEnum::REFUNDED, 'isRefunded'],
    'DeliveryMethodEnum::PICKUP' => [DeliveryMethodEnum::PICKUP, 'isPickup'],
    'DeliveryMethodEnum::COURIER' => [DeliveryMethodEnum::COURIER, 'isCourier'],
    'DeliveryMethodEnum::CDEK' => [DeliveryMethodEnum::CDEK, 'isCdek'],
    'PaymentMethodEnum::ONLINE' => [PaymentMethodEnum::ONLINE, 'isOnline'],
    'PaymentMethodEnum::PICKUP' => [PaymentMethodEnum::PICKUP, 'isPickup'],
    'FilamentUserRoleEnum::ADMIN' => [FilamentUserRoleEnum::ADMIN, 'isAdmin'],
    'FilamentUserRoleEnum::MANAGER' => [FilamentUserRoleEnum::MANAGER, 'isManager'],
    'CdekRequestStateEnum::SUCCESSFUL' => [CdekRequestStateEnum::SUCCESSFUL, 'isSuccessful'],
    'CdekRequestStateEnum::INVALID' => [CdekRequestStateEnum::INVALID, 'isInvalid'],
    'CdekPrintStateEnum::READY' => [CdekPrintStateEnum::READY, 'isReady'],
    'TbankPaymentStatusEnum::CANCELED' => [TbankPaymentStatusEnum::CANCELED, 'isCanceled'],
    'TbankPaymentStatusEnum::REVERSED' => [TbankPaymentStatusEnum::REVERSED, 'isReversed'],
]);
