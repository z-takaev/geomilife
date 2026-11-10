<?php

declare(strict_types=1);

namespace App\DTO\Tbank;

final readonly class TbankCheckOrderResponseDto
{
    /** @param list<TbankOrderPaymentDto> $payments */
    public function __construct(
        public string $orderId,
        public array $payments,
    ) {}
}
