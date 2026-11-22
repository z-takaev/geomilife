<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\DTO\Cdek\CdekCalculateTariffRequestDto;
use App\DTO\Cdek\CdekDeliveryPointResponseDto;
use App\DTO\Cdek\CdekGetDeliveryPointsRequestDto;
use App\DTO\Cdek\CdekLocationDto;
use App\DTO\Cdek\CdekPackageDto;
use App\DTO\Orders\CheckoutDeliveryDto;
use App\DTO\Orders\CheckoutInputDto;
use App\DTO\Orders\PackedPackageDto;
use App\Enums\Orders\DeliveryMethodEnum;
use App\Models\City;
use App\Services\Cdek\CdekService;
use App\Support\Values\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

final class CheckoutDeliveryService
{
    public function __construct(
        private readonly OrderPackingService $packing,
        private readonly CdekService $cdek,
    ) {}

    public function availableMethods(?City $city): array
    {
        if ($city === null || ! $city->is_active) {
            return [];
        }

        $methods = [];

        if ($city->is_pickup_available) {
            $methods[DeliveryMethodEnum::PICKUP->value] = DeliveryMethodEnum::PICKUP->getLabel();
        }

        if ($city->is_courier_available) {
            $methods[DeliveryMethodEnum::COURIER->value] = DeliveryMethodEnum::COURIER->getLabel();
        }

        if (! $city->is_cdek_excluded && $city->cdek_code > 0) {
            $methods[DeliveryMethodEnum::CDEK->value] = DeliveryMethodEnum::CDEK->getLabel();
        }

        return $methods;
    }

    public function calculate(City $city, ?DeliveryMethodEnum $method, CheckoutInputDto $input, array $items): CheckoutDeliveryDto
    {
        if ($method === null || ! array_key_exists($method->value, $this->availableMethods($city))) {
            return new CheckoutDeliveryDto(Money::fromMinor(0));
        }

        return match ($method) {
            DeliveryMethodEnum::PICKUP => $this->pickup($city),
            DeliveryMethodEnum::COURIER => $this->courier($city, $input),
            DeliveryMethodEnum::CDEK => $this->cdek($city, $input, $items),
        };
    }

    public function deliveryPoints(City $city, array $packages = []): array
    {
        $cacheKey = 'checkout:cdek:points:'.$city->cdek_code.':PVZ';
        $points = Cache::get($cacheKey);

        if (! is_array($points) || array_any($points, static fn (mixed $point): bool => ! $point instanceof CdekDeliveryPointResponseDto)) {
            $points = $this->loadDeliveryPoints($city);
            Cache::put($cacheKey, $points, now()->addMinutes(5));
        }

        return array_values(array_filter(
            $points,
            fn (CdekDeliveryPointResponseDto $point): bool => $this->pointSupportsPackages($point, $city, $packages),
        ));
    }

    private function pickup(City $city): CheckoutDeliveryDto
    {
        return new CheckoutDeliveryDto(
            price: Money::fromMinor(0),
            address: [
                'address' => $city->pickup_address,
                'working_hours' => $city->pickup_working_hours,
                'phone' => $city->pickup_phone,
            ],
        );
    }

    private function courier(City $city, CheckoutInputDto $input): CheckoutDeliveryDto
    {
        $basePrice = $city->courier_delivery_price ?? Money::fromMinor(0);

        $doorPrice = $input->deliveryToDoor
            ? ($city->door_delivery_price ?? Money::fromMinor(0))
            : Money::fromMinor(0);

        $floor = $input->deliveryToDoor ? max(0, (int) $input->floor) : 0;

        $liftingPrice = ($city->floor_lifting_price ?? Money::fromMinor(0))->multiply(max(0, $floor - 1));

        $address = [];

        $address = [
            'street' => trim($input->street),
            'house' => trim($input->house),
            'building' => trim((string) $input->building),
            'entrance' => trim((string) $input->entrance),
            'floor' => trim((string) $input->floor),
            'apartment' => trim((string) $input->apartment),
            'intercom' => trim((string) $input->intercom),
        ];

        $address['address'] = trim($address['street'].' '.$address['house']);

        return new CheckoutDeliveryDto(
            price: $basePrice->add($doorPrice)->add($liftingPrice),
            details: [
                'base_minor' => $basePrice->minorAmount(),
                'door_minor' => $doorPrice->minorAmount(),
                'lifting_minor' => $liftingPrice->minorAmount(),
                'delivery_to_door' => $input->deliveryToDoor,
            ],
            address: $address,
        );
    }

