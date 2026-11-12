<?php

declare(strict_types=1);

namespace App\Support\Email;

use Illuminate\Support\Str;

final class EmailNormalizer
{
    public static function normalize(?string $email): ?string
    {
        return $email === null ? null : Str::lower(trim($email));
    }
}
