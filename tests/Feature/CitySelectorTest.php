<?php

declare(strict_types=1);

use App\Livewire\CitySelector;
use App\Models\City;
use App\Models\User;
use App\Services\Cities\CurrentCityService;
use Illuminate\Support\Facades\Cookie;
use Livewire\Livewire;

it('сохраняет выбранный город в аккаунте, сессии и cookie', function (): void {
    $user = User::query()->create(['first_name' => 'Иван', 'email' => 'buyer@example.com', 'password' => 'Password123']);
    $city = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => true]);
    Livewire::actingAs($user)->test(CitySelector::class)->call('select', $city->id);
    expect($user->fresh()->city_id)->toBe($city->id)
        ->and(session('city.id'))->toBe($city->id)
        ->and(Cookie::queued('city_id')->getValue())->toBe((string) $city->id)
        ->and(app(CurrentCityService::class)->current()->id)->toBe($city->id);
});

it('отклоняет выбор отключенного города без изменения сессии', function (): void {
    $city = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => false]);
    session()->put('city.id', 999);
    Livewire::test(CitySelector::class)->call('select', $city->id)->assertNotFound();
    expect(session('city.id'))->toBe(999);
});

it('берет город аккаунта раньше города сессии', function (): void {
    $accountCity = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => true]);
    $sessionCity = City::query()->create(['cdek_code' => 137, 'name' => 'Санкт-Петербург', 'is_active' => true]);
    $user = User::query()->create(['first_name' => 'Иван', 'email' => 'buyer@example.com', 'password' => 'Password123', 'city_id' => $accountCity->id]);
    $this->actingAs($user);
    session()->put('city.id', $sessionCity->id);
    expect(app(CurrentCityService::class)->current()->id)->toBe($accountCity->id);
});

it('не возвращает отключенный или удаленный город сессии', function (bool $deleted): void {
    $city = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => false]);
    session()->put('city.id', $city->id);
    if ($deleted) {
        $city->delete();
    }
    expect(app(CurrentCityService::class)->current())->toBeNull();
})->with([true, false]);

it('ограничивает подсказки и исключает отключенные города', function (): void {
    for ($code = 1; $code <= 7; $code++) {
        City::query()->create(['cdek_code' => $code, 'name' => 'Город '.$code, 'is_active' => true]);
    }
    City::query()->create(['cdek_code' => 8, 'name' => 'Город скрытый', 'is_active' => false]);
    Livewire::test(CitySelector::class)->set('query', ' Город ')
        ->assertViewHas('searchResults', fn ($cities): bool => $cities->count() === 5 && $cities->every->is_active);
});

it('ищет символы SQL-шаблонов буквально', function (string $query): void {
    City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => true]);
    Livewire::test(CitySelector::class)->set('query', $query)
        ->assertViewHas('searchResults', fn ($cities): bool => $cities->isEmpty());
})->with(['%', '_']);
