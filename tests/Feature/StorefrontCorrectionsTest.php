<?php

declare(strict_types=1);

use App\Enums\Orders\OrderNotificationAudienceEnum;
use App\Livewire\Checkout;
use App\Livewire\CitySelector;
use App\Models\Order;
use App\Models\Setting;
use App\Notifications\Orders\OrderUpdateNotification;
use App\Services\Cart\CartService;
use App\Services\Cities\CurrentCityService;
use App\Services\Orders\OrderNotificationService;
use App\Support\Values\Money;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

it('выбор в шапке имеет приоритет и сохраняет город профиля', function (): void {
    $profileCity = CheckoutFixtures::city();
    $selectedCity = CheckoutFixtures::city();
    $user = CheckoutFixtures::user(['city_id' => $profileCity->id]);

    Livewire::actingAs($user)->test(CitySelector::class)->call('select', $selectedCity->id);

    expect($user->fresh()->city_id)->toBe($profileCity->id)
        ->and(app(CurrentCityService::class)->current()?->id)->toBe($selectedCity->id);

    Livewire::test(Checkout::class)->assertSet('form.city_id', $selectedCity->id)
        ->call('selectCity', $profileCity->id);

    expect(app(CurrentCityService::class)->current()?->id)->toBe($selectedCity->id)
        ->and($user->fresh()->city_id)->toBe($profileCity->id);
});

it('не перезаписывает город заказа событием выбора в шапке', function (): void {
    $city = CheckoutFixtures::city();
    Livewire::test(Checkout::class)->call('selectCity', $city->id)
        ->assertNotDispatched('city-selected')
        ->assertSet('form.city_id', $city->id);

    expect(Livewire::test(Checkout::class)->effects['listeners'] ?? [])->not->toContain('city-selected');
});

it('выбирает согласие по умолчанию и повторно сообщает об ошибках оформления', function (): void {
    $component = Livewire::test(Checkout::class)->assertSet('form.agreement', true);
    $component->call('placeOrder')->assertHasErrors()->assertDispatched('checkout-validation-failed');
    $component->call('placeOrder')->assertHasErrors()->assertDispatched('checkout-validation-failed');
});

it('направляет письмо администратору без Telegram', function (): void {
    Notification::fake();
    config(['telegram.manager_chat_id' => null]);
    Setting::query()->create(['admin_email' => 'admin@example.test']);
    $order = Order::query()->create([
        'status' => 'NEW', 'payment_status' => 'UNPAID', 'payment_method' => 'ONLINE',
        'delivery_method' => 'PICKUP', 'customer_name' => 'Иван', 'customer_phone' => '+79991234567',
        'customer_email' => 'buyer@example.test', 'delivery_address' => [],
        'delivery_price' => Money::fromMinor(0),
        'subtotal' => Money::fromMinor(100),
        'total' => Money::fromMinor(100),
    ]);

    app(OrderNotificationService::class)->queue($order, 'CREATED', 'created');

    Notification::assertSentOnDemand(
        OrderUpdateNotification::class,
        fn (OrderUpdateNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notification->audience === OrderNotificationAudienceEnum::MANAGER
            && ($notifiable->routes['mail'] ?? null) === 'admin@example.test'
            && in_array('mail', $channels, true),
    );
});

it('автоматически обновляет изменившийся итог без кнопки перерасчёта', function (): void {
    $city = CheckoutFixtures::city();
    $variant = CheckoutFixtures::variant();
    app(CartService::class)->add($variant->id, 1);
    $component = CheckoutFixtures::form($city);
    $variant->update(['price' => Money::fromRubles(1200)]);

    $component->call('placeOrder')->assertHasErrors(['quoted_total_minor'])
        ->assertSet('quoted_total_minor', 120000);
    expect(Order::query()->count())->toBe(0);
});
