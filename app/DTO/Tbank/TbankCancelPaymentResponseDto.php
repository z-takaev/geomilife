<?php

declare(strict_types=1);

namespace App\DTO\Tbank;

use App\Enums\Tbank\TbankPaymentStatusEnum;
use App\Support\Values\Money;

final readonly class TbankCancelPaymentResponseDto
{
    public function __construct(
        public string $paymentId,
        public TbankPaymentStatusEnum $status,
        public ?Money $originalAmount = null,
        public ?Money $newAmount = null,
    ) {}
}