    private function cdek(City $city, CheckoutInputDto $input, array $items): CheckoutDeliveryDto
    {
        $packages = $this->packing->pack($items);
        $pointCode = $input->deliveryPointCode;

        if ($pointCode === '') {
            return new CheckoutDeliveryDto(Money::fromMinor(0), packages: $packages);
        }

        $point = collect($this->deliveryPoints($city, $packages))
            ->first(static fn (CdekDeliveryPointResponseDto $point): bool => $point->code === $pointCode);

        if ($point === null) {
            throw ValidationException::withMessages([
                'delivery_point_code' => __('Выбранный пункт недоступен для этого заказа.'),
            ]);
        }

        $calculation = $this->cdek->calculateTariff(new CdekCalculateTariffRequestDto(
            fromLocation: new CdekLocationDto(code: (int) config('orders.cdek.city_code')),
            toLocation: new CdekLocationDto(code: $city->cdek_code),
            packages: array_map(static fn (PackedPackageDto $package): CdekPackageDto => new CdekPackageDto(
                weight: $package->weight,
                length: (int) ceil($package->length / 10),
                width: (int) ceil($package->width / 10),
                height: (int) ceil($package->height / 10),
            ), $packages),
            shipmentPoint: (string) config('orders.cdek.shipment_point'),
            deliveryPoint: $point->code,
        ), (int) config('orders.cdek.tariff_code'));

        $rawPrice = $calculation->totalSum;
        $roundedPrice = Money::fromMinor(intdiv($rawPrice->minorAmount() + 50, 100) * 100);

        return new CheckoutDeliveryDto(
            price: $roundedPrice,
            details: [
                'tariff_code' => (int) config('orders.cdek.tariff_code'),
                'original_delivery_minor' => $rawPrice->minorAmount(),
                'period_min' => $calculation->periodMin,
                'period_max' => $calculation->periodMax,
            ],
            address: [
                'code' => $point->code,
                'type' => $point->type,
                'address' => $point->address,
                'work_time' => $point->workTime,
            ],
            packages: $packages,
            point: $point,
        );
    }

    private function loadDeliveryPoints(City $city): array
    {
        $result = [];

        for ($page = 0; $page < 100; $page++) {
            $batch = $this->cdek->getDeliveryPoints(new CdekGetDeliveryPointsRequestDto(
                cityCode: $city->cdek_code,
                size: 500,
                page: $page,
            ));
            $result = array_merge($result, $batch);

            if (count($batch) < 500) {
                return $result;
            }
        }

        throw ValidationException::withMessages([
            'delivery_point_code' => __('Не удалось получить полный список пунктов. Повторите позже.'),
        ]);
    }

    private function pointSupportsPackages(CdekDeliveryPointResponseDto $point, City $city, array $packages): bool
    {
        if ($point->cityCode !== $city->cdek_code || $point->type !== 'PVZ') {
            return false;
        }

        foreach ($packages as $package) {
            if (($point->weightMin !== null && $package->weight < $point->weightMin * 1000)
                || ($point->weightMax !== null && $package->weight > $point->weightMax * 1000)) {
                return false;
            }

            if ($point->dimensions === []) {
                continue;
            }

            $packageSize = [
                (int) ceil($package->length / 10),
                (int) ceil($package->width / 10),
                (int) ceil($package->height / 10),
            ];
            sort($packageSize);
            $fits = false;

            foreach ($point->dimensions as $cell) {
                $cellSize = [$cell['width'], $cell['height'], $cell['depth']];
                sort($cellSize);

                if ($packageSize[0] <= $cellSize[0]
                    && $packageSize[1] <= $cellSize[1]
                    && $packageSize[2] <= $cellSize[2]) {
                    $fits = true;
                    break;
                }
            }

            if (! $fits) {
                return false;
            }
        }

        return true;
    }
}
