<?php

declare(strict_types=1);

namespace App\Enums\Cdek;

enum CdekPrintStateEnum: string
{
    case ACCEPTED = 'ACCEPTED';
    case PROCESSING = 'PROCESSING';
    case READY = 'READY';
    case INVALID = 'INVALID';
    case REMOVED = 'REMOVED';
}
