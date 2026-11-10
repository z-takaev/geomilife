<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethodEnum: string implements HasLabel
{
    case ONLINE = 'ONLINE';
    case PICKUP = 'PICKUP';

    public function getLabel(): string
    {
        return match ($this) {
            self::ONLINE => __('Картой онлайн'),
            self::PICKUP => __('При получении'),
        };
    }
}
