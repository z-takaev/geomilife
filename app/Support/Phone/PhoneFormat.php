<?php

declare(strict_types=1);

namespace App\Support\Phone;

final class PhoneFormat
{
    private const string FORMATTED_BODY = '\\+7 \\(\\d{3}\\) \\d{3}-\\d{2}-\\d{2}';

    private const string COMPACT_BODY = '\\+7\\d{10}';

    public const string MASK = '+7 (999) 999-99-99';

    public const string FORMATTED_PATTERN = '/^'.self::FORMATTED_BODY.'$/';

    public const string COMPACT_PATTERN = '/^'.self::COMPACT_BODY.'$/';

    public const string FORMATTED_OR_COMPACT_PATTERN = '/^(?:'.self::FORMATTED_BODY.'|'.self::COMPACT_BODY.')$/';

    public static function normalize(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) === 10) {
            $digits = '7'.$digits;
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            $digits = '7'.substr($digits, 1);
        }

        return '+'.$digits;
    }

    public static function isValid(string $value): bool
    {
        $value = trim($value);

        if (preg_match('/\A[\d\s()+-]+\z/u', $value) !== 1) {
            return false;
        }

        if (str_contains($value, '+') && (! str_starts_with($value, '+7') || substr_count($value, '+') !== 1)) {
            return false;
        }

        return preg_match(self::COMPACT_PATTERN, self::normalize($value)) === 1;
    }

    public static function normalizeForValidation(?string $value): ?string
    {
        return $value !== null && self::isValid($value) ? self::normalize($value) : $value;
    }

    public static function format(string $value): string
    {
        $normalizedValue = self::normalize($value);

        if (! preg_match(self::COMPACT_PATTERN, $normalizedValue)) {
            return $value;
        }

        return sprintf(
            '+7 (%s) %s-%s-%s',
            substr($normalizedValue, 2, 3),
            substr($normalizedValue, 5, 3),
            substr($normalizedValue, 8, 2),
            substr($normalizedValue, 10, 2),
        );
    }
}
