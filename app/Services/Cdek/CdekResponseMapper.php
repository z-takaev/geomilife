<?php

declare(strict_types=1);

namespace App\Services\Cdek;

use App\DTO\Cdek\Responses\CdekCalculationResponseDto;
use App\DTO\Cdek\Responses\CdekCityResponseDto;
use App\DTO\Cdek\Responses\CdekDeliveryPointResponseDto;
use App\DTO\Cdek\Responses\CdekOperationResponseDto;
use App\DTO\Cdek\Responses\CdekOrderResponseDto;
use App\DTO\Cdek\Responses\CdekPrintResponseDto;
use App\DTO\Cdek\Responses\CdekTariffListResponseDto;
use App\DTO\Cdek\Responses\CdekTariffResponseDto;
use App\DTO\Cdek\Responses\CdekWebhookResponseDto;
use App\Enums\Cdek\CdekDeliveryPointTypeEnum;
use App\Enums\Cdek\CdekPrintStateEnum;
use App\Enums\Cdek\CdekRequestStateEnum;
use App\Services\Cdek\Exceptions\CdekApiException;
use DateTimeImmutable;
use Exception;

final class CdekResponseMapper
{
    /**
     * @param  array<mixed>  $data
     * @return list<CdekCityResponseDto>
     */
    public function cities(array $data): array
    {
        return array_map(fn (array $city): CdekCityResponseDto => new CdekCityResponseDto(
            code: $this->integer($city, 'code'),
            name: $this->string($city, 'city'),
            region: ($city['region'] ?? null) === '' ? null : $this->optionalString($city, 'region'),
            subRegion: ($city['sub_region'] ?? null) === '' ? null : $this->optionalString($city, 'sub_region'),
        ), $this->records($data));
    }

    /**
     * @param  array<mixed>  $data
     * @return list<CdekDeliveryPointResponseDto>
     */
    public function deliveryPoints(array $data): array
    {
        return array_map(function (array $point): CdekDeliveryPointResponseDto {
            $location = $this->object($point, 'location');
            $type = CdekDeliveryPointTypeEnum::tryFrom($this->string($point, 'type'));

            if ($type === null || $type === CdekDeliveryPointTypeEnum::ALL) {
                throw CdekApiException::invalidResponse();
            }

            $dimensions = array_map(fn (array $dimension): array => [
                'width' => $this->integer($dimension, 'width'),
                'height' => $this->integer($dimension, 'height'),
                'depth' => $this->integer($dimension, 'depth'),
            ], $this->items($point, 'dimensions'));

            return new CdekDeliveryPointResponseDto(
                code: $this->string($point, 'code'),
                type: $type,
                cityCode: $this->integer($location, 'city_code'),
                address: $this->string($location, 'address_full'),
                latitude: $this->number($location, 'latitude'),
                longitude: $this->number($location, 'longitude'),
                workTime: $this->string($point, 'work_time'),
                weightMin: $this->optionalNumber($point, 'weight_min'),
                weightMax: $this->optionalNumber($point, 'weight_max'),
                dimensions: $dimensions,
            );
        }, $this->records($data));
    }

    /** @param array<mixed> $data */
    public function tariffs(array $data): CdekTariffListResponseDto
    {
        $this->ensureCalculationSucceeded($data);

        if (! array_key_exists('tariff_codes', $data)) {
            throw CdekApiException::invalidResponse();
        }

        $tariffs = array_map(fn (array $tariff): CdekTariffResponseDto => new CdekTariffResponseDto(
            code: $this->integer($tariff, 'tariff_code'),
            name: $this->string($tariff, 'tariff_name'),
            deliveryMode: $this->integer($tariff, 'delivery_mode'),
            deliverySum: $this->number($tariff, 'delivery_sum'),
            periodMin: $this->integer($tariff, 'period_min'),
            periodMax: $this->integer($tariff, 'period_max'),
        ), $this->items($data, 'tariff_codes'));

        return new CdekTariffListResponseDto($tariffs, $this->issues($data, 'warnings'));
    }

