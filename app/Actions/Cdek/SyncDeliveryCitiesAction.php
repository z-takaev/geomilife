<?php

declare(strict_types=1);

namespace App\Actions\Cdek;

use App\DTO\Cdek\CdekCityResponseDto;
use App\Models\City;
use App\Services\Cdek\CdekService;

final class SyncDeliveryCitiesAction
{
    private const int PAGE_SIZE = 1000;

    public function __construct(
        private readonly CdekService $cdekService,
    ) {}

    public function run(): int
    {
        $page = 0;
        $synchronizedCitiesCount = 0;

        do {
            $cities = $this->cdekService->getCities($page, self::PAGE_SIZE);

            $records = array_map(
                fn (CdekCityResponseDto $city): array => $this->toRecord($city),
                $cities,
            );

            if ($records !== []) {
                City::query()->upsert(
                    $records,
                    ['cdek_code'],
                    ['name', 'region', 'sub_region', 'updated_at'],
                );
            }

            $citiesCount = count($cities);
            $synchronizedCitiesCount += $citiesCount;
            $page++;
        } while ($citiesCount === self::PAGE_SIZE);

        return $synchronizedCitiesCount;
    }

    /**
     * @return array<string, mixed>
     */
    private function toRecord(CdekCityResponseDto $city): array
    {
        return [
            'cdek_code' => $city->code,
            'name' => $city->name,
            'region' => $city->region,
            'sub_region' => $city->subRegion,
            'is_active' => true,
            'is_suggested' => in_array($city->code, config('cities.suggested_cdek_codes'), true),
            'is_pickup_available' => false,
            'is_courier_available' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
