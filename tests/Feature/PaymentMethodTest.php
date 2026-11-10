<?php

declare(strict_types=1);

use App\Enums\Orders\PaymentMethodEnum;
use App\Models\Order;
use App\Services\Cart\CartService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CheckoutFixtures;

it('сохраняет способ оплаты и восстанавливает его как enum', function (): void {
    $order = CheckoutFixtures::order(attributes: ['payment_method' => PaymentMethodEnum::PICKUP]);

    expect($order->fresh()->payment_method)->toBe(PaymentMethodEnum::PICKUP)
        ->and($order->payment_expires_at)->toBeNull();
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_method' => 'PICKUP']);
});

it('задаёт срок онлайн-оплаты при создании заказа', function (): void {
    $this->travelTo(now()->startOfSecond());
    $order = CheckoutFixtures::order(attributes: ['payment_method' => PaymentMethodEnum::ONLINE]);

    expect($order->fresh()->payment_method)->toBe(PaymentMethodEnum::ONLINE)
        ->and($order->payment_expires_at->equalTo(now()->addMinutes((int) config('orders.payment_timeout_minutes'))))->toBeTrue();
});

it('предоставляет подписи способов оплаты', function (): void {
    expect(PaymentMethodEnum::ONLINE->getLabel())->toBe('Картой онлайн')
        ->and(PaymentMethodEnum::PICKUP->getLabel())->toBe('При получении');
});

it('отклоняет недопустимый способ оплаты при оформлении', function (string $delivery, string $payment): void {
    Notification::fake();
    Queue::fake();
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);

    CheckoutFixtures::form($city, ['delivery_method' => $delivery, 'payment_method' => $payment])
        ->call('placeOrder')->assertHasErrors(['payment_method']);

    expect(Order::query()->count())->toBe(0);
})->with([
    'оплата при самовывозе для курьера' => ['COURIER', 'PICKUP'],
    'неизвестное значение' => ['PICKUP', 'INVALID'],
    'старое значение' => ['PICKUP', 'ON_RECEIPT'],
]);
