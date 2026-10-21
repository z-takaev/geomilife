<?php

declare(strict_types=1);

namespace App\Services\Cdek;

use App\Services\Cdek\Exceptions\CdekApiException;
use InvalidArgumentException;

final class CdekService
{
    public function __construct(
        private readonly CdekClient $client,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCities(int $page = 0, int $size = 1000): array
    {
        if ($page < 0) {
            throw new InvalidArgumentException('Номер страницы не может быть отрицательным.');
        }

        if ($size < 1) {
            throw new InvalidArgumentException('Размер страницы должен быть больше нуля.');
        }

        $cities = $this->client->get('/v2/location/cities', [
            'country_codes' => 'RU',
            'lang' => 'RU',
            'page' => $page,
            'size' => $size,
        ]);

        if (! array_is_list($cities)) {
            throw CdekApiException::invalidResponse();
        }

        return $cities;
    }
}
