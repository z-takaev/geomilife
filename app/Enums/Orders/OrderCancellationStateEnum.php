<?php

declare(strict_types=1);

namespace App\Enums\Orders;

enum OrderCancellationStateEnum: string
{
    case PENDING = 'PENDING';
    case FAILED = 'FAILED';
    case COMPLETED = 'COMPLETED';
}
