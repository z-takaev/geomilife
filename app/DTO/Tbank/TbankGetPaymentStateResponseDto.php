<?php

declare(strict_types=1);

namespace App\DTO\Tbank;

use App\Enums\Tbank\TbankPaymentStatusEnum;
use App\Support\Values\Money;

final readonly class TbankGetPaymentStateResponseDto
{
    public function __construct(
        public string $paymentId,
        public string $orderId,
        public Money $amount,
        public TbankPaymentStatusEnum $status,
    ) {}
}
