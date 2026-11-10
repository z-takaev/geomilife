<?php

declare(strict_types=1);

namespace App\DTO\Cdek;

use InvalidArgumentException;

final readonly class CdekCalculateTariffRequestDto
{
    /**
     * @param  list<CdekPackageDto>  $packages  Грузовые места.
     */
    public function __construct(
        public CdekLocationDto $fromLocation,
        public CdekLocationDto $toLocation,
        public array $packages,
        public ?string $shipmentPoint = null,
        public ?string $deliveryPoint = null,
    ) {
        if (! array_is_list($packages) || $packages === []) {
            throw new InvalidArgumentException('Некорректный список packages.');
        }

        foreach ($packages as $item) {
            if (! $item instanceof CdekPackageDto) {
                throw new InvalidArgumentException('Поле packages должно содержать объекты CdekPackageDto.');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'from_location' => $this->fromLocation->toArray(),
            'to_location' => $this->toLocation->toArray(),
            'packages' => array_map(static fn (CdekPackageDto $item): array => $item->toArray(), $this->packages),
            'shipment_point' => $this->shipmentPoint,
            'delivery_point' => $this->deliveryPoint,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
