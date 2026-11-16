<?php

declare(strict_types=1);

use App\Actions\Cdek\SyncDeliveryCitiesAction;
use App\DTO\Cdek\CdekCityResponseDto;
use App\DTO\Cdek\CdekGetDeliveryPointsRequestDto;
use App\Models\Address;
use App\Models\City;
use App\Models\User;
use App\Services\Cdek\CdekResponseMapper;
use App\Services\Cdek\CdekService;
use App\Services\Cdek\Exceptions\CdekApiException;
use App\Support\Values\Money;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config(['services.cdek.base_url' => 'https://cdek.test', 'services.cdek.client_id' => 'test-client',
        'services.cdek.client_secret' => 'test-secret', 'services.cdek.connect_timeout' => 5, 'services.cdek.timeout' => 10]);
    Http::preventStrayRequests();
});

function cdekDeliveryPoint(int $cityCode, string $status = 'ACTIVE', bool $isHandout = true): array
{
    return [
        'type' => 'PVZ',
        'status' => $status,
        'is_handout' => $isHandout,
        'location' => ['city_code' => $cityCode],
    ];
}

it('получает OAuth токен и повторно использует его для запросов городов', function (): void {
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/location/cities*' => Http::response([['code' => 44, 'city' => 'Москва']]),
    ]);
    $service = app(CdekService::class);
    $cities = $service->getCities();
    expect($cities)->toHaveCount(1);
    expect($cities[0])->toBeInstanceOf(CdekCityResponseDto::class);
    expect($cities[0]->code)->toBe(44)
        ->and($cities[0]->name)->toBe('Москва')
        ->and($cities[0]->region)->toBeNull()
        ->and($cities[0]->subRegion)->toBeNull();
    $service->getCities(1, 25);
    Http::assertSentCount(3);
    Http::assertSent(function (Request $request): bool {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $request->method() === 'POST'
            && $query === ['grant_type' => 'client_credentials', 'client_id' => 'test-client', 'client_secret' => 'test-secret'];
    });
    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->hasHeader('Authorization', 'Bearer token')
        && $request['page'] === 1 && $request['size'] === 25 && $request['country_codes'] === 'RU' && $request['lang'] === 'RU');
});

it('обновляет токен после 401 и повторяет запрос только один раз', function (int $finalStatus): void {
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::sequence()->push(['access_token' => 'old', 'expires_in' => 3600])->push(['access_token' => 'new', 'expires_in' => 3600]),
        'cdek.test/v2/location/cities*' => Http::sequence()->push([], 401)->push([], $finalStatus),
    ]);
    if ($finalStatus === 200) {
        expect(app(CdekService::class)->getCities())->toBe([]);
    } else {
        expect(fn () => app(CdekService::class)->getCities())->toThrow(CdekApiException::class);
    }
    Http::assertSentCount(4);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET' && $request->hasHeader('Authorization', 'Bearer new'));
})->with([200, 401]);

it('не обращается к API с некорректной пагинацией', function (int $page, int $size): void {
    Http::fake();
    expect(fn () => app(CdekService::class)->getCities($page, $size))->toThrow(InvalidArgumentException::class);
    Http::assertNothingSent();
})->with([[-1, 1000], [0, 0]]);

it('отклоняет поврежденный OAuth ответ', function (array $response): void {
    Http::fake(['cdek.test/*' => Http::response($response)]);
    expect(fn () => app(CdekService::class)->getCities())->toThrow(CdekApiException::class);
    Http::assertSentCount(1);
})->with([[[]], [['access_token' => '', 'expires_in' => 3600]], [['access_token' => 'token', 'expires_in' => '3600']]]);

it('возвращает сведения об ошибке API', function (): void {
    $errors = [['code' => 'INVALID', 'message' => 'Некорректный запрос']];
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/location/cities*' => Http::response(['errors' => $errors], 400),
    ]);
    try {
        app(CdekService::class)->getCities();
        $this->fail('Ожидалась ошибка API');
    } catch (CdekApiException $exception) {
        expect($exception->statusCode)->toBe(400)->and($exception->errors)->toBe($errors)
            ->and($exception->getMessage())->toBe('Некорректный запрос');
    }
});

it('отклоняет ответ городов неожиданного формата', function (): void {
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/location/cities*' => Http::response(['unexpected' => true]),
    ]);
    expect(fn () => app(CdekService::class)->getCities())->toThrow(CdekApiException::class);
});

