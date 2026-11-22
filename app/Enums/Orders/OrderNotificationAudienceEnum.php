<?php

declare(strict_types=1);

namespace App\Enums\Orders;

enum OrderNotificationAudienceEnum: string
{
    case BUYER = 'BUYER';
    case MANAGER = 'MANAGER';
}