    /** @param array<mixed> $data */
    public function calculation(array $data): CdekCalculationResponseDto
    {
        $this->ensureCalculationSucceeded($data);

        return new CdekCalculationResponseDto(
            deliverySum: $this->number($data, 'delivery_sum'),
            totalSum: $this->number($data, 'total_sum'),
            currency: $this->string($data, 'currency'),
            periodMin: $this->integer($data, 'period_min'),
            periodMax: $this->integer($data, 'period_max'),
            weightCalc: $this->integer($data, 'weight_calc'),
            warnings: $this->issues($data, 'warnings'),
        );
    }

    /** @param array<mixed> $data */
    public function operation(array $data): CdekOperationResponseDto
    {
        $entity = $this->optionalObject($data, 'entity');
        $uuid = $entity === null ? null : $this->optionalString($entity, 'uuid');
        $requests = [];
        $latest = null;

        foreach ($this->items($data, 'requests') as $request) {
            $state = CdekRequestStateEnum::tryFrom($this->string($request, 'state'));

            if ($state === null) {
                throw CdekApiException::invalidResponse();
            }

            $parsed = [
                'uuid' => $this->optionalString($request, 'request_uuid'),
                'type' => $this->string($request, 'type'),
                'dateTime' => $this->date($request, 'date_time'),
                'state' => $state,
                'errors' => $this->issues($request, 'errors'),
                'warnings' => $this->issues($request, 'warnings'),
            ];
            $requests[] = $parsed;

            if ($latest === null
                || ($parsed['type'] === 'CREATE' && $latest['type'] !== 'CREATE')
                || (($parsed['type'] === 'CREATE') === ($latest['type'] === 'CREATE') && $parsed['dateTime'] >= $latest['dateTime'])) {
                $latest = $parsed;
            }
        }

        $errors = [...$this->issues($data, 'errors'), ...($latest['errors'] ?? [])];
        $warnings = [...$this->issues($data, 'warnings'), ...($latest['warnings'] ?? [])];

        if ($uuid === null && ($latest === null || $latest['state'] === CdekRequestStateEnum::SUCCESSFUL) && $errors === []) {
            throw CdekApiException::invalidResponse();
        }

        return new CdekOperationResponseDto($uuid, $latest['state'] ?? null, $errors, $warnings, $requests);
    }

    /** @param array<mixed> $data */
    public function order(array $data): CdekOrderResponseDto
    {
        $operation = $this->operation($data);
        $entity = $this->optionalObject($data, 'entity');
        $status = $entity === null ? null : $this->latestStatus($entity);
        $number = $entity['cdek_number'] ?? null;

        if ($number !== null && ! is_int($number) && ! is_string($number)) {
            throw CdekApiException::invalidResponse();
        }

        return new CdekOrderResponseDto(
            uuid: $operation->uuid,
            cdekNumber: $number === null ? null : (string) $number,
            statusCode: $status['code'] ?? null,
            statusName: $status['name'] ?? null,
            statusDateTime: $status['dateTime'] ?? null,
            operation: $operation,
        );
    }

    /** @param array<mixed> $data */
    public function printDocument(array $data): CdekPrintResponseDto
    {
        $operation = $this->operation($data);
        $entity = $this->optionalObject($data, 'entity');
        $status = $entity === null ? null : $this->latestStatus($entity);
        $state = $status === null ? null : CdekPrintStateEnum::tryFrom($status['code']);

        if ($status !== null && $state === null) {
            throw CdekApiException::invalidResponse();
        }

        $url = $entity === null ? null : $this->optionalString($entity, 'url');

        if ($state === CdekPrintStateEnum::READY && $url === null) {
            throw CdekApiException::invalidResponse();
        }

        return new CdekPrintResponseDto($operation->uuid, $state, $url, $status['dateTime'] ?? null, $operation);
    }

    /**
     * @param  array<mixed>  $data
     * @return list<CdekWebhookResponseDto>
     */
    public function webhooks(array $data): array
    {
        return array_map(fn (array $webhook): CdekWebhookResponseDto => new CdekWebhookResponseDto(
            uuid: $this->string($webhook, 'uuid'),
            type: $this->string($webhook, 'type'),
            url: $this->string($webhook, 'url'),
        ), $this->records($data));
    }

