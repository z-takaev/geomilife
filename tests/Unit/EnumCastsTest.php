<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentMethodEnum;
use App\Enums\Orders\PaymentStatusEnum;
use App\Enums\Products\ProductUnitEnum;
use App\Enums\Reviews\ReviewSourceEnum;
use App\Models\FilamentUser;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Review;

it('преобразует сохранённые значения в enum предметной области и обратно', function (string $modelClass, string $attribute, string $enumClass): void {
    foreach ($enumClass::cases() as $case) {
        $model = new $modelClass;
        $model->setRawAttributes([$attribute => $case->value]);

        expect($model->getAttribute($attribute))->toBe($case);

        $model->setAttribute($attribute, $case);

        expect($model->getAttributes()[$attribute])->toBe($case->value);
    }
})->with([
    'статус заказа' => [Order::class, 'status', OrderStatusEnum::class],
    'статус оплаты заказа' => [Order::class, 'payment_status', PaymentStatusEnum::class],
    'способ оплаты' => [Order::class, 'payment_method', PaymentMethodEnum::class],
    'способ доставки' => [Order::class, 'delivery_method', DeliveryMethodEnum::class],
    'статус платежа' => [Payment::class, 'status', PaymentStatusEnum::class],
    'единица измерения товара' => [ProductVariant::class, 'unit', ProductUnitEnum::class],
    'источник отзыва' => [Review::class, 'source', ReviewSourceEnum::class],
    'роль пользователя админ-панели' => [FilamentUser::class, 'role', FilamentUserRoleEnum::class],
]);
