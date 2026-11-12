<?php

declare(strict_types=1);

namespace App\Enums\Cdek;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CdekPrintStateEnum: string implements HasColor, HasLabel
{
    case PENDING = 'PENDING';
    case CREATING = 'CREATING';
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

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('Ожидает формирования'),
            self::CREATING, self::ACCEPTED, self::PROCESSING => __('Формируется'),
            self::READY => __('Готов'),
            self::INVALID => __('Не удалось подготовить'),
            self::REMOVED => __('Требуется повторное формирование'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::READY => 'success',
            self::INVALID => 'danger',
            default => 'warning',
        };
    }
}
