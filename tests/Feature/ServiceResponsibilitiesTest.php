<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentMethodEnum;
use App\Enums\Orders\PaymentStatusEnum;
use App\Models\FilamentUser;
use App\Models\OrderNotificationDelivery;
use App\Services\Cart\CartService;
use App\Services\Orders\OrderLifecycleService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\Support\CheckoutFixtures;
use Tests\Support\PaymentFixtures;

beforeEach(function (): void {
    Notification::fake();
    Http::preventStrayRequests();
});

it('списывает только заказанное количество и обновляет кеш корзины', function (bool $authenticated): void {
    $ordered = CheckoutFixtures::variant();
    $removed = CheckoutFixtures::variant();
    $untouched = CheckoutFixtures::variant();

    if ($authenticated) {
        $this->actingAs(CheckoutFixtures::user());
    }

    $cart = app(CartService::class);
    $cart->add($ordered->id, 5);
    $cart->add($removed->id, 2);
    $cart->add($untouched->id, 3);
    expect($cart->count())->toBe(10);

    DB::transaction(fn () => $cart->removeOrderedItems([
        ['product_variant_id' => $ordered->id, 'quantity' => 2],
        ['product_variant_id' => $removed->id, 'quantity' => 2],
    ]));

    expect($cart->quantities())->toEqual([$ordered->id => 3, $untouched->id => 3])
        ->and($cart->count())->toBe(6)
        ->and(app(CartService::class)->count())->toBe(6);
})->with([false, true]);

it('при списании не затрагивает корзину другого покупателя', function (): void {
    $variant = CheckoutFixtures::variant();
    $owner = CheckoutFixtures::user();
    $owner->cart()->create()->items()->create(['product_variant_id' => $variant->id, 'quantity' => 5]);
    $this->actingAs(CheckoutFixtures::user());
    $cart = app(CartService::class);
    $cart->add($variant->id, 3);

    DB::transaction(fn () => $cart->removeOrderedItems([
        ['product_variant_id' => $variant->id, 'quantity' => 2],
    ]));

    expect($cart->quantities())->toBe([$variant->id => 1])
        ->and($owner->cart->items()->sole()->quantity)->toBe(5);
});

it('фиксирует оплату и выдачу при самовывозе один раз', function (FilamentUserRoleEnum $role): void {
    $order = PaymentFixtures::order([
        'payment_method' => PaymentMethodEnum::PICKUP,
        'delivery_method' => DeliveryMethodEnum::PICKUP,
        'status' => OrderStatusEnum::READY_FOR_PICKUP,
    ]);
    $actor = PaymentFixtures::manager();
    $actor->update(['role' => $role]);
    $lifecycle = app(OrderLifecycleService::class);

    $lifecycle->advanceStatus($order, OrderStatusEnum::DELIVERED, $actor);
    $lifecycle->advanceStatus($order->fresh(), OrderStatusEnum::DELIVERED, $actor);

    expect($order->fresh()->payment_status)->toBe(PaymentStatusEnum::PAID)
        ->and($order->fresh()->status)->toBe(OrderStatusEnum::DELIVERED)
        ->and(OrderNotificationDelivery::query()->where('order_id', $order->id)->count())->toBe(1);
    Http::assertNothingSent();
})->with([FilamentUserRoleEnum::ADMIN, FilamentUserRoleEnum::MANAGER]);

it('запрещает выдачу самовывоза при неподходящем состоянии заказа', function (array $attributes): void {
    $order = PaymentFixtures::order(array_replace([
        'payment_method' => PaymentMethodEnum::PICKUP,
        'delivery_method' => DeliveryMethodEnum::PICKUP,
        'status' => OrderStatusEnum::READY_FOR_PICKUP,
    ], $attributes));

    expect(fn () => app(OrderLifecycleService::class)->advanceStatus($order, OrderStatusEnum::DELIVERED, PaymentFixtures::manager()))
        ->toThrow(ValidationException::class);

    expect($order->fresh()->payment_status)->toBe(PaymentStatusEnum::UNPAID);
})->with([
    'онлайн-оплата' => [['payment_method' => PaymentMethodEnum::ONLINE]],
    'курьер' => [['delivery_method' => DeliveryMethodEnum::COURIER]],
    'отменён' => [['status' => 'CANCELLED']],
    'отмена в процессе' => [['cancellation_state' => 'PENDING']],
]);

it('не позволяет несохранённому сотруднику подтвердить выдачу', function (): void {
    $order = PaymentFixtures::order([
        'payment_method' => PaymentMethodEnum::PICKUP,
        'delivery_method' => DeliveryMethodEnum::PICKUP,
        'status' => OrderStatusEnum::READY_FOR_PICKUP,
    ]);
    $actor = new FilamentUser(['role' => FilamentUserRoleEnum::MANAGER]);

    expect(fn () => app(OrderLifecycleService::class)->advanceStatus($order, OrderStatusEnum::DELIVERED, $actor))
        ->toThrow(AuthorizationException::class);

    expect($order->fresh()->payment_status)->toBe(PaymentStatusEnum::UNPAID);
});
