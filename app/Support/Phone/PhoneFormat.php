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
}
