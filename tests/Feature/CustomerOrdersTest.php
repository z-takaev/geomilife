<?php

declare(strict_types=1);

use App\Support\Values\Money;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CheckoutFixtures;

beforeEach(function (): void {
    Notification::fake();
    Queue::fake();
    Http::preventStrayRequests();
    Http::fake();
});

it('показывает карточки только своих заказов начиная с новых', function (): void {
    $user = CheckoutFixtures::user();
    $old = CheckoutFixtures::order($user, ['number' => 'OWN-OLD-301', 'created_at' => now()->subDay()]);
    $new = CheckoutFixtures::order($user, ['number' => 'OWN-NEW-302', 'created_at' => now()]);
    $foreign = CheckoutFixtures::order(CheckoutFixtures::user(), ['number' => 'FOREIGN-SECRET-303']);

    $this->actingAs($user)->get(route('orders.index'))->assertOk()
        ->assertSeeInOrder([$new->number, $old->number])->assertDontSee($foreign->number)
        ->assertSee('1 200 ₽')->assertDontSee('Страница заказов находится в разработке.');
});

it('показывает пустое состояние списка вместо заглушки разработки', function (): void {
    $this->actingAs(CheckoutFixtures::user())->get(route('orders.index'))->assertOk()
        ->assertSee('У вас пока нет заказов')->assertDontSee('Страница заказов находится в разработке.');
});

it('детали используют снимки получателя товаров и адреса а не текущие справочники', function (): void {
    $user = CheckoutFixtures::user(['first_name' => 'Имя профиля']);
    $order = CheckoutFixtures::order($user, [
        'customer_name' => 'Получатель из снимка',
        'delivery_address' => ['city' => 'Владикавказ', 'address' => 'Историческая улица, 17'],
        'comment' => 'Позвонить перед выдачей',
        'is_preorder' => true,
    ]);
    $variant = CheckoutFixtures::variant();
    $order->items()->create([
        'product_id' => $variant->product_id, 'product_variant_id' => $variant->getKey(),
        'product_name' => 'Масло из снимка', 'variant_name' => '250 мл', 'sku' => $variant->sku,
        'price' => Money::fromRubles(600), 'old_price' => Money::fromRubles(700), 'quantity' => 2, 'total' => Money::fromRubles(1200),
    ]);
    $variant->product->update(['name' => 'Изменённый каталог']);
    $variant->update(['price' => Money::fromRubles(999)]);

    $this->actingAs($user)->get(route('orders.show', $order))->assertOk()
        ->assertSee('Получатель из снимка')->assertSee('Масло из снимка')->assertSee('250 мл')
        ->assertSee('Историческая улица, 17')->assertSee('Позвонить перед выдачей')
        ->assertSee('600 ₽')->assertSee('1 200 ₽')->assertDontSee('Изменённый каталог')
        ->assertSee('Точные сроки доставки согласовываются отдельно с менеджером')
        ->assertDontSee('Детальная страница заказа находится в разработке.');
});

it('чужая детальная страница недоступна даже при совпадении email получателя', function (): void {
    $user = CheckoutFixtures::user();
    $foreign = CheckoutFixtures::order(CheckoutFixtures::user(), ['customer_email' => $user->email]);
    $guest = CheckoutFixtures::order(null, ['customer_email' => $user->email]);

    $this->actingAs($user)->get(route('orders.show', $foreign))->assertNotFound();
    $this->actingAs($user)->get(route('orders.show', $guest))->assertNotFound();
    $this->get(route('orders.index'))->assertDontSee($foreign->number)->assertDontSee($guest->number);
});

it('гость не получает список и детали заказа без авторизации', function (): void {
    $order = CheckoutFixtures::order(CheckoutFixtures::user());

    $this->getJson(route('orders.index'))->assertUnauthorized();
    $this->getJson(route('orders.show', $order))->assertUnauthorized();
});

it('старые гостевые ссылки не предоставляют доступ к заказу и действиям', function (): void {
    $order = CheckoutFixtures::order();
    $path = '/orders/guest/старый-токен?expires=9999999999&signature=old';

    $this->get($path)->assertNotFound();
    $this->post('/orders/guest/старый-токен/cancel')->assertNotFound();
    $this->post('/orders/guest/старый-токен/pay')->assertNotFound();
    expect($order->fresh()->status->value)->toBe('NEW');
});

it('владелец может отменить только новый заказ и не может отменить чужой', function (): void {
    $user = CheckoutFixtures::user();
    $new = CheckoutFixtures::order($user);
    $confirmed = CheckoutFixtures::order($user, ['status' => 'CONFIRMED']);
    $foreign = CheckoutFixtures::order(CheckoutFixtures::user());

    $this->actingAs($user)->post(route('orders.cancel', $new), ['reason' => 'Изменились планы'])->assertRedirect();
    $this->assertDatabaseHas('orders', ['id' => $new->getKey(), 'status' => 'CANCELLED']);
    $this->post(route('orders.cancel', $confirmed), ['reason' => 'Поздняя отмена'])->assertForbidden();
    $this->post(route('orders.cancel', $foreign), ['reason' => 'Подмена'])->assertNotFound();
    $this->assertDatabaseHas('orders', ['id' => $confirmed->getKey(), 'status' => 'CONFIRMED']);
    $this->assertDatabaseHas('orders', ['id' => $foreign->getKey(), 'status' => 'NEW']);
    Http::assertNothingSent();
});

it('проверяет владельца и состояние заказа до валидации причины отмены', function (): void {
    $user = CheckoutFixtures::user();
    $new = CheckoutFixtures::order($user);
    $confirmed = CheckoutFixtures::order($user, ['status' => 'CONFIRMED']);
    $pending = CheckoutFixtures::order($user, ['cancellation_state' => 'PENDING']);
    $foreign = CheckoutFixtures::order(CheckoutFixtures::user());
    $payload = ['reason' => str_repeat('а', 1001)];

    $this->actingAs($user)->postJson(route('orders.cancel', $foreign), $payload)->assertNotFound();
    $this->postJson(route('orders.cancel', $confirmed), $payload)->assertForbidden();
    $this->postJson(route('orders.cancel', $pending), $payload)->assertForbidden();
    $this->postJson(route('orders.cancel', $new), $payload)->assertUnprocessable()->assertJsonValidationErrors('reason');
    $this->assertDatabaseHas('orders', ['id' => $new->id, 'status' => 'NEW', 'cancellation_state' => null]);
});

it('использует причину по умолчанию при отмене заказа без причины', function (): void {
    $user = CheckoutFixtures::user();
    $order = CheckoutFixtures::order($user);

    $this->actingAs($user)->post(route('orders.cancel', $order))->assertRedirect(route('orders.show', $order));

    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'CANCELLED', 'cancellation_reason' => __('Отмена покупателем')]);
});
