<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DeliveryMethodEnum: string implements HasLabel
{
    case PICKUP = 'PICKUP';
    case COURIER = 'COURIER';
    case CDEK = 'CDEK';

    public function getLabel(): string
    {
        return match ($this) {
            self::PICKUP => __('Самовывоз'),
            self::COURIER => __('Курьер'),
            self::CDEK => __('СДЭК'),
        };
    }
}
