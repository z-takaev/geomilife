<?php

declare(strict_types=1);

namespace App\Support\Values;

use App\Support\Values\Casts\MoneyCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use InvalidArgumentException;
use OverflowException;

final readonly class Money implements Castable
{
    public const string CURRENCY = 'RUB';

    private const int MINOR_UNITS_PER_RUBLE = 100;

    private function __construct(private int $minorAmount)
    {
        if ($minorAmount < 0) {
            throw new InvalidArgumentException('Сумма не может быть отрицательной.');
        }
    }

    public static function fromMinor(int $minorAmount): self
    {
        return new self($minorAmount);
    }

    public static function fromRubles(int|string $rubles): self
    {
        $value = str_replace(',', '.', trim((string) $rubles));

        if (preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $value, $matches) !== 1) {
            throw new InvalidArgumentException('Некорректное значение денежной суммы.');
        }

        $wholeRubles = self::parseWholeRubles($matches[1]);
        $minorPart = (int) str_pad($matches[2] ?? '', 2, '0');

        if ($wholeRubles > intdiv(PHP_INT_MAX - $minorPart, self::MINOR_UNITS_PER_RUBLE)) {
            throw new OverflowException('Денежная сумма превышает допустимое значение.');
        }

        return new self($wholeRubles * self::MINOR_UNITS_PER_RUBLE + $minorPart);
    }

    public static function tryFromRubles(int|string $rubles): ?self
    {
        try {
            return self::fromRubles($rubles);
        } catch (InvalidArgumentException|OverflowException) {
            return null;
        }
    }

    public static function castUsing(array $arguments): string
    {
        return MoneyCast::class;
    }

    public function minorAmount(): int
    {
        return $this->minorAmount;
    }

    public function toRubles(): string
    {
        $wholeRubles = intdiv($this->minorAmount, self::MINOR_UNITS_PER_RUBLE);
        $minorPart = $this->minorAmount % self::MINOR_UNITS_PER_RUBLE;

        return sprintf('%d.%02d', $wholeRubles, $minorPart);
    }

    public function add(self $money): self
    {
        if ($this->minorAmount > PHP_INT_MAX - $money->minorAmount) {
            throw new OverflowException('Результат сложения денежных сумм превышает допустимое значение.');
        }

        return new self($this->minorAmount + $money->minorAmount);
    }

    public function multiply(int $quantity): self
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Множитель денежной суммы не может быть отрицательным.');
        }

        if ($quantity !== 0 && $this->minorAmount > intdiv(PHP_INT_MAX, $quantity)) {
            throw new OverflowException('Результат умножения денежной суммы превышает допустимое значение.');
        }

        return new self($this->minorAmount * $quantity);
    }

    public function discountedBy(int $percent): self
    {
        if ($percent < 0 || $percent > 100) {
            throw new InvalidArgumentException('Размер скидки должен быть от 0 до 100 процентов.');
        }

        $multiplier = 100 - $percent;
        $wholePart = intdiv($this->minorAmount, 100) * $multiplier;
        $remainder = $this->minorAmount % 100;
        $roundedRemainder = intdiv($remainder * $multiplier + 50, 100);

        return new self($wholePart + $roundedRemainder);
    }

    public function equals(self $money): bool
    {
        return $this->minorAmount === $money->minorAmount;
    }

    public function format(): string
    {
        $roundedRubles = intdiv($this->minorAmount, self::MINOR_UNITS_PER_RUBLE)
            + (int) ($this->minorAmount % self::MINOR_UNITS_PER_RUBLE >= 50);

        return preg_replace('/\B(?=(\d{3})+(?!\d))/', ' ', (string) $roundedRubles).' ₽';
    }

    private static function parseWholeRubles(string $rubles): int
    {
        $normalizedRubles = ltrim($rubles, '0');
        $normalizedRubles = $normalizedRubles === '' ? '0' : $normalizedRubles;
        $maxRubles = (string) intdiv(PHP_INT_MAX, self::MINOR_UNITS_PER_RUBLE);

        if (strlen($normalizedRubles) > strlen($maxRubles)
            || (strlen($normalizedRubles) === strlen($maxRubles) && strcmp($normalizedRubles, $maxRubles) > 0)) {
            throw new OverflowException('Денежная сумма превышает допустимое значение.');
        }

        return (int) $normalizedRubles;
    }
}