it('оборачивает ошибку соединения в исключение интеграции', function (): void {
    Http::fake(['cdek.test/*' => Http::failedConnection()]);
    expect(fn () => app(CdekService::class)->getCities())->toThrow(CdekApiException::class, 'Не удалось подключиться');
});

it('обновляет справочник без дублей и сохраняет настройки доставки', function (): void {
    $city = City::query()->create(['cdek_code' => 44, 'name' => 'Старое имя', 'is_active' => false,
        'is_pickup_available' => true, 'courier_delivery_price' => Money::fromRubles(500)]);
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/deliverypoints*' => Http::response([cdekDeliveryPoint(44), cdekDeliveryPoint(137)]),
        'cdek.test/v2/location/cities*' => Http::response([['code' => 44, 'city' => 'Москва', 'region' => 'Москва'], ['code' => 137, 'city' => 'Санкт-Петербург']]),
    ]);
    expect(app(SyncDeliveryCitiesAction::class)->run())->toBe(2);
    expect(app(SyncDeliveryCitiesAction::class)->run())->toBe(2);
    $this->assertDatabaseCount('cities', 2);
    expect($city->fresh()->name)->toBe('Москва')->and($city->fresh()->is_active)->toBeTrue()
        ->and($city->fresh()->is_pickup_available)->toBeTrue()
        ->and($city->fresh()->courier_delivery_price->toRubles())->toBe('500.00');
});

it('сбрасывает кеш городов после синхронизации справочника', function (): void {
    $city = City::query()->create([
        'cdek_code' => 44,
        'name' => 'Старое имя',
        'is_active' => true,
        'is_suggested' => true,
    ]);
    City::cached();
    City::cachedById($city->id);
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/deliverypoints*' => Http::response([cdekDeliveryPoint(44)]),
        'cdek.test/v2/location/cities*' => Http::response([['code' => 44, 'city' => 'Новое имя']]),
    ]);

    app(SyncDeliveryCitiesAction::class)->run();

    expect(Cache::has((string) config('cache.items.city_selector.key')))->toBeFalse()
        ->and(City::cachedById($city->id)?->name)->toBe('Новое имя');
});

it('загружает следующую страницу после полной страницы справочника', function (): void {
    $cities = array_map(fn (int $code): array => ['code' => $code, 'city' => 'Город '.$code], range(1, 1000));
    $deliveryPoints = array_map(fn (int $code): array => cdekDeliveryPoint($code), range(1, 1000));
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/deliverypoints*' => Http::sequence()->push($deliveryPoints)->push([cdekDeliveryPoint(1001)]),
        'cdek.test/v2/location/cities*' => Http::sequence()->push($cities)->push([['code' => 1001, 'city' => 'Последний']]),
    ]);
    expect(app(SyncDeliveryCitiesAction::class)->run())->toBe(1001);
    $this->assertDatabaseCount('cities', 1001);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET' && $request['page'] === 1);
});

it('не записывает частично страницу с поврежденным городом', function (): void {
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/deliverypoints*' => Http::response([cdekDeliveryPoint(44)]),
        'cdek.test/v2/location/cities*' => Http::response([['code' => 44, 'city' => 'Москва'], ['code' => 'invalid', 'city' => 'Ошибка']]),
    ]);
    expect(fn () => app(SyncDeliveryCitiesAction::class)->run())->toThrow(CdekApiException::class);
    $this->assertDatabaseCount('cities', 0);
});

it('получает коды городов только из активных пунктов выдачи', function (): void {
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/deliverypoints*' => Http::response([
            cdekDeliveryPoint(44),
            cdekDeliveryPoint(44),
            cdekDeliveryPoint(137, 'CLOSED'),
            cdekDeliveryPoint(146, isHandout: false),
        ]),
    ]);

    $page = app(CdekService::class)->getDeliveryPointCityCodes();

    expect($page->cityCodes)->toBe([44, 44])
        ->and($page->itemsCount)->toBe(4);
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/deliverypoints')
        && $request['country_code'] === 'RU'
        && $request['type'] === 'PVZ'
        && $request['is_handout'] === true
        && $request['page'] === 0
        && $request['size'] === 1000);
});

