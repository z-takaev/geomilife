<?php

declare(strict_types=1);

namespace App\DTO\Cdek;

final readonly class CdekDeliveryPointCityCodesPageDto
{
    /**
     * @param  list<int>  $cityCodes
     */
    public function __construct(
        public array $cityCodes,
        public int $itemsCount,
    ) {}
}
