<?php

declare(strict_types=1);

namespace App\DTO\Cdek\Requests;

use App\Support\Traits\Makeable;
use InvalidArgumentException;

final readonly class CreateOrderDto
{
    use Makeable;

    /**
     * @param  string  $number  Номер заказа магазина в рамках договора СДЭК.
     * @param  list<CdekOrderPackageDto>  $packages  От 1 до 255 грузовых мест.
     * @param  list<CdekAdditionalServiceDto>|null  $services  Дополнительные услуги.
     */
    public function __construct(
        public int $tariffCode,
        public string $number,
        public CdekContactDto $recipient,
        public array $packages,
        public ?string $shipmentPoint = null,
        public ?string $deliveryPoint = null,
        public ?CdekLocationDto $fromLocation = null,
        public ?CdekLocationDto $toLocation = null,
        public ?CdekContactDto $sender = null,
        public ?array $services = null,
        public ?string $comment = null,
    ) {
        if ($tariffCode < 1 || trim($number) === '') {
            throw new InvalidArgumentException('Код тарифа и номер заказа обязательны.');
        }

        if (($shipmentPoint === null) === ($fromLocation === null)) {
            throw new InvalidArgumentException('Укажите либо shipmentPoint, либо fromLocation.');
        }

        if (($deliveryPoint === null) === ($toLocation === null)) {
            throw new InvalidArgumentException('Укажите либо deliveryPoint, либо toLocation.');
        }

        foreach ([$fromLocation, $toLocation] as $location) {
            if ($location !== null && $location->address === null) {
                throw new InvalidArgumentException('Для курьерского отправления требуется полный адрес.');
            }
        }

        if (! array_is_list($packages) || $packages === [] || count($packages) > 255) {
            throw new InvalidArgumentException('Некорректный список packages.');
        }

        foreach ($packages as $item) {
            if (! $item instanceof CdekOrderPackageDto) {
                throw new InvalidArgumentException('Поле packages должно содержать объекты CdekOrderPackageDto.');
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
            'tariff_code' => $this->tariffCode,
            'number' => $this->number,
            'recipient' => $this->recipient->toArray(),
            'packages' => array_map(static fn (CdekOrderPackageDto $item): array => $item->toArray(), $this->packages),
            'shipment_point' => $this->shipmentPoint,
            'delivery_point' => $this->deliveryPoint,
            'from_location' => $this->fromLocation?->toArray(),
            'to_location' => $this->toLocation?->toArray(),
            'sender' => $this->sender?->toArray(),
            'services' => $this->services === null ? null : array_map(static fn (CdekAdditionalServiceDto $item): array => $item->toArray(), $this->services),
            'comment' => $this->comment,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
