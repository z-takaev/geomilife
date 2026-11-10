<?php

declare(strict_types=1);

use App\Enums\FilamentUserRoleEnum;
use App\Filament\Pages\ManageSettings;
use App\Models\FilamentUser;
use App\Models\Setting;
use Filament\Facades\Filament;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

it('не подключает снег без настроек или при выключенном переключателе', function (bool $hasSettings): void {
    if ($hasSettings) {
        $setting = Setting::query()->create();
        expect($setting->fresh()->snow_enabled)->toBeFalse();
    }

    $this->get(route('home'))->assertOk()
        ->assertDontSee('snow.min.css')->assertDontSee('snow.min.js')->assertDontSee('new Snow(');
})->with([false, true]);

it('подключает локальные файлы снега на публичных страницах только при включенной настройке', function (): void {
    $setting = Setting::query()->create(['snow_enabled' => true]);

    foreach (['home', 'contacts'] as $route) {
        $this->get(route($route))->assertOk()
            ->assertSee(asset('assets/css/snow.min.css'), false)
            ->assertSee(asset('assets/js/snow.min.js'), false)
            ->assertSee('new Snow()', false);
    }

    $this->get('/admin/login')->assertOk()
        ->assertDontSee('snow.min.css')->assertDontSee('snow.min.js')->assertDontSee('new Snow(');

    $setting->update(['snow_enabled' => false]);
    $this->get(route('home'))->assertOk()
        ->assertDontSee('snow.min.css')->assertDontSee('snow.min.js')->assertDontSee('new Snow(');
});

it('сохраняет переключатель снега в настройках Filament и обновляет кеш', function (): void {
    Storage::fake('public');
    CheckoutFixtures::privacyPolicy();
    $admin = FilamentUser::query()->create([
        'name' => 'Администратор',
        'email' => 'snow@example.com',
        'password' => 'password',
        'role' => FilamentUserRoleEnum::ADMIN,
    ]);
    $this->actingAs($admin, 'filament');
    Filament::setCurrentPanel('admin');

    expect(Setting::cached()->snow_enabled)->toBeFalse();

    $component = Livewire::test(ManageSettings::class)
        ->assertFormFieldExists('snow_enabled', fn ($field): bool => $field instanceof Toggle)
        ->fillForm([
            'phone' => '+7 (988) 375-75-55',
            'email' => 'store@example.com',
            'admin_email' => 'admin@example.com',
            'address' => 'Мира, 10',
            'working_hours' => 'Пн–Пт',
            'snow_enabled' => true,
        ])
        ->call('save')->assertHasNoFormErrors();

    expect(Setting::query()->sole()->snow_enabled)->toBeTrue()
        ->and(Setting::cached()->snow_enabled)->toBeTrue();

    $component->fillForm(['snow_enabled' => false])->call('save')->assertHasNoFormErrors();

    expect(Setting::query()->sole()->snow_enabled)->toBeFalse()
        ->and(Setting::cached()->snow_enabled)->toBeFalse();
});
