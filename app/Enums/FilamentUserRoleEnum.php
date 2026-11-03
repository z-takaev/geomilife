<?php

declare(strict_types=1);

namespace App\Enums;

enum FilamentUserRoleEnum: string
{
    case ADMIN = 'ADMIN';
    case MANAGER = 'MANAGER';
}
