<?php

declare(strict_types=1);

namespace App\Actions\Cdek;

use App\DTO\Cdek\CdekCityResponseDto;
use App\Models\City;
use App\Services\Cdek\CdekService;
use App\Services\Cdek\Exceptions\CdekApiException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class SyncDeliveryCitiesAction
{
    private const int PAGE_SIZE = 1000;

    public function __construct(
        private readonly CdekService $cdekService,
    ) {}

    public function run(): int
    {
        try {
            $deliveryPointCityCodes = $this->deliveryPointCityCodes();

            if ($deliveryPointCityCodes === []) {
                throw CdekApiException::invalidResponse();
            }

            $records = $this->cityRecords($deliveryPointCityCodes);

            if ($records === []) {
                throw CdekApiException::invalidResponse();
            }

            DB::transaction(fn () => $this->synchronize($records));
        } finally {
            City::forgetCache();
        }

        return count($records);
    }

    private function deliveryPointCityCodes(): array
    {
        $cityCodes = [];
        $page = 0;

        do {
            $deliveryPoints = $this->cdekService->getDeliveryPointCityCodes($page, self::PAGE_SIZE);

            foreach ($deliveryPoints->cityCodes as $cityCode) {
                $cityCodes[$cityCode] = $cityCode;
            }

            $page++;
        } while ($deliveryPoints->itemsCount === self::PAGE_SIZE);

        return array_values($cityCodes);
    }

    private function cityRecords(array $deliveryPointCityCodes): array
    {
        $availableCityCodes = array_fill_keys($deliveryPointCityCodes, true);
        $records = [];
        $page = 0;

        do {
            $cities = $this->cdekService->getCities($page, self::PAGE_SIZE);

            foreach ($cities as $city) {
                if (isset($availableCityCodes[$city->code])) {
                    $records[$city->code] = $this->toRecord($city);
                }
            }

            $citiesCount = count($cities);
            $page++;
        } while ($citiesCount === self::PAGE_SIZE);

        return array_values($records);
    }

    private function synchronize(array $records): void
    {
        City::query()->upsert(
            $records,
            ['cdek_code'],
            ['name', 'region', 'sub_region', 'is_active', 'updated_at'],
        );

        $actualCityCodes = array_column($records, 'cdek_code');
        $staleCityCodes = City::query()
            ->whereNotIn('cdek_code', $actualCityCodes)
            ->pluck('cdek_code');

        $staleCityCodes->chunk(500)->each(static function ($cityCodes): void {
            $referencedCityCodes = City::query()
                ->whereIn('cdek_code', $cityCodes)
                ->where(static fn (Builder $query): Builder => $query
                    ->whereHas('users')
                    ->orWhereHas('addresses')
                    ->orWhereHas('orders')
                    ->orWhereHas('savedDeliveryPoints'))
                ->pluck('cdek_code');

            City::query()
                ->whereIn('cdek_code', $referencedCityCodes)
                ->update(['is_active' => false]);

            City::query()
                ->whereIn('cdek_code', $cityCodes->diff($referencedCityCodes))
                ->delete();
        });
    }

    // TODO: Вынести этот метод в Dto класс
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
