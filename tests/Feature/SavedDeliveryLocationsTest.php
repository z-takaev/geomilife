<?php

declare(strict_types=1);

use App\Enums\PaymentMethodEnum;
use App\Livewire\Checkout;
use App\Livewire\UserProfile;
use App\Models\Address;
use App\Models\SavedDeliveryPoint;
use App\Services\Cart\CartService;
use App\Services\Customers\SavedDeliveryLocationService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

beforeEach(function (): void {
    Notification::fake();
    Queue::fake();
    CheckoutFixtures::fakeBank();
    $this->city = CheckoutFixtures::city();
    $this->user = CheckoutFixtures::user(['city_id' => $this->city->getKey()]);
    $this->actingAs($this->user);
});

it('после заказа автоматически сохраняет новый адрес и делает активным', function (): void {
    $old = $this->user->addresses()->create(CheckoutFixtures::address($this->city, ['house' => '1', 'is_default' => true]));
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    CheckoutFixtures::form($this->city, ['delivery_method' => 'COURIER', 'payment_method' => PaymentMethodEnum::ONLINE->value, 'street' => 'Мира', 'house' => '10'])
        ->call('placeOrder')->assertHasNoErrors();
    $address = $this->user->addresses()->where('house', '10')->sole();
    expect($address->is_default)->toBeTrue()->and($address->last_used_at)->not->toBeNull()->and($old->fresh()->is_default)->toBeFalse();
});

it('не сохраняет адрес из незавершённой или невалидной формы', function (): void {
    Storage::fake('public');
    CheckoutFixtures::privacyPolicy();
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    $component = CheckoutFixtures::form($this->city, ['delivery_method' => 'COURIER', 'payment_method' => PaymentMethodEnum::ONLINE->value, 'street' => 'Мира', 'house' => '10']);
    $this->assertDatabaseCount('addresses', 0);
    $component->set('agreement', false)->call('placeOrder')->assertHasErrors(['agreement']);
    $this->assertDatabaseCount('addresses', 0);
});

it('подставляет активный адрес и позволяет выбрать другой не меняя активность до заказа', function (): void {
    $active = $this->user->addresses()->create(CheckoutFixtures::address($this->city, ['house' => '1', 'is_default' => true]));
    $other = $this->user->addresses()->create(CheckoutFixtures::address($this->city, ['house' => '2']));
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    Livewire::test(Checkout::class)->set('delivery_method', 'COURIER')->assertSet('address_id', $active->getKey())
        ->call('selectAddress', $other->getKey())->assertSet('house', '2');
    expect($active->fresh()->is_default)->toBeTrue()->and($other->fresh()->is_default)->toBeFalse();
});

it('при выборе адреса другого города меняет город формы без изменения профиля', function (): void {
    $otherCity = CheckoutFixtures::city(['name' => 'Другой город']);
    $address = $this->user->addresses()->create(CheckoutFixtures::address($otherCity));
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    CheckoutFixtures::form($this->city)->call('selectAddress', $address->getKey())->assertSet('city_id', $otherCity->getKey());
    expect($this->user->fresh()->city_id)->toBe($this->city->getKey());
});

it('не разрешает выбрать чужой сохранённый адрес на оформлении', function (): void {
    $other = CheckoutFixtures::user();
    $address = $other->addresses()->create(CheckoutFixtures::address($this->city));
    app(CartService::class)->add(CheckoutFixtures::variant()->getKey(), 1);
    CheckoutFixtures::form($this->city)->call('selectAddress', $address->getKey())->assertNotFound();
});

it('повторное использование нормализованного адреса не создаёт дубль и обновляет время', function (): void {
    $service = app(SavedDeliveryLocationService::class);
    $address = $service->rememberAddress($this->user, CheckoutFixtures::address($this->city));
    $address->update(['last_used_at' => now()->subDays(5)]);
    $reused = $service->rememberAddress($this->user, CheckoutFixtures::address($this->city, ['street' => ' Мира ', 'house' => ' 10 ']));
    expect($reused->getKey())->toBe($address->getKey())
        ->and($reused->is_default)->toBeTrue()->and($reused->last_used_at->isToday())->toBeTrue();
    $this->assertDatabaseCount('addresses', 1);
});

it('одиннадцатый адрес вытесняет давно использованный а не созданный первым', function (): void {
    $addresses = collect(range(1, 10))->map(fn (int $number): Address => $this->user->addresses()->create(
        CheckoutFixtures::address($this->city, ['house' => (string) $number, 'last_used_at' => now()->subDays($number), 'is_default' => $number === 1]),
    ));
    $service = app(SavedDeliveryLocationService::class);
    $new = $service->rememberAddress($this->user, CheckoutFixtures::address($this->city, ['house' => '11']));
    expect($this->user->addresses()->count())->toBe(10)->and($new->is_default)->toBeTrue()
        ->and($addresses->first()->fresh())->not->toBeNull()
        ->and($addresses->last()->fresh())->toBeNull()
        ->and($this->user->addresses()->where('is_default', true)->count())->toBe(1);
});

