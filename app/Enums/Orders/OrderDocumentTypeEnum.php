<?php

declare(strict_types=1);

namespace App\Enums\Orders;

use Filament\Support\Contracts\HasLabel;

enum OrderDocumentTypeEnum: string implements HasLabel
{
    case WAYBILL = 'WAYBILL';
    case BARCODES = 'BARCODES';

    public function isWaybill(): bool
    {
        return $this === self::WAYBILL;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::WAYBILL => __('Квитанция'),
            self::BARCODES => __('Наклейки'),
        };
    }
}
