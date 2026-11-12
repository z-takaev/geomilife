<?php

declare(strict_types=1);

namespace App\Support\Email;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

final class NormalizedEmailCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return is_string($value) ? $value : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return EmailNormalizer::normalize(is_string($value) ? $value : null);
    }
}
