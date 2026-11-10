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

    public function is(self $type): bool
    {
        return $this === $type;
    }

    public function isReady(): bool
    {
        return $this->is(self::READY);
    }
}
