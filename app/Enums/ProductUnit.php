<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductUnit: string implements HasLabel
{
    case GRAM = 'GRAM';
    case MILLILITER = 'MILLILITER';
    case PIECE = 'PIECE';

    public function getLabel(): string
    {
        return match ($this) {
            self::GRAM => 'г',
            self::MILLILITER => 'мл',
            self::PIECE => 'шт',
        };
    }
}
