<?php

declare(strict_types=1);

namespace App\Support\Values\Casts;

use App\Support\Values\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class MoneyCast implements CastsAttributes, SerializesCastableAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): Money
    {
        if (! is_int($value) && ! is_string($value)) {
            throw new InvalidArgumentException("Атрибут {$key} должен содержать целое количество копеек.");
        }

        return Money::fromMinor((int) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        if ($value instanceof Money) {
            return $value->minorAmount();
        }

        if (! is_int($value) && ! is_string($value)) {
            throw new InvalidArgumentException("Атрибут {$key} должен быть объектом Money или суммой в рублях.");
        }

        return Money::fromRubles($value)->minorAmount();
    }

    public function serialize(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (! $value instanceof Money) {
            throw new InvalidArgumentException("Атрибут {$key} должен быть объектом Money.");
        }

        return $value->toRubles();
    }
}
