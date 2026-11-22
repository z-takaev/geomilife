<?php

declare(strict_types=1);

namespace App\DTO\Orders;

use App\DTO\Cdek\CdekDeliveryPointResponseDto;
use App\Support\Values\Money;

final readonly class CheckoutDeliveryDto
{
    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, mixed>  $address
     * @param  list<PackedPackageDto>  $packages
     */
    public function __construct(
        public Money $price,
        public array $details = [],
        public array $address = [],
        public array $packages = [],
        public ?CdekDeliveryPointResponseDto $point = null,
    ) {}
}