it('изменённый адрес сохраняет отдельно не меняя прежнюю запись', function (): void {
    $service = app(SavedDeliveryLocationService::class);
    $old = $service->rememberAddress($this->user, CheckoutFixtures::address($this->city));
    $new = $service->rememberAddress($this->user, CheckoutFixtures::address($this->city, ['house' => '20']));
    expect($old->fresh()->house)->toBe('10')->and($old->fresh()->is_default)->toBeFalse()
        ->and($new->house)->toBe('20')->and($new->is_default)->toBeTrue();
    $this->assertDatabaseCount('addresses', 2);
});

it('лимиты адресов и пвз независимы и повтор пвз не вытесняет другие записи', function (): void {
    $service = app(SavedDeliveryLocationService::class);
    foreach (range(1, 10) as $number) {
        $service->rememberAddress($this->user, CheckoutFixtures::address($this->city, ['house' => (string) $number]));
        $service->rememberDeliveryPoint($this->user, $this->city, CheckoutFixtures::point($this->city, 'VLZ'.$number));
    }
    $service->rememberDeliveryPoint($this->user, $this->city, CheckoutFixtures::point($this->city, 'VLZ1'));
    expect($this->user->addresses()->count())->toBe(10)
        ->and(SavedDeliveryPoint::query()->where('user_id', $this->user->getKey())->count())->toBe(10)
        ->and(SavedDeliveryPoint::query()->where('user_id', $this->user->getKey())->where('code', 'VLZ1')->sole()->is_default)->toBeTrue()
        ->and($this->user->addresses()->where('house', '10')->sole()->is_default)->toBeTrue();
});

it('новый пвз вытесняет давно использованный пункт и сохраняет активный адрес курьера', function (): void {
    $service = app(SavedDeliveryLocationService::class);
    $address = $service->rememberAddress($this->user, CheckoutFixtures::address($this->city));
    foreach (range(1, 10) as $number) {
        $point = $service->rememberDeliveryPoint($this->user, $this->city, CheckoutFixtures::point($this->city, 'VLZ'.$number));
        $point->update(['last_used_at' => now()->subDays($number)]);
    }
    $new = $service->rememberDeliveryPoint($this->user, $this->city, CheckoutFixtures::point($this->city, 'VLZ11'));
    expect(SavedDeliveryPoint::query()->where('user_id', $this->user->getKey())->count())->toBe(10)
        ->and(SavedDeliveryPoint::query()->where('user_id', $this->user->getKey())->where('code', 'VLZ10')->exists())->toBeFalse()
        ->and($new->is_default)->toBeTrue()->and($address->fresh()->is_default)->toBeTrue();
});

it('в профиле выбирает и удаляет пвз а после удаления активного использует последний посещённый', function (): void {
    $service = app(SavedDeliveryLocationService::class);
    $first = $service->rememberDeliveryPoint($this->user, $this->city, CheckoutFixtures::point($this->city, 'VLZ1'));
    $first->update(['last_used_at' => now()->subDays(3)]);
    $recent = $service->rememberDeliveryPoint($this->user, $this->city, CheckoutFixtures::point($this->city, 'VLZ2'));
    $recent->update(['last_used_at' => now()->subDay()]);
    $active = $service->rememberDeliveryPoint($this->user, $this->city, CheckoutFixtures::point($this->city, 'VLZ3'));
    Livewire::test(UserProfile::class)->call('setDefaultDeliveryPoint', $first->getKey());
    expect($first->fresh()->is_default)->toBeTrue()->and($active->fresh()->is_default)->toBeFalse();
    Livewire::test(UserProfile::class)->call('deleteDeliveryPoint', $first->getKey());
    expect($first->fresh())->toBeNull()->and($active->fresh()->is_default)->toBeTrue();
});

it('не позволяет управлять чужим сохранённым пунктом', function (string $method): void {
    $owner = CheckoutFixtures::user();
    $point = app(SavedDeliveryLocationService::class)->rememberDeliveryPoint($owner, $this->city, CheckoutFixtures::point($this->city));
    Livewire::test(UserProfile::class)->call($method, $point->getKey())->assertNotFound();
    expect($point->fresh())->not->toBeNull()->and($point->fresh()->is_default)->toBeTrue();
})->with(['setDefaultDeliveryPoint', 'deleteDeliveryPoint']);
