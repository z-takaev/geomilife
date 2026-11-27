<?php

declare(strict_types=1);

use App\Livewire\CitySelector;
use App\Livewire\RegistrationCitySelector;
use App\Models\City;
use App\Models\User;
use App\Services\Cities\CurrentCityService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('сохраняет выбранный город без запроса геолокации', function (): void {
    $city = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => true]);
    session()->put('city.id', $city->id);
    Http::fake();

    expect(app(CurrentCityService::class)->getOrDetect()?->id)->toBe($city->id);

    Http::assertNothingSent();
});

it('сохраняет определённый по IP город в сессии и cookie', function (): void {
    $city = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => true]);
    request()->server->set('REMOTE_ADDR', '8.8.8.8');
    Http::fake(['*' => Http::response(['success' => true, 'country_code' => 'RU', 'city' => 'Москва'])]);

    expect(app(CurrentCityService::class)->getOrDetect()?->id)->toBe($city->id)
        ->and(session('city.id'))->toBe($city->id)
        ->and(Cookie::queued('city_id')->getValue())->toBe((string) $city->id);

    Http::assertSentCount(1);
});

it('сохраняет выбранный город в сессии и cookie без изменения аккаунта', function (): void {
    $user = User::query()->create(['first_name' => 'Иван', 'email' => 'buyer@example.com', 'password' => 'Password123']);
    $city = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => true]);
    Livewire::actingAs($user)->test(CitySelector::class)->call('select', $city->id);
    expect($user->fresh()->city_id)->toBeNull()
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
    $citySearchLimit = (int) config('pagination.per_page.city_search');

    for ($code = 1; $code <= $citySearchLimit + 2; $code++) {
        City::query()->create(['cdek_code' => $code, 'name' => 'Город '.$code, 'is_active' => true]);
    }
    City::query()->create(['cdek_code' => $citySearchLimit + 3, 'name' => 'Город скрытый', 'is_active' => false]);
    Livewire::test(CitySelector::class)->set('query', ' Город ')
        ->assertViewHas('searchResults', fn ($cities): bool => $cities->count() === $citySearchLimit && $cities->every->is_active);
});

it('показывает регион в подсказках города', function (): void {
    City::query()->create([
        'cdek_code' => 44,
        'name' => 'Киров',
        'region' => 'Кировская область',
        'is_active' => true,
    ]);

    Livewire::test(CitySelector::class)
        ->set('query', 'Киров')
        ->assertSee('Кировская область');
});

it('ищет символы SQL-шаблонов буквально', function (string $query): void {
    City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => true]);
    Livewire::test(CitySelector::class)->set('query', $query)
        ->assertViewHas('searchResults', fn ($cities): bool => $cities->isEmpty());
})->with(['%', '_']);

it('загружает список популярных городов только при открытии окна', function (): void {
    $city = City::query()->create([
        'cdek_code' => 44,
        'name' => 'Москва',
        'is_active' => true,
        'is_suggested' => true,
    ]);

    Livewire::test(CitySelector::class)
        ->assertViewHas('featuredCities', fn ($cities): bool => $cities->isEmpty())
        ->assertDontSee($city->name)
        ->call('loadOptions')
        ->assertSee($city->name);
});

it('обновляет кеш списка городов после изменения города', function (): void {
    $city = City::query()->create([
        'cdek_code' => 44,
        'name' => 'Москва',
        'is_active' => true,
        'is_suggested' => true,
    ]);

    Livewire::test(CitySelector::class)->call('loadOptions')->assertSee($city->name);

    $city->update(['is_suggested' => false]);

    Livewire::test(CitySelector::class)->call('loadOptions')->assertDontSee($city->name);
});

it('бессрочно кеширует текущий город и сбрасывает кеш после его изменения', function (): void {
    $city = City::query()->create([
        'cdek_code' => 44,
        'name' => 'Москва',
        'is_active' => true,
    ]);
    session()->put('city.id', $city->id);

    expect((new CurrentCityService)->current()?->name)->toBe('Москва');

    DB::enableQueryLog();
    expect((new CurrentCityService)->current()?->name)->toBe('Москва');
    $cityQueries = collect(DB::getQueryLog())->filter(
        fn (array $query): bool => str_contains($query['query'], 'from `cities`'),
    );
    DB::disableQueryLog();

    expect($cityQueries)->toBeEmpty();

    $city->update(['name' => 'Москва новая']);

    expect((new CurrentCityService)->current()?->name)->toBe('Москва новая');
});

it('не ограничивает кеш списка городов сроком жизни', function (): void {
    City::query()->create([
        'cdek_code' => 44,
        'name' => 'Москва',
        'is_active' => true,
        'is_suggested' => true,
    ]);

    expect(City::cached())->toHaveCount(1);
    $this->travel(2)->hours();

    DB::enableQueryLog();
    expect(City::cached())->toHaveCount(1);
    $cityQueries = collect(DB::getQueryLog())->filter(
        fn (array $query): bool => str_contains($query['query'], 'from `cities`'),
    );
    DB::disableQueryLog();

    expect($cityQueries)->toBeEmpty();
});

it('хранит список городов в кеше без объектов моделей', function (): void {
    City::query()->create([
        'cdek_code' => 44,
        'name' => 'Москва',
        'is_active' => true,
        'is_suggested' => true,
    ]);

    expect(City::cached())->each->toBeInstanceOf(City::class);

    $cachedCities = Cache::get((string) config('cache.items.city_selector.key'));

    expect($cachedCities)->toBeArray()
        ->and($cachedCities[0])->toBeArray();
});

it('пересобирает старый кеш объектов городов', function (): void {
    $city = City::query()->create([
        'cdek_code' => 44,
        'name' => 'Москва',
        'is_active' => true,
        'is_suggested' => true,
    ]);
    $incompleteCity = unserialize('O:15:"App\\Models\\City":0:{}', ['allowed_classes' => false]);
    Cache::put(
        (string) config('cache.items.city_selector.key'),
        new Collection([$incompleteCity]),
        60,
    );

    expect(City::cached())->toHaveCount(1)
        ->and(City::cached()->first())->toBeInstanceOf(City::class)
        ->and(City::cached()->first()->id)->toBe($city->id);
});

it('не запрашивает город регистрации без выбранного идентификатора', function (): void {
    DB::enableQueryLog();

    Livewire::test(RegistrationCitySelector::class);

    $cityQueries = collect(DB::getQueryLog())->filter(
        fn (array $query): bool => str_contains($query['query'], 'from `cities`'),
    );
    DB::disableQueryLog();

    expect($cityQueries)->toBeEmpty();
});
