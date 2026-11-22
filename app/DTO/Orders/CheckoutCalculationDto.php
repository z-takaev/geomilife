<?php

declare(strict_types=1);

namespace App\DTO\Orders;

use App\DTO\Cdek\CdekDeliveryPointResponseDto;
use App\Models\City;
use App\Support\Values\Money;

final readonly class CheckoutCalculationDto
{
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

    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'subtotal' => $this->subtotal,
            'delivery' => $this->delivery,
            'total' => $this->total,
            'city' => $this->city,
            'details' => $this->details,
            'address' => $this->address,
            'packages' => $this->packages,
            'point' => $this->point,
            'fingerprint' => $this->fingerprint,
            'is_preorder' => $this->isPreorder,
        ];
    }
}
