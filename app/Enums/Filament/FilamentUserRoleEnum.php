<?php

declare(strict_types=1);

namespace App\Enums\Filament;

enum FilamentUserRoleEnum: string
{
    case ADMIN = 'ADMIN';
    case MANAGER = 'MANAGER';

    public function is(self $type): bool
    {
        return $this === $type;
    }

    public function isAdmin(): bool
    {
        return $this->is(self::ADMIN);
    }

    public function isManager(): bool
    {
        return $this->is(self::MANAGER);
    }
}
