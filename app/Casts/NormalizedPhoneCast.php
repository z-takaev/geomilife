<?php

declare(strict_types=1);

namespace App\Casts;

use App\Support\Phone\PhoneFormat;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class NormalizedPhoneCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return PhoneFormat::isValid($value) ? PhoneFormat::normalize($value) : $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) || ! PhoneFormat::isValid($value)) {
            throw new InvalidArgumentException('Некорректный номер телефона.');
        }

        return PhoneFormat::normalize($value);
    }
}
