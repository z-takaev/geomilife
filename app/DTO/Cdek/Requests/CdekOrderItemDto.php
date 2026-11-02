<?php

declare(strict_types=1);

namespace App\DTO\Cdek\Requests;

use App\Support\Traits\Makeable;
use InvalidArgumentException;

final readonly class CdekOrderItemDto
{
    use Makeable;

    /**
     * @param  int  $weight  Вес единицы товара в граммах.
     * @param  int  $amount  Количество единиц товара, от 1 до 999.
     * @param  int|float  $cost  Объявленная стоимость единицы товара в валюте взаиморасчётов.
     */
    public function __construct(
        public string $name,
        public string $wareKey,
        public CdekPaymentDto $payment,
        public int $weight,
        public int $amount,
        public int|float $cost,
    ) {
        if ($weight < 1 || $amount < 1 || $amount > 999 || $cost < 0) {
            throw new InvalidArgumentException('Проверьте вес, количество и объявленную стоимость товара.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'ware_key' => $this->wareKey,
            'payment' => $this->payment->toArray(),
            'weight' => $this->weight,
            'amount' => $this->amount,
            'cost' => $this->cost,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
