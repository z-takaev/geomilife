<?php

declare(strict_types=1);

namespace App\DTO\Orders;

use App\Support\Values\Money;
use Throwable;

final readonly class CheckoutPresentationDto
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $details
     * @param  list<PackedPackageDto>  $packages
     */
    public function __construct(
        public array $items,
        public Money $subtotal,
        public Money $delivery,
        public Money $total,
        public array $details,
        public bool $isPreorder,
        public array $packages,
        public bool $deliveryReady,
    ) {}

    public static function fromCalculation(CheckoutCalculationDto $calculation, bool $deliveryReady): self
    {
        return new self(
            items: array_map(static fn (CheckoutItemDto $item): array => $item->toArray(), $calculation->items),
            subtotal: $calculation->subtotal,
            delivery: $calculation->delivery,
            total: $calculation->total,
            details: $calculation->details,
            isPreorder: $calculation->isPreorder,
            packages: $calculation->packages,
            deliveryReady: $deliveryReady,
        );
    }

    public static function empty(): self
    {
        $zero = Money::fromMinor(0);

        return new self([], $zero, $zero, $zero, [], false, [], false);
    }

    public static function tryFromArray(mixed $data): ?self
    {
        if (! is_array($data)) {
            return null;
        }

        try {
            return new self(
                items: is_array($data['items'] ?? null) ? array_values($data['items']) : [],
                subtotal: Money::fromMinor((int) ($data['subtotal_minor'] ?? 0)),
                delivery: Money::fromMinor((int) ($data['delivery_minor'] ?? 0)),
                total: Money::fromMinor((int) ($data['total_minor'] ?? 0)),
                details: is_array($data['details'] ?? null) ? $data['details'] : [],
                isPreorder: (bool) ($data['is_preorder'] ?? false),
                packages: array_map(PackedPackageDto::fromArray(...), is_array($data['packages'] ?? null) ? $data['packages'] : []),
                deliveryReady: (bool) ($data['delivery_ready'] ?? false),
            );
        } catch (Throwable) {
            return null;
        }
    }

    public function withoutReadyDelivery(): self
    {
        return new self(
            $this->items,
            $this->subtotal,
            $this->delivery,
            $this->total,
            $this->details,
            $this->isPreorder,
            $this->packages,
            false,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'subtotal_minor' => $this->subtotal->minorAmount(),
            'delivery_minor' => $this->delivery->minorAmount(),
            'total_minor' => $this->total->minorAmount(),
            'details' => $this->details,
            'is_preorder' => $this->isPreorder,
            'packages' => array_map(static fn (PackedPackageDto $package): array => $package->toArray(), $this->packages),
            'delivery_ready' => $this->deliveryReady,
        ];
    }
}
