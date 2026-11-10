<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CatalogSortEnum: string implements HasLabel
{
    case POPULAR = 'POPULAR';
    case NEW = 'NEW';
    case PRICE_ASC = 'PRICE_ASC';
    case PRICE_DESC = 'PRICE_DESC';
    case NAME = 'NAME';

    public function getLabel(): string
    {
        return match ($this) {
            self::POPULAR => 'Сначала хиты',
            self::NEW => 'По новизне',
            self::PRICE_ASC => 'Сначала дешевле',
            self::PRICE_DESC => 'Сначала дороже',
            self::NAME => 'По названию',
        };
    }
}
