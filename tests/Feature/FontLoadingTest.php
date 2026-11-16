<?php

declare(strict_types=1);

it('предварительно загружает основной шрифт в формате woff2', function (): void {
    $this->get(route('home'))->assertOk()
        ->assertSee('rel="preload"', false)
        ->assertSee('Montserrat.woff2', false)
        ->assertSee('type="font/woff2"', false);

    expect(file_exists(public_path('assets/fonts/Montserrat.woff2')))->toBeTrue()
        ->and(file_exists(public_path('assets/fonts/Montserrat-Italic.woff2')))->toBeTrue();
});
