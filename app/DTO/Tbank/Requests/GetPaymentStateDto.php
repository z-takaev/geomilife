<?php

declare(strict_types=1);

namespace App\DTO\Tbank\Requests;

use InvalidArgumentException;

final readonly class GetPaymentStateDto
{
    public function __construct(public string $paymentId)
    {
        if (preg_match('/^[0-9]{1,20}$/D', $paymentId) !== 1) {
            throw new InvalidArgumentException('Укажите корректный идентификатор платежа Т-Банка.');
        }
    }

    public function toArray(): array
    {
        return ['PaymentId' => $this->paymentId];
    }
}