it('сохраняет только города с активными пунктами выдачи', function (): void {
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/deliverypoints*' => Http::response([cdekDeliveryPoint(44), cdekDeliveryPoint(44), cdekDeliveryPoint(137, 'CLOSED')]),
        'cdek.test/v2/location/cities*' => Http::response([
            ['code' => 44, 'city' => 'Москва'],
            ['code' => 137, 'city' => 'Санкт-Петербург'],
        ]),
    ]);

    expect(app(SyncDeliveryCitiesAction::class)->run())->toBe(1);

    $this->assertDatabaseHas('cities', ['cdek_code' => 44, 'is_active' => true]);
    $this->assertDatabaseMissing('cities', ['cdek_code' => 137]);
});

it('удаляет несвязанные города без ПВЗ и отключает связанные', function (): void {
    $actualCity = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => false]);
    $staleCity = City::query()->create(['cdek_code' => 137, 'name' => 'Санкт-Петербург', 'is_active' => true]);
    $referencedCity = City::query()->create(['cdek_code' => 146, 'name' => 'Тула', 'is_active' => true]);
    $userCity = City::query()->create(['cdek_code' => 147, 'name' => 'Калуга', 'is_active' => true]);
    $user = User::query()->create([
        'city_id' => $userCity->id,
        'first_name' => 'Иван',
        'email' => 'ivan@example.com',
        'password' => 'password',
    ]);
    Address::query()->create(['user_id' => $user->id, 'city_id' => $referencedCity->id]);
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/deliverypoints*' => Http::response([cdekDeliveryPoint(44)]),
        'cdek.test/v2/location/cities*' => Http::response([['code' => 44, 'city' => 'Москва']]),
    ]);

    app(SyncDeliveryCitiesAction::class)->run();

    expect($actualCity->fresh()->is_active)->toBeTrue()
        ->and($staleCity->fresh())->toBeNull()
        ->and($referencedCity->fresh()->is_active)->toBeFalse()
        ->and($userCity->fresh()->is_active)->toBeFalse();
});

it('не изменяет города если список пунктов выдачи пуст', function (): void {
    $city = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => true]);
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/deliverypoints*' => Http::response([]),
    ]);

    expect(fn () => app(SyncDeliveryCitiesAction::class)->run())->toThrow(CdekApiException::class);

    expect($city->fresh()->is_active)->toBeTrue();
});

it('не изменяет города если не удалось загрузить все страницы пунктов выдачи', function (): void {
    $city = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => true]);
    $deliveryPoints = array_map(fn (int $index): array => cdekDeliveryPoint($index), range(1, 1000));
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/deliverypoints*' => Http::sequence()
            ->push($deliveryPoints)
            ->push(['unexpected' => true]),
    ]);

    expect(fn () => app(SyncDeliveryCitiesAction::class)->run())->toThrow(CdekApiException::class);

    expect($city->fresh()->is_active)->toBeTrue();
});

it('всегда запрашивает только ПВЗ СДЭК', function (): void {
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/deliverypoints*' => Http::response([]),
    ]);

    app(CdekService::class)->getDeliveryPoints(new CdekGetDeliveryPointsRequestDto(cityCode: 44));

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/deliverypoints')
        && $request['city_code'] === 44 && $request['type'] === 'PVZ');
});

it('всегда формирует наклейки СДЭК в формате A4', function (): void {
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/print/barcodes' => Http::response(['entity' => ['uuid' => 'b778dabf-459b-4e37-85e5-402402e85f34']]),
    ]);
    $orderUuid = 'a778dabf-459b-4e37-85e5-402402e85f34';

    app(CdekService::class)->createBarcodes($orderUuid, copyCount: 2);

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/print/barcodes')
        && $request['format'] === 'A4' && $request['copy_count'] === 2
        && $request['orders'] === [['order_uuid' => $orderUuid]]);
});

it('отклоняет ответ СДЭК с типом пункта отличным от ПВЗ', function (string $type): void {
    $point = [
        'code' => 'MSK1', 'type' => $type, 'work_time' => '09:00–20:00',
        'location' => ['city_code' => 44, 'address_full' => 'Москва, ул. Примерная, 1',
            'latitude' => 55.75, 'longitude' => 37.61],
    ];

    expect(fn () => app(CdekResponseMapper::class)->deliveryPoints([$point]))
        ->toThrow(CdekApiException::class);
})->with(['POSTAMAT', 'ALL', 'UNKNOWN']);
