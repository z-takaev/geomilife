<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Services\Cart\CartService;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CheckoutFixtures;

it('не создаёт документ политики при заполнении настроек', function (): void {
    Storage::fake('public');

    $this->seed(SettingSeeder::class);

    expect(Setting::query()->sole()->privacy_policy)->toBeNull();
    expect(Storage::disk('public')->allFiles('documents/privacy-policy'))->toBeEmpty();
});

it('оформляет заказ без согласия при отсутствии документа', function (bool $hasSettings): void {
    Notification::fake();
    Queue::fake();

    if ($hasSettings) {
        Setting::query()->create();
    }

    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);

    CheckoutFixtures::form($city, ['agreement' => false])
        ->assertDontSeeHtml('wire:model="form.agreement"')
        ->assertDontSee('Соглашаюсь с')
        ->call('placeOrder')->assertHasNoErrors();

    $this->assertDatabaseCount('orders', 1);
})->with([false, true]);

it('показывает обязательное согласие и блокирует заказ до подтверждения при наличии документа', function (): void {
    Notification::fake();
    Queue::fake();
    Storage::fake('public');
    $setting = CheckoutFixtures::privacyPolicy();
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);

    $component = CheckoutFixtures::form($city, ['agreement' => false])
        ->assertSeeHtml('wire:model="form.agreement"')
        ->assertSeeHtml($setting->privacyPolicyUrl())
        ->call('placeOrder')->assertHasErrors(['form.agreement' => 'accepted']);

    $this->assertDatabaseCount('orders', 0);

    $component->set('form.agreement', true)->call('placeOrder')->assertHasNoErrors();

    $this->assertDatabaseCount('orders', 1);
});

it('проверяет наличие документа заново при отправке формы', function (): void {
    Storage::fake('public');
    Setting::query()->create();
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);
    $component = CheckoutFixtures::form($city, ['agreement' => false]);
    CheckoutFixtures::privacyPolicy();

    $component->call('placeOrder')->assertHasErrors(['form.agreement' => 'accepted']);

    $this->assertDatabaseCount('orders', 0);
});
