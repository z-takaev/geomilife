<?php

declare(strict_types=1);

namespace App\Enums\Cdek;

enum CdekRequestStateEnum: string
{
    case ACCEPTED = 'ACCEPTED';
    case WAITING = 'WAITING';
    case SUCCESSFUL = 'SUCCESSFUL';
    case INVALID = 'INVALID';

    public function is(self $type): bool
    {
        return $this === $type;
    }

    public function isSuccessful(): bool
    {
        return $this->is(self::SUCCESSFUL);
    }

    public function isInvalid(): bool
    {
        return $this->is(self::INVALID);
    }
}
