<?php

declare(strict_types=1);

use App\Support\Phone\PhoneFormat;

it('определяет поддерживаемые форматы российского телефона', function (string $pattern, string $phone, bool $valid): void {
    expect((bool) preg_match($pattern, $phone))->toBe($valid);
})->with([
    [PhoneFormat::FORMATTED_PATTERN, '+7 (999) 123-45-67', true],
    [PhoneFormat::FORMATTED_PATTERN, '+79991234567', false],
    [PhoneFormat::COMPACT_PATTERN, '+79991234567', true],
    [PhoneFormat::COMPACT_PATTERN, '+7 (999) 123-45-67', false],
    [PhoneFormat::FORMATTED_OR_COMPACT_PATTERN, '+7 (999) 123-45-67', true],
    [PhoneFormat::FORMATTED_OR_COMPACT_PATTERN, '+79991234567', true],
    [PhoneFormat::FORMATTED_OR_COMPACT_PATTERN, '89991234567', false],
]);

it('хранит маску форматированного российского телефона', function (): void {
    expect(PhoneFormat::MASK)->toBe('+7 (999) 999-99-99');
});

it('нормализует поддерживаемые варианты телефона', function (string $phone): void {
    expect(PhoneFormat::normalize($phone))->toBe('+79991234567');
})->with(['+7 (999) 123-45-67', '+79991234567', '89991234567', '9991234567']);
