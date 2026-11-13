<?php

declare(strict_types=1);

it('формирует robots txt с адресом сайта из конфигурации', function (): void {
    config(['app.url' => 'https://example.test/']);

    $response = $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    expect($response->getContent())->toBe(implode("\n", [
        'User-agent: *',
        'Disallow: /admin/',
        '',
        'Sitemap: https://example.test/sitemap.xml',
    ]));
});
