<?php

declare(strict_types=1);

namespace App\DTO\Cdek\Requests;

use App\Support\Traits\Makeable;
use InvalidArgumentException;

final readonly class CdekPaymentDto
{
    use Makeable;

    /**
     * @param  int|float  $value  Сумма в валюте получателя; для предоплаченного товара — 0.
     * @param  float|null  $vatSum  Сумма НДС.
     * @param  int|null  $vatRate  Ставка НДС в процентах; null — без НДС.
     */
    public function __construct(
        public int|float $value,
        public ?float $vatSum = null,
        public ?int $vatRate = null,
    ) {
        if ($value < 0 || ($vatRate !== null && $vatSum === null)) {
            throw new InvalidArgumentException('Сумма не может быть отрицательной; при ставке НДС требуется сумма НДС.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'value' => $this->value,
            'vat_sum' => $this->vatSum,
            'vat_rate' => $this->vatRate,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
