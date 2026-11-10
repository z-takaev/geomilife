<?php

declare(strict_types=1);

use App\Enums\Orders\PaymentMethodEnum;
use App\Models\Order;
use App\Services\Cart\CartService;
use App\Services\Orders\OrderPaymentService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\Support\CheckoutFixtures;
use Tests\Support\PaymentFixtures;

beforeEach(function (): void {
    Notification::fake();
    Queue::fake();
    CheckoutFixtures::fakeBank();
});

it('не предоставляет повторную оплату через страницу и маршрут заказа', function (): void {
    $order = CheckoutFixtures::order(CheckoutFixtures::user(), ['payment_method' => PaymentMethodEnum::ONLINE]);
    $this->actingAs($order->user);

    expect(Route::has('orders.pay'))->toBeFalse();
    $this->post('/orders/'.$order->id.'/pay')->assertNotFound();
    $this->get(route('orders.show', $order))->assertOk()->assertDontSee('Оплатить заказ')->assertDontSee('Оплатите до');
    $this->get(route('orders.index'))->assertOk()->assertDontSee('Оплатить');
    Http::assertNothingSent();
});

it('не хранит часовой срок оплаты и не планирует автоотмену по нему', function (): void {
    expect(Schema::hasColumn('orders', 'payment_expires_at'))->toBeFalse()
        ->and(Artisan::all())->not->toHaveKey('orders:expire');
});

it('не создаёт новую попытку оплаты существующего платежа', function (string $status): void {
    $order = PaymentFixtures::order();
    PaymentFixtures::payment($order, ['status' => $status]);

    expect(fn () => app(OrderPaymentService::class)->startOnlinePayment($order))->toThrow(ValidationException::class);
    expect($order->payments()->count())->toBe(1);
    Http::assertNothingSent();
})->with(['PENDING', 'FAILED', 'CANCELLED', 'AUTHORIZED']);

it('при повторной отправке оформления не открывает оплату сохранённого заказа', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);
    $component = CheckoutFixtures::form($city, ['payment_method' => PaymentMethodEnum::ONLINE->value]);
    $component->call('placeOrder')->assertHasNoErrors()->assertRedirect('https://tbank.checkout.test/payment/731000001');
    $this->travel(15)->minutes();
    $component->call('placeOrder')->assertHasNoErrors()->assertRedirect(route('home'));

    expect(Order::query()->count())->toBe(1)
        ->and(Order::query()->sole()->payments()->count())->toBe(1);
    Http::assertSentCount(1);
});

it('предлагает онлайн оплату только сразу при оформлении', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);

    CheckoutFixtures::form($city, ['payment_method' => PaymentMethodEnum::ONLINE->value])
        ->assertSee('Оплата сразу при оформлении')
        ->assertDontSee('Оплатите в течение часа');
});

it('не задаёт часовой срок банковской ссылки при оформлении', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);

    CheckoutFixtures::form($city, ['payment_method' => PaymentMethodEnum::ONLINE->value])
        ->call('placeOrder')->assertHasNoErrors()->assertRedirect('https://tbank.checkout.test/payment/731000001');

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/Init')
        && ! array_key_exists('RedirectDueDate', $request->data()));
});
