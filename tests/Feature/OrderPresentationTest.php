<?php

declare(strict_types=1);

use App\Support\Values\Money;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\Support\CheckoutFixtures;

beforeEach(function (): void {
    Notification::fake();
    Queue::fake();
    Http::preventStrayRequests();
});

it('скрывает количество в карточке и профиль в хлебных крошках списка', function (): void {
    $user = CheckoutFixtures::user();
    CheckoutFixtures::order($user);

    $this->actingAs($user)->get(route('orders.index'))->assertOk()
        ->assertDontSee('шт.')
        ->assertViewHas('breadcrumbs', fn ($breadcrumbs): bool => array_column($breadcrumbs->items(), 'label') === ['Главная', 'Мои заказы']);
});

it('показывает количество рядом с итогом товара без старой цены и автообновления', function (): void {
    $user = CheckoutFixtures::user();
    $order = CheckoutFixtures::order($user);
    $variant = CheckoutFixtures::variant();
    $order->items()->create([
        'product_id' => $variant->product_id,
        'product_variant_id' => $variant->getKey(),
        'product_name' => 'Товар из заказа',
        'variant_name' => '250 мл',
        'sku' => $variant->sku,
        'price' => Money::fromRubles(600),
        'old_price' => Money::fromRubles(700),
        'quantity' => 2,
        'total' => Money::fromRubles(1200),
    ]);

    $this->actingAs($user)->get(route('orders.show', $order))->assertOk()
        ->assertSee('2 × 1 200 ₽')
        ->assertSee('600 ₽')
        ->assertDontSee('2 шт.')
        ->assertDontSee('700 ₽')
        ->assertDontSee('<del>', false)
        ->assertDontSee('window.location.reload', false)
        ->assertSee('<h1 class="page-title font-sora">Детали заказа</h1>', false)
        ->assertViewHas('breadcrumbs', fn ($breadcrumbs): bool => array_column($breadcrumbs->items(), 'label') === ['Главная', 'Мои заказы', 'Детали заказа']);
});

it('показывает бесплатную и платную доставку в деталях заказа', function (int $deliveryPrice, string $label): void {
    $user = CheckoutFixtures::user();
    $order = CheckoutFixtures::order($user, ['delivery_price' => Money::fromRubles($deliveryPrice)]);

    $this->actingAs($user)->get(route('orders.show', $order))->assertOk()
        ->assertSee('<dd>'.$label.'</dd>', false);
})->with([[0, 'бесплатно'], [350, '350 ₽']]);

it('скрывает причину отмены от покупателя и гостя', function (): void {
    $user = CheckoutFixtures::user();
    $order = CheckoutFixtures::order($user, [
        'status' => 'CANCELLED',
        'cancellation_reason' => 'Служебный комментарий отмены',
    ]);

    $this->actingAs($user)->get(route('orders.show', $order))->assertOk()
        ->assertSee('Заказ отменён.')
        ->assertDontSee($order->cancellation_reason);

    $url = URL::temporarySignedRoute('orders.guest.show', now()->addDay(), ['token' => $order->guest_token]);
    $this->get($url)->assertOk()
        ->assertSee('Заказ отменён.')
        ->assertDontSee($order->cancellation_reason)
        ->assertViewHas('breadcrumbs', fn ($breadcrumbs): bool => array_column($breadcrumbs->items(), 'label') === ['Главная', 'Детали заказа']);
});
