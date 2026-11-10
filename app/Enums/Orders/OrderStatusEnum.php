<?php

declare(strict_types=1);

namespace App\Enums\Orders;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatusEnum: string implements HasColor, HasLabel
{
    case NEW = 'NEW';
    case CONFIRMED = 'CONFIRMED';
    case READY_FOR_PICKUP = 'READY_FOR_PICKUP';
    case READY_TO_SHIP = 'READY_TO_SHIP';
    case SHIPPED = 'SHIPPED';
    case DELIVERED = 'DELIVERED';
    case CANCELLED = 'CANCELLED';

    public function is(self $type): bool
    {
        return $this === $type;
    }

    public function isNew(): bool
    {
        return $this->is(self::NEW);
    }

    public function isReadyForPickup(): bool
    {
        return $this->is(self::READY_FOR_PICKUP);
    }

    public function isReadyToShip(): bool
    {
        return $this->is(self::READY_TO_SHIP);
    }

    public function isDelivered(): bool
    {
        return $this->is(self::DELIVERED);
    }

    public function isCancelled(): bool
    {
        return $this->is(self::CANCELLED);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::NEW => __('Новый'),
            self::CONFIRMED => __('Подтверждён и собирается'),
            self::READY_FOR_PICKUP => __('Готов к выдаче'),
            self::READY_TO_SHIP => __('Готов к отправке'),
            self::SHIPPED => __('Отправлен'),
            self::DELIVERED => __('Доставлен'),
            self::CANCELLED => __('Отменён'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NEW => 'info',
            self::CONFIRMED => 'warning',
            self::READY_FOR_PICKUP => 'warning',
            self::READY_TO_SHIP => 'warning',
            self::SHIPPED => 'info',
            self::DELIVERED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
