<?php

declare(strict_types=1);

namespace App\Enums\Orders;

enum PaymentOperationStateEnum: string
{
    case INITIATING = 'INITIATING';
    case RECONCILE_REQUIRED = 'RECONCILE_REQUIRED';
    case REFUNDING = 'REFUNDING';
}
