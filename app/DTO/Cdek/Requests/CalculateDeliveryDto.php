<?php

declare(strict_types=1);

namespace App\DTO\Cdek\Requests;

use App\Support\Traits\Makeable;
use InvalidArgumentException;

final readonly class CalculateDeliveryDto
{
    use Makeable;

    /**
     * @param  list<CdekPackageDto>  $packages  Грузовые места.
     * @param  list<CdekAdditionalServiceDto>|null  $services  Услуги для расчёта по конкретному тарифу.
     */
    public function __construct(
        public CdekLocationDto $fromLocation,
        public CdekLocationDto $toLocation,
        public array $packages,
        public ?string $shipmentPoint = null,
        public ?string $deliveryPoint = null,
        public ?array $services = null,
    ) {
        if (! array_is_list($packages) || $packages === []) {
            throw new InvalidArgumentException('Некорректный список packages.');
        }

        foreach ($packages as $item) {
            if (! $item instanceof CdekPackageDto) {
                throw new InvalidArgumentException('Поле packages должно содержать объекты CdekPackageDto.');
            }
        }
        if ($services !== null) {
            if (! array_is_list($services)) {
                throw new InvalidArgumentException('Некорректный список services.');
            }

            foreach ($services as $item) {
                if (! $item instanceof CdekAdditionalServiceDto) {
                    throw new InvalidArgumentException('Поле services должно содержать объекты CdekAdditionalServiceDto.');
                }
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
            'services' => $this->services === null ? null : array_map(static fn (CdekAdditionalServiceDto $item): array => $item->toArray(), $this->services),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
