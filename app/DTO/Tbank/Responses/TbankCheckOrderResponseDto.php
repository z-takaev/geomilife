<?php

declare(strict_types=1);

namespace App\DTO\Tbank\Responses;

final readonly class TbankCheckOrderResponseDto
{
    /** @param list<TbankOrderPaymentResponseDto> $payments */
    public function __construct(
        public string $orderId,
        public array $payments,
    ) {}
}
