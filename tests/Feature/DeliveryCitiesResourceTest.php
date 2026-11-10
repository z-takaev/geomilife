<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Filament\Resources\Cities\CityResource;
use App\Filament\Resources\Cities\Pages\ListCities;
use App\Jobs\Cdek\SyncDeliveryCitiesJob;
use App\Models\FilamentUser;
use App\Services\Cdek\Exceptions\CdekApiException;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function (): void {
    Http::preventStrayRequests();
    Filament::setCurrentPanel('admin');
    $this->actingAs(FilamentUser::query()->create([
        'name' => 'Администратор',
        'email' => 'admin@example.test',
        'password' => 'password',
        'role' => FilamentUserRoleEnum::ADMIN,
    ]), 'filament');
});

it('называет ресурс городами доставки в навигации и на странице списка', function (): void {
    expect(CityResource::getNavigationLabel())->toBe('Города доставки')
        ->and(CityResource::getPluralModelLabel())->toBe('Города доставки')
        ->and(CityResource::getModelLabel())->toBe('город доставки');

    Livewire::test(ListCities::class)->assertSee('Города доставки');
});

it('запускает синхронизацию СДЭК из заголовка списка через очередь', function (): void {
    Queue::fake();

    Livewire::test(ListCities::class)
        ->assertActionVisible('syncCdekCities')
        ->assertSee('Синхронизировать города СДЭК')
        ->callAction('syncCdekCities')
        ->assertNotified('Синхронизация городов СДЭК запущена');

    Queue::assertPushed(SyncDeliveryCitiesJob::class, 1);
    Http::assertNothingSent();
});

it('синхронизирует города существующим Action при выполнении задания', function (): void {
    config(['services.cdek.base_url' => 'https://cdek.test',
        'services.cdek.client_id' => 'test-client', 'services.cdek.client_secret' => 'test-secret']);
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/location/cities*' => Http::response([['code' => 44, 'city' => 'Москва']]),
    ]);

    app()->call([new SyncDeliveryCitiesJob, 'handle']);

    $this->assertDatabaseHas('cities', ['cdek_code' => 44, 'name' => 'Москва']);
});

it('передает ошибку синхронизации обработчику очереди', function (): void {
    config(['services.cdek.base_url' => 'https://cdek.test',
        'services.cdek.client_id' => 'test-client', 'services.cdek.client_secret' => 'test-secret']);
    Http::fake(['cdek.test/*' => Http::response([], 400)]);

    expect(fn () => app()->call([new SyncDeliveryCitiesJob, 'handle']))
        ->toThrow(CdekApiException::class);
});
