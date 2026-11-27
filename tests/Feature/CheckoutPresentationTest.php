<?php

declare(strict_types=1);

use App\Livewire\Checkout;
use App\Services\Cart\CartService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

it('показывает подсказки оформления только после попытки отправки и убирает устранённую причину', function (): void {
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);
    $city = CheckoutFixtures::city();
    Livewire::test(Checkout::class)->assertDontSeeHtml('id="checkout-block-reason"')
        ->call('placeOrder')->assertSee('Выберите город для доставки.')
        ->call('selectCity', $city->id)->assertDontSeeHtml('id="checkout-block-reason"');
});

it('сохраняет город профиля при выборе города доставки', function (): void {
    $home = CheckoutFixtures::city();
    $other = CheckoutFixtures::city(['name' => 'Другой город']);
    $user = CheckoutFixtures::user(['city_id' => $home->id]);
    $this->actingAs($user);

    Livewire::test(Checkout::class)->assertSet('form.city_id', $home->id)
        ->call('selectCity', $other->id)->assertSet('form.city_id', $other->id)
        ->assertDontSee('Выбрать другой')->assertDontSee('Повторить расчёт');

    expect($user->fresh()->city_id)->toBe($home->id);
});

it('использует город гостя из шапки', function (): void {
    $city = CheckoutFixtures::city();
    session()->put('city', ['id' => $city->id, 'selected' => true]);
    Livewire::test(Checkout::class)->assertSet('form.city_id', $city->id);
});

it('оставляет город гостя пустым если город неизвестен', function (): void {
    Livewire::test(Checkout::class)->assertSet('form.city_id', null)
        ->assertSee('Начните вводить город');
});

it('не ищет уже выбранный город повторно', function (): void {
    $city = CheckoutFixtures::city();
    DB::enableQueryLog();

    Livewire::test(Checkout::class)->call('selectCity', $city->id);

    $searchQueries = collect(DB::getQueryLog())->filter(
        fn (array $query): bool => str_contains($query['query'], 'from `cities`')
            && str_contains($query['query'], 'like'),
    );
    DB::disableQueryLog();

    expect($searchQueries)->toBeEmpty();
});
