<?php

declare(strict_types=1);

use App\Enums\Orders\PaymentMethodEnum;
use App\Models\Order;
use App\Services\Cart\CartService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CheckoutFixtures;

it('сохраняет способ оплаты и восстанавливает его как enum', function (PaymentMethodEnum $method): void {
    $order = CheckoutFixtures::order(attributes: ['payment_method' => $method]);

    expect($order->fresh()->payment_method)->toBe($method);
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_method' => $method->value]);
})->with(PaymentMethodEnum::cases());

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
        ->call('placeOrder')->assertHasErrors(['form.payment_method']);

    expect(Order::query()->count())->toBe(0);
})->with([
    'оплата при самовывозе для курьера' => ['COURIER', 'PICKUP'],
    'неизвестное значение' => ['PICKUP', 'INVALID'],
    'старое значение' => ['PICKUP', 'ON_RECEIPT'],
]);
