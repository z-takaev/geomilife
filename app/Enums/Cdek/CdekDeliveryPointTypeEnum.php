<?php

declare(strict_types=1);

namespace App\Enums\Cdek;

enum CdekDeliveryPointTypeEnum: string
{
    case PVZ = 'PVZ';
    case POSTAMAT = 'POSTAMAT';
    case ALL = 'ALL';
}
