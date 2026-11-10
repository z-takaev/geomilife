<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('формирует ссылки с учётом HTTPS туннеля только в локальной среде', function (string $environment, string $scheme): void {
    app()->detectEnvironment(static fn (): string => $environment);

    Route::get('/proxy-url-check', static fn (): array => [
        'stylesheet' => asset('assets/css/styles.css'),
        'webhook' => route('tbank.webhook'),
    ]);

    $this->withServerVariables([
        'REMOTE_ADDR' => '172.20.0.1',
    ])->withHeaders([
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Port' => '443',
    ])->get('http://shop.ngrok-free.app/proxy-url-check')
        ->assertOk()
        ->assertJsonPath('stylesheet', $scheme.'://shop.ngrok-free.app/assets/css/styles.css')
        ->assertJsonPath('webhook', $scheme.'://shop.ngrok-free.app/webhooks/tbank');
})->with([
    'локальный HTTPS туннель' => ['local', 'https'],
    'рабочее окружение без доверенного прокси' => ['production', 'http'],
]);

it('сохраняет HTTP ссылки при прямом локальном подключении', function (): void {
    app()->detectEnvironment(static fn (): string => 'local');

    Route::get('/proxy-url-check', static fn (): array => [
        'stylesheet' => asset('assets/css/styles.css'),
    ]);

    $this->get('http://localhost/proxy-url-check')
        ->assertOk()
        ->assertJsonPath('stylesheet', 'http://localhost/assets/css/styles.css');
});
