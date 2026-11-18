<?php

declare(strict_types=1);

namespace App\Support\Email;

use Illuminate\Support\Str;

// TODO: Нужна ли нам нормализация email?
final class EmailNormalizer
{
    public static function normalize(?string $email): ?string
    {
        return $email === null ? null : Str::lower(trim($email));
    }
}
