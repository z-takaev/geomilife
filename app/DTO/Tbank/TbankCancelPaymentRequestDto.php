<?php

declare(strict_types=1);

namespace App\DTO\Tbank;

use App\Services\Tbank\TbankIdentifierValidator;
use App\Support\Values\Money;
use InvalidArgumentException;

final readonly class TbankCancelPaymentRequestDto
{
    public function __construct(
        public string $paymentId,
        public Money $amount,
    ) {
        TbankIdentifierValidator::validatePaymentId($paymentId);

        if ($amount->minorAmount() < 1) {
            throw new InvalidArgumentException('Сумма отмены должна быть положительной.');
        }
    }

    /** @return array{PaymentId: string, Amount: int} */
    public function toArray(): array
    {
        return [
            'PaymentId' => $this->paymentId,
            'Amount' => $this->amount->minorAmount(),
        ];
    }
}
