<?php

declare(strict_types=1);

namespace App\Enums\Orders;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatusEnum: string implements HasColor, HasLabel
{
    case UNPAID = 'UNPAID';
    case PENDING = 'PENDING';
    case AUTHORIZED = 'AUTHORIZED';
    case PAID = 'PAID';
    case FAILED = 'FAILED';
    case CANCELLED = 'CANCELLED';
    case REFUND_PENDING = 'REFUND_PENDING';
    case REFUNDED = 'REFUNDED';
    case REFUND_FAILED = 'REFUND_FAILED';

    public function is(self $type): bool
    {
        return $this === $type;
    }

    public function isPaid(): bool
    {
        return $this->is(self::PAID);
    }

    public function isAuthorized(): bool
    {
        return $this->is(self::AUTHORIZED);
    }

    public function isCancelled(): bool
    {
        return $this->is(self::CANCELLED);
    }

    public function isRefundPending(): bool
    {
        return $this->is(self::REFUND_PENDING);
    }

    public function isRefunded(): bool
    {
        return $this->is(self::REFUNDED);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::UNPAID => __('Не оплачен'),
            self::PENDING => __('Ожидается оплата'),
            self::AUTHORIZED => __('Средства заблокированы'),
            self::PAID => __('Оплачен'),
            self::FAILED => __('Ошибка оплаты'),
            self::CANCELLED => __('Платёж отменён'),
            self::REFUND_PENDING => __('Возврат выполняется'),
            self::REFUNDED => __('Возвращён'),
            self::REFUND_FAILED => __('Ошибка возврата'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::UNPAID => 'gray',
            self::PENDING => 'warning',
            self::AUTHORIZED => 'warning',
            self::PAID => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'gray',
            self::REFUND_PENDING => 'warning',
            self::REFUNDED => 'info',
            self::REFUND_FAILED => 'danger',
        };
    }
}
