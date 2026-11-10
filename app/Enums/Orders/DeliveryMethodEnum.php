<?php

declare(strict_types=1);

namespace App\Enums\Orders;

use Filament\Support\Contracts\HasLabel;

enum DeliveryMethodEnum: string implements HasLabel
{
    case PICKUP = 'PICKUP';
    case COURIER = 'COURIER';
    case CDEK = 'CDEK';

    public function is(self $type): bool
    {
        return $this === $type;
    }

    public function isPickup(): bool
    {
        return $this->is(self::PICKUP);
    }

    public function isCourier(): bool
    {
        return $this->is(self::COURIER);
    }

    public function isCdek(): bool
    {
        return $this->is(self::CDEK);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PICKUP => __('Самовывоз'),
            self::COURIER => __('Курьер'),
            self::CDEK => __('СДЭК'),
        };
    }
}
