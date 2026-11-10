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

it('гость видит один заказ по подписанной ссылке без служебных данных', function (): void {
    $order = CheckoutFixtures::order(null, ['delivery_details' => ['internal_error' => 'SECRET-INTERNAL-API-ERROR']]);
    $other = CheckoutFixtures::order();
    $url = URL::temporarySignedRoute('orders.guest.show', now()->addDays(30), ['token' => $order->guest_token]);

    $this->get($url)->assertOk()->assertSee($order->number)->assertDontSee($other->number)
        ->assertDontSee('SECRET-INTERNAL-API-ERROR');
});

it('гостевой доступ запрещён без подписи с истёкшей подписью или подменённым токеном', function (): void {
    $order = CheckoutFixtures::order();
    $other = CheckoutFixtures::order();
    $valid = URL::temporarySignedRoute('orders.guest.show', now()->addDays(30), ['token' => $order->guest_token]);
    $expired = URL::temporarySignedRoute('orders.guest.show', now()->subMinute(), ['token' => $order->guest_token]);

    $this->get(route('orders.guest.show', ['token' => $order->guest_token]))->assertForbidden();
    $this->get($expired)->assertForbidden();
    $this->get(str_replace($order->guest_token, $other->guest_token, $valid))->assertForbidden();
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

it('гость отменяет только новый заказ по подписанной ссылке', function (): void {
    $order = CheckoutFixtures::order();
    $confirmed = CheckoutFixtures::order(null, ['status' => 'CONFIRMED']);
    $url = URL::temporarySignedRoute('orders.guest.cancel', now()->addDays(30), ['token' => $order->guest_token]);
    $forbidden = URL::temporarySignedRoute('orders.guest.cancel', now()->addDays(30), ['token' => $confirmed->guest_token]);

    $this->post(route('orders.guest.cancel', ['token' => $order->guest_token]))->assertForbidden();
    $this->post($url, ['reason' => 'Не нужен'])->assertRedirect();
    $this->post($forbidden, ['reason' => 'Не нужен'])->assertForbidden();
    $this->assertDatabaseHas('orders', ['id' => $order->getKey(), 'status' => 'CANCELLED']);
    $this->assertDatabaseHas('orders', ['id' => $confirmed->getKey(), 'status' => 'CONFIRMED']);
});
