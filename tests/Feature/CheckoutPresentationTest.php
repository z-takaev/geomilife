<?php

declare(strict_types=1);

use App\Livewire\Checkout;
use App\Services\Cart\CartService;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

it('показывает подсказки оформления только после попытки отправки и убирает устранённую причину', function (): void {
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);
    $city = CheckoutFixtures::city();
    Livewire::test(Checkout::class)->assertDontSeeHtml('id="checkout-block-reason"')
        ->call('placeOrder')->assertSee('Выберите город для доставки.')
        ->call('selectCity', $city->id)->assertDontSeeHtml('id="checkout-block-reason"');
});

it('возвращает город пользователя при выборе другого ПВЗ и не выбирает сохранённый пункт повторно', function (): void {
    $home = CheckoutFixtures::city();
    $other = CheckoutFixtures::city(['name' => 'Другой город']);
    $user = CheckoutFixtures::user(['city_id' => $home->id]);
    $this->actingAs($user);
    Livewire::test(Checkout::class)->call('selectCity', $other->id)
        ->call('chooseAnotherDeliveryPoint')->assertSet('form.city_id', $home->id)
        ->assertSet('city_search', $home->name)->assertSet('form.delivery_point_code', '')
        ->assertSet('saved_delivery_point_id', null);
});

it('возвращает определённый системой город гостя при выборе другого ПВЗ', function (): void {
    $city = CheckoutFixtures::city();
    session()->put('city.id', $city->id);
    Livewire::test(Checkout::class)->call('selectCity', CheckoutFixtures::city()->id)
        ->call('chooseAnotherDeliveryPoint')->assertSet('form.city_id', $city->id)->assertSet('city_search', $city->name);
});

it('оставляет выбор города пустым если город пользователя и системы неизвестен', function (): void {
    Livewire::test(Checkout::class)->call('selectCity', CheckoutFixtures::city()->id)
        ->call('chooseAnotherDeliveryPoint')->assertSet('form.city_id', null)->assertSet('city_search', '')
        ->assertSee('Выберите город (от 3 символов)');
});
