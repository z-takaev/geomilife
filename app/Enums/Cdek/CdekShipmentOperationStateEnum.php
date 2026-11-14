<?php

declare(strict_types=1);

namespace App\Enums\Cdek;

use Filament\Support\Contracts\HasLabel;

enum CdekShipmentOperationStateEnum: string implements HasLabel
{
    case CREATING = 'CREATING';
    case REGISTERING = 'REGISTERING';
    case REGISTERED = 'REGISTERED';
    case FAILED = 'FAILED';
    case UNKNOWN = 'UNKNOWN';
    case ABSENT = 'ABSENT';
    case CANCELLING = 'CANCELLING';
    case CANCELLED = 'CANCELLED';
    case CANCEL_FAILED = 'CANCEL_FAILED';

    public function is(self $type): bool
    {
        return $this === $type;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::CREATING, self::REGISTERING => __('Регистрируется'),
            self::REGISTERED => __('Зарегистрировано'),
            self::FAILED => __('Ошибка регистрации'),
            self::UNKNOWN => __('Состояние неизвестно'),
            self::ABSENT => __('Не зарегистрировано в СДЭК'),
            self::CANCELLING => __('Отменяется'),
            self::CANCELLED => __('Отменено'),
            self::CANCEL_FAILED => __('Ошибка отмены'),
        };
    }
}
