<?php

declare(strict_types=1);

namespace App\Actions\Cdek;

use App\Models\City;
use App\Services\Cdek\CdekService;
use App\Services\Cdek\Exceptions\CdekApiException;
use DateTimeInterface;

final class SyncCdekCitiesAction
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
            $now = now();
            $records = array_map(
                fn (array $city): array => $this->toRecord($city, $now),
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
     * @param  array<string, mixed>  $city
     * @return array<string, mixed>
     */
    private function toRecord(array $city, DateTimeInterface $now): array
    {
        $code = $city['code'] ?? null;
        $name = $city['city'] ?? null;
        $region = $city['region'] ?? null;
        $subRegion = $city['sub_region'] ?? null;

        if (
            ! is_int($code)
            || ! is_string($name)
            || ($region !== null && ! is_string($region))
        ) {
            throw CdekApiException::invalidResponse();
        }

        if (! is_string($subRegion)) {
            $subRegion = null;
        }

        return [
            'cdek_code' => $code,
            'name' => $name,
            'region' => $region,
            'sub_region' => $subRegion,
            'is_active' => true,
            'is_pickup_available' => false,
            'is_courier_available' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
