<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    config(['services.cdek.base_url' => 'https://cdek.test',
        'services.cdek.client_id' => 'client', 'services.cdek.client_secret' => 'secret']);
    URL::forceRootUrl('https://shop.test');
    URL::forceScheme('https');
    Http::preventStrayRequests();
    Http::fake(['cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600])]);
});

it('регистрирует подписку СДЭК на адрес маршрута и не создаёт повторную', function (): void {
    $webhook = ['uuid' => 'f359a4b2-b37f-4939-94af-b7315cf47d18',
        'type' => 'ORDER_STATUS', 'url' => 'https://shop.test/webhooks/cdek'];
    Http::fake(['cdek.test/v2/webhooks' => Http::sequence()
        ->push([])->push(['entity' => ['uuid' => $webhook['uuid']]])->push([$webhook])]);

    $this->artisan('cdek:webhook')->expectsOutputToContain('зарегистрирован')->assertSuccessful();
    $this->artisan('cdek:webhook')->expectsOutputToContain('уже зарегистрирован')->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://cdek.test/v2/webhooks'
        && $request->data() === ['type' => 'ORDER_STATUS', 'url' => $webhook['url']]);
    expect(Http::recorded(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://cdek.test/v2/webhooks'))->toHaveCount(1);
});

it('не считает подписку другого типа или адреса существующей регистрацией', function (string $type, string $url): void {
    Http::fake(['cdek.test/v2/webhooks' => Http::sequence()
        ->push([['uuid' => 'existing', 'type' => $type, 'url' => $url]])
        ->push(['entity' => ['uuid' => 'created']])]);

    $this->artisan('cdek:webhook')->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://cdek.test/v2/webhooks');
})->with([
    ['PRINT_FORM', 'https://shop.test/webhooks/cdek'],
    ['ORDER_STATUS', 'https://old-shop.test/webhooks/cdek'],
]);

it('не регистрирует вебхук без HTTPS', function (): void {
    URL::forceRootUrl('http://shop.test');
    URL::forceScheme('http');

    $this->artisan('cdek:webhook')->expectsOutputToContain('HTTPS')->assertFailed();

    Http::assertNothingSent();
});

it('сообщает об отсутствующих реквизитах СДЭК', function (): void {
    config(['services.cdek.client_secret' => '']);

    $this->artisan('cdek:webhook')->expectsOutputToContain('client_secret')->assertFailed();

    Http::assertNothingSent();
});

it('не создаёт подписку после ошибки получения списка', function (): void {
    Http::fake(['cdek.test/v2/webhooks' => Http::response(['errors' => [
        ['code' => 'ERROR', 'message' => 'Ошибка получения подписок'],
    ]], 500)]);

    $this->artisan('cdek:webhook')->expectsOutputToContain('Ошибка получения подписок')->assertFailed();

    Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://cdek.test/v2/webhooks');
});

it('не сообщает об успехе при ошибке регистрации в ответе API', function (): void {
    Http::fake(['cdek.test/v2/webhooks' => Http::sequence()->push([])->push(['errors' => [
        ['code' => 'INVALID', 'message' => 'Некорректный адрес'],
    ]])]);

    $this->artisan('cdek:webhook')->expectsOutputToContain('Не удалось зарегистрировать')->assertFailed();
});
