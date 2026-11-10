<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethodEnum: string implements HasLabel
{
    case ONLINE = 'ONLINE';
    case PICKUP = 'PICKUP';

    public function is(self $type): bool
    {
        return $this === $type;
    }

    public function isOnline(): bool
    {
        return $this->is(self::ONLINE);
    }

    public function isPickup(): bool
    {
        return $this->is(self::PICKUP);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ONLINE => __('Картой онлайн'),
            self::PICKUP => __('При получении'),
        };
    }
}
