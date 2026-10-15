<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductAvailability: string implements HasColor, HasLabel
{
    case PREORDER = 'PREORDER';
    case IN_STOCK = 'IN_STOCK';
    case UNAVAILABLE = 'UNAVAILABLE';

    public function getLabel(): string
    {
        return match ($this) {
            self::PREORDER => 'Под заказ',
            self::IN_STOCK => 'В наличии',
            self::UNAVAILABLE => 'Недоступен',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PREORDER => 'warning',
            self::IN_STOCK => 'success',
            self::UNAVAILABLE => 'danger',
        };
    }
}
