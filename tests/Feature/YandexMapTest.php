<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Filament\Pages\ManageSettings;
use App\Models\Setting;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

require_once __DIR__.'/../Support/FulfillmentFixtures.php';

it('показывает карту по идентификатору из настроек', function (): void {
    $id = '386e39964882c544c6f7899214774d2ed29e17f97df68f795e37fa871ea1573f';
    Setting::query()->create(['yandex_map_id' => $id]);
    $this->get(route('contacts'))->assertOk()->assertSee('constructor%3A'.$id, false)
        ->assertSee('api-maps.yandex.ru/services/constructor/1.0/js/', false);
});

it('сохраняет только допустимый идентификатор карты', function (string $id, bool $valid): void {
    Storage::fake('public');
    CheckoutFixtures::privacyPolicy();
    Filament::setCurrentPanel('admin');
    $this->actingAs(fulfillmentEmployee(FilamentUserRoleEnum::ADMIN), 'filament');
    $component = Livewire::test(ManageSettings::class)->fillForm([
        'phone' => '+7 (988) 375-75-55', 'email' => 'store@example.com', 'admin_email' => 'admin@example.com',
        'address' => 'Мира, 10', 'working_hours' => 'Пн–Пт', 'yandex_map_id' => $id,
    ])->call('save');
    if ($valid) {
        $component->assertHasNoFormErrors();
        expect(Setting::query()->sole()->yandex_map_id)->toBe($id);
    } else {
        $component->assertHasFormErrors(['yandex_map_id']);
        expect(Setting::query()->sole()->yandex_map_id)->toBeNull();
    }
})->with([
    ['386e39964882c544c6f7899214774d2ed29e17f97df68f795e37fa871ea1573f', true],
    ['<script>alert(1)</script>', false], ['invalid-id', false],
]);

it('не подключает карту при пустом или подмененном идентификаторе', function (?string $id): void {
    Setting::query()->create(['yandex_map_id' => $id]);
    $this->get(route('contacts'))->assertOk()->assertDontSee('api-maps.yandex.ru/services/constructor/1.0/js/', false)
        ->assertDontSee('<script>alert(1)</script>', false);
})->with([null, '<script>alert(1)</script>']);
