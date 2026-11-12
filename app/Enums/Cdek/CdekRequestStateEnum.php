<?php

declare(strict_types=1);

namespace App\Enums\Cdek;

use Filament\Support\Contracts\HasLabel;

enum CdekRequestStateEnum: string implements HasLabel
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

    public function getLabel(): string
    {
        return match ($this) {
            self::ACCEPTED => __('Принят'),
            self::WAITING => __('Ожидает обработки'),
            self::SUCCESSFUL => __('Успешно обработан'),
            self::INVALID => __('Некорректный запрос'),
        };
    }
}