    /** @param array<mixed> $data */
    private function ensureCalculationSucceeded(array $data): void
    {
        $errors = $this->issues($data, 'errors');

        if ($errors !== []) {
            throw new CdekApiException($errors[0]['message'], errors: $errors, warnings: $this->issues($data, 'warnings'));
        }
    }

    /**
     * @param  array<mixed>  $data
     * @return array{code: string, name: ?string, dateTime: DateTimeImmutable}|null
     */
    private function latestStatus(array $data): ?array
    {
        $latest = null;

        foreach ($this->items($data, 'statuses') as $status) {
            $deleted = $status['deleted'] ?? false;

            if (! is_bool($deleted)) {
                throw CdekApiException::invalidResponse();
            }

            if ($deleted) {
                continue;
            }

            $date = $this->date($status, 'date_time');

            if ($latest === null || $date > $latest['dateTime']) {
                $latest = [
                    'code' => $this->string($status, 'code'),
                    'name' => $this->optionalString($status, 'name'),
                    'dateTime' => $date,
                ];
            }
        }

        return $latest;
    }

    /**
     * @param  array<mixed>  $data
     * @return list<array{code: string, message: string}>
     */
    private function issues(array $data, string $key): array
    {
        return array_map(fn (array $issue): array => [
            'code' => $this->string($issue, 'code'),
            'message' => $this->string($issue, 'message'),
        ], $this->items($data, $key));
    }

    /**
     * @param  array<mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function items(array $data, string $key): array
    {
        $value = $data[$key] ?? [];

        if (! is_array($value)) {
            throw CdekApiException::invalidResponse();
        }

        return $this->records($value);
    }

    /**
     * @param  array<mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function records(array $data): array
    {
        if (! array_is_list($data)) {
            throw CdekApiException::invalidResponse();
        }

        foreach ($data as $item) {
            if (! is_array($item) || array_is_list($item)) {
                throw CdekApiException::invalidResponse();
            }
        }

        return $data;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<string, mixed>
     */
    private function object(array $data, string $key): array
    {
        return $this->optionalObject($data, $key) ?? throw CdekApiException::invalidResponse();
    }

    /**
     * @param  array<mixed>  $data
     * @return array<string, mixed>|null
     */
    private function optionalObject(array $data, string $key): ?array
    {
        $value = $data[$key] ?? null;

        if ($value !== null && (! is_array($value) || ($value !== [] && array_is_list($value)))) {
            throw CdekApiException::invalidResponse();
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private function string(array $data, string $key): string
    {
        return $this->optionalString($data, $key) ?? throw CdekApiException::invalidResponse();
    }

    /** @param array<mixed> $data */
    private function optionalString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if ($value !== null && (! is_string($value) || $value === '')) {
            throw CdekApiException::invalidResponse();
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private function integer(array $data, string $key): int
    {
        $value = $data[$key] ?? null;

        if (! is_int($value)) {
            throw CdekApiException::invalidResponse();
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private function number(array $data, string $key): float
    {
        return $this->optionalNumber($data, $key) ?? throw CdekApiException::invalidResponse();
    }

    /** @param array<mixed> $data */
    private function optionalNumber(array $data, string $key): ?float
    {
        $value = $data[$key] ?? null;

        if ($value !== null && ! is_int($value) && ! is_float($value)) {
            throw CdekApiException::invalidResponse();
        }

        return $value === null ? null : (float) $value;
    }

    /** @param array<mixed> $data */
    private function date(array $data, string $key): DateTimeImmutable
    {
        $value = $this->string($data, $key);

        if (preg_match('/^\d{4}-\d{2}-\d{2}T/', $value) !== 1) {
            throw CdekApiException::invalidResponse();
        }

        try {
            $date = new DateTimeImmutable($value);
        } catch (Exception) {
            throw CdekApiException::invalidResponse();
        }

        $errors = DateTimeImmutable::getLastErrors();

        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            throw CdekApiException::invalidResponse();
        }

        return $date;
    }
}
