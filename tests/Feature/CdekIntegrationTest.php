<?php

declare(strict_types=1);

use App\Actions\Cdek\SyncCdekCitiesAction;
use App\Models\City;
use App\Services\Cdek\CdekService;
use App\Services\Cdek\Exceptions\CdekApiException;
use App\Support\Values\Money;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config(['services.cdek.base_url' => 'https://cdek.test', 'services.cdek.client_id' => 'test-client',
        'services.cdek.client_secret' => 'test-secret', 'services.cdek.connect_timeout' => 5, 'services.cdek.timeout' => 10]);
    Http::preventStrayRequests();
});

it('получает OAuth токен и повторно использует его для запросов городов', function (): void {
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/location/cities*' => Http::response([['code' => 44, 'city' => 'Москва']]),
    ]);
    $service = app(CdekService::class);
    expect($service->getCities())->toBe([['code' => 44, 'city' => 'Москва']]);
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
        'cdek.test/v2/location/cities*' => Http::response([['code' => 44, 'city' => 'Москва', 'region' => 'Москва'], ['code' => 137, 'city' => 'Санкт-Петербург']]),
    ]);
    expect(app(SyncCdekCitiesAction::class)->run())->toBe(2);
    expect(app(SyncCdekCitiesAction::class)->run())->toBe(2);
    $this->assertDatabaseCount('cities', 2);
    expect($city->fresh()->name)->toBe('Москва')->and($city->fresh()->is_active)->toBeFalse()
        ->and($city->fresh()->is_pickup_available)->toBeTrue()
        ->and($city->fresh()->courier_delivery_price->toRubles())->toBe('500.00');
});

it('загружает следующую страницу после полной страницы справочника', function (): void {
    $cities = array_map(fn (int $code): array => ['code' => $code, 'city' => 'Город '.$code], range(1, 1000));
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/location/cities*' => Http::sequence()->push($cities)->push([['code' => 1001, 'city' => 'Последний']]),
    ]);
    expect(app(SyncCdekCitiesAction::class)->run())->toBe(1001);
    $this->assertDatabaseCount('cities', 1001);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET' && $request['page'] === 1);
});

it('не записывает частично страницу с поврежденным городом', function (): void {
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/location/cities*' => Http::response([['code' => 44, 'city' => 'Москва'], ['code' => 'invalid', 'city' => 'Ошибка']]),
    ]);
    expect(fn () => app(SyncCdekCitiesAction::class)->run())->toThrow(CdekApiException::class);
    $this->assertDatabaseCount('cities', 0);
});
