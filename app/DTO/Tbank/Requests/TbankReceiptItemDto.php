<?php

declare(strict_types=1);

namespace App\DTO\Tbank\Requests;

use App\Support\Values\Money;
use InvalidArgumentException;

final readonly class TbankReceiptItemDto
{
    public function __construct(
        public string $name,
        public Money $price,
        public int $quantity,
        public string $tax,
        public ?string $paymentMethod = null,
        public ?string $paymentObject = null,
    ) {
        if (trim($name) === '' || mb_strlen($name) > 128 || $quantity < 1 || $quantity > 99999) {
            throw new InvalidArgumentException('Укажите название позиции чека до 128 символов и количество от 1 до 99999.');
        }

        if (! in_array($tax, ['none', 'vat0', 'vat5', 'vat7', 'vat10', 'vat22', 'vat105', 'vat107', 'vat110', 'vat122'], true)) {
            throw new InvalidArgumentException('Некорректная ставка НДС позиции чека.');
        }

        if ($paymentMethod !== null && ! in_array($paymentMethod, ['full_prepayment', 'prepayment', 'advance', 'full_payment', 'partial_payment', 'credit', 'credit_payment'], true)) {
            throw new InvalidArgumentException('Некорректный способ расчёта позиции чека.');
        }

        if ($paymentObject !== null && ! in_array($paymentObject, ['commodity', 'excise', 'job', 'service', 'gambling_bet', 'gambling_prize', 'lottery', 'lottery_prize', 'intellectual_activity', 'payment', 'agent_commission', 'composite', 'another'], true)) {
            throw new InvalidArgumentException('Некорректный предмет расчёта позиции чека.');
        }
    }

    public function total(): Money
    {
        return $this->price->multiply($this->quantity);
    }

    public function toArray(): array
    {
        return array_filter([
            'Name' => $this->name,
            'Price' => $this->price->minorAmount(),
            'Quantity' => $this->quantity,
            'Amount' => $this->total()->minorAmount(),
            'Tax' => $this->tax,
            'PaymentMethod' => $this->paymentMethod,
            'PaymentObject' => $this->paymentObject,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
