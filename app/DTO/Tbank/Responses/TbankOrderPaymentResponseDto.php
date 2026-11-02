<?php

declare(strict_types=1);

namespace App\DTO\Tbank\Responses;

use App\Enums\Tbank\TbankPaymentStatusEnum;
use App\Support\Values\Money;

final readonly class TbankOrderPaymentResponseDto
{
    public function __construct(
        public string $paymentId,
        public Money $amount,
        public TbankPaymentStatusEnum $status,
    ) {}
}
