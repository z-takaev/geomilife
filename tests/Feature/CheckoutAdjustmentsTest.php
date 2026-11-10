<?php

declare(strict_types=1);

use App\Enums\FilamentUserRoleEnum;
use App\Enums\PaymentMethodEnum;
use App\Filament\Pages\ManageSettings;
use App\Livewire\Checkout;
use App\Livewire\ShoppingCart;
use App\Models\FilamentUser;
use App\Models\Setting;
use App\Models\ShippingBox;
use App\Services\Cart\CartService;
use App\Services\Customers\SavedDeliveryLocationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

it('возвращает ошибку при прямом переходе к оформлению пустой корзины', function (): void {
    $this->get(route('checkout'))->assertNotFound();
    Livewire::test(ShoppingCart::class)->assertDontSeeHtml('href="'.route('checkout').'"');
});

it('открывает оформление с товарами в корзине', function (): void {
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);
    $this->get(route('checkout'))->assertOk();
});

it('не запрашивает подсказки городов до трёх символов', function (): void {
    CheckoutFixtures::city();
    $component = Livewire::test(Checkout::class);
    DB::enableQueryLog();
    $component->set('city_search', 'Вл')->assertViewHas('cities', fn ($cities): bool => $cities->isEmpty());
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    expect(collect($queries)->contains(fn (array $query): bool => str_contains($query['query'], 'like')))->toBeFalse();
    $component->set('city_search', 'Вла')->assertViewHas('cities', fn ($cities): bool => $cities->count() === 1);
});

it('сохраняет введённого другого получателя при переключении и восстанавливает профиль', function (): void {
    $user = CheckoutFixtures::user();
    $this->actingAs($user);
    Livewire::test(Checkout::class)
        ->set('other_recipient', true)->assertSet('first_name', '')->assertSet('email', '')
        ->set('first_name', 'Анна')->set('email', 'anna@example.com')
        ->set('other_recipient', false)->assertSet('first_name', $user->first_name)->assertSet('email', $user->email)
        ->set('other_recipient', true)->assertSet('first_name', 'Анна')->assertSet('email', 'anna@example.com');
});

it('требует квартиру при доставке до двери и оставляет корпус необязательным', function (): void {
    $city = CheckoutFixtures::city();
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);
    CheckoutFixtures::form($city, ['delivery_method' => 'COURIER', 'payment_method' => PaymentMethodEnum::ONLINE->value, 'street' => 'Мира', 'house' => '1', 'delivery_to_door' => true, 'floor' => '1'])
        ->call('placeOrder')->assertHasErrors(['apartment'])->assertHasNoErrors(['building']);
});

it('объясняет причину недоступности оформления', function (): void {
    app(CartService::class)->add(CheckoutFixtures::variant()->id, 1);
    Livewire::test(Checkout::class)->assertDontSee('Выберите город для доставки.')->call('placeOrder')->assertSee('Выберите город для доставки.');
    $city = CheckoutFixtures::city(['is_pickup_available' => false, 'is_courier_available' => false]);
    Livewire::test(Checkout::class)->call('selectCity', $city->id)
        ->assertDontSee('В этом городе нет доступных способов доставки.')->call('placeOrder')
        ->assertSee('В этом городе нет доступных способов доставки.');
});

it('автоматически выбирает основной ПВЗ другого города и позволяет изменить выбор', function (): void {
    $city = CheckoutFixtures::city(['cdek_code' => 44, 'is_cdek_excluded' => false]);
    $user = CheckoutFixtures::user(['city_id' => CheckoutFixtures::city()->id]);
    $this->actingAs($user);
    $point = app(SavedDeliveryLocationService::class)->rememberDeliveryPoint($user, $city, CheckoutFixtures::point($city, 'MSK100'));
    CheckoutFixtures::fakeCdek($city, 35000);
    ShippingBox::query()->create(['name' => 'Коробка', 'length' => 100, 'width' => 100, 'height' => 100, 'weight' => 10, 'is_active' => true]);
    app(CartService::class)->add(CheckoutFixtures::variant(['weight' => 200, 'length' => 10, 'width' => 10, 'height' => 10])->id, 1);
    Livewire::test(Checkout::class)
        ->assertSet('city_id', $city->id)->assertSet('saved_delivery_point_id', $point->id)
        ->assertViewHas('selectedDeliveryPoint', fn ($point): bool => $point['code'] === 'MSK100')
        ->assertViewHas('deliveryReady', true)
        ->assertSet('delivery_method', 'CDEK')->assertSeeHtml('id="checkout-city"')
        ->call('clearDeliveryPoint')->assertSet('delivery_point_code', '')->assertSet('saved_delivery_point_id', null)
        ->assertSeeHtml('id="checkout-city"');
    expect($point->fresh())->not->toBeNull();
    expect($user->fresh()->city_id)->not->toBe($city->id);
});

it('показывает ссылку на загруженную политику при оформлении', function (): void {
    Storage::fake('public');
    $setting = CheckoutFixtures::privacyPolicy();

    Livewire::test(Checkout::class)->assertSeeHtml($setting->privacyPolicyUrl())
        ->assertSee('Соглашаюсь с')->assertSee('политикой обработки персональных данных');
});

it('в настройках требует PDF и заменяет документ без возможности удаления', function (): void {
    Storage::fake('public');
    CheckoutFixtures::privacyPolicy();
    $setting = Setting::query()->sole();
    $oldPath = $setting->privacy_policy;
    $pdf = Storage::disk('public')->get($oldPath);
    $admin = FilamentUser::query()->create([
        'name' => 'Администратор', 'email' => 'policy@example.com', 'password' => 'password',
        'role' => FilamentUserRoleEnum::ADMIN,
    ]);
    $this->actingAs($admin, 'filament');
    Filament\Facades\Filament::setCurrentPanel('admin');
    $component = Livewire::test(ManageSettings::class)
        ->fillForm([
            'phone' => '+7 (988) 375-75-55', 'email' => 'store@example.com', 'admin_email' => 'admin@example.com',
            'address' => 'Мира, 10', 'working_hours' => 'Пн–Пт 09:00–18:00', 'privacy_policy' => [],
        ])->call('save')->assertHasFormErrors(['privacy_policy' => 'required']);
    expect($setting->fresh()->privacy_policy)->toBe($oldPath);
    Storage::disk('public')->assertExists($oldPath);
    $component->fillForm(['privacy_policy' => [UploadedFile::fake()->createWithContent('updated-policy.pdf', $pdf)]])
        ->call('save')->assertHasNoFormErrors();
    expect($setting->fresh()->privacy_policy)->not->toBe($oldPath);
    expect(Storage::disk('public')->get($setting->fresh()->privacy_policy))->toBe($pdf);
    Storage::disk('public')->assertMissing($oldPath);
});
