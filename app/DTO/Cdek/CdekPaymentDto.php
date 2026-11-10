<?php

declare(strict_types=1);

namespace App\DTO\Cdek;

use App\Support\Values\Money;
use InvalidArgumentException;

final readonly class CdekPaymentDto
{
    /**
     * @param  Money  $value  Сумма в валюте получателя; для предоплаченного товара — 0.
     * @param  Money|null  $vatSum  Сумма НДС.
     * @param  int|null  $vatRate  Ставка НДС в процентах; null — без НДС.
     */
    public function __construct(
        public Money $value,
        public ?Money $vatSum = null,
        public ?int $vatRate = null,
    ) {
        if ($vatRate !== null && $vatSum === null) {
            throw new InvalidArgumentException('При ставке НДС требуется сумма НДС.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'value' => (float) $this->value->toRubles(),
            'vat_sum' => $this->vatSum === null ? null : (float) $this->vatSum->toRubles(),
            'vat_rate' => $this->vatRate,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
