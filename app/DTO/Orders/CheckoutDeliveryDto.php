<?php

declare(strict_types=1);

namespace App\DTO\Orders;

use App\DTO\Cdek\CdekDeliveryPointResponseDto;
use App\Support\Values\Money;

final readonly class CheckoutDeliveryDto
{
    public function __construct(
        public Money $price,
        public array $details = [],
        public array $address = [],
        public array $packages = [],
        public ?CdekDeliveryPointResponseDto $point = null,
    ) {}
}
