<?php

declare(strict_types=1);

namespace App\Enums\Cdek;

enum CdekRequestStateEnum: string
{
    case ACCEPTED = 'ACCEPTED';
    case WAITING = 'WAITING';
    case SUCCESSFUL = 'SUCCESSFUL';
    case INVALID = 'INVALID';
}
