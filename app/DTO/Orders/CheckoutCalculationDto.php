<?php

declare(strict_types=1);

namespace App\DTO\Orders;

use App\DTO\Cdek\CdekDeliveryPointResponseDto;
use App\Models\City;
use App\Support\Values\Money;

final readonly class CheckoutCalculationDto
{
    /**
     * @param  list<CheckoutItemDto>  $items
     * @param  array<string, mixed>  $details
     * @param  array<string, mixed>  $address
     * @param  list<PackedPackageDto>  $packages
     */
    public function __construct(
        public array $items,
        public Money $subtotal,
        public Money $delivery,
        public Money $total,
        public ?City $city,
        public array $details,
        public array $address,
        public array $packages,
        public ?CdekDeliveryPointResponseDto $point,
        public string $fingerprint,
        public bool $isPreorder,
    ) {}
}
