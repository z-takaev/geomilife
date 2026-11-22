<?php

declare(strict_types=1);

namespace App\Enums\Tbank;

use App\Enums\Orders\PaymentStatusEnum;

enum TbankPaymentStatusEnum: string
{
    case NEW = 'NEW';
    case FORM_SHOWED = 'FORM_SHOWED';
    case AUTHORIZING = 'AUTHORIZING';
    case THREEDS_CHECKING = '3DS_CHECKING';
    case THREEDS_CHECKED = '3DS_CHECKED';
    case AUTHORIZED = 'AUTHORIZED';
    case CONFIRMING = 'CONFIRMING';
    case CONFIRMED = 'CONFIRMED';
    case REVERSING = 'REVERSING';
    case PARTIAL_REVERSED = 'PARTIAL_REVERSED';
    case REVERSED = 'REVERSED';
    case REFUNDING = 'REFUNDING';
    case ASYNC_REFUNDING = 'ASYNC_REFUNDING';
    case PARTIAL_REFUNDED = 'PARTIAL_REFUNDED';
    case REFUNDED = 'REFUNDED';
    case CANCELED = 'CANCELED';
    case DEADLINE_EXPIRED = 'DEADLINE_EXPIRED';
    case REJECTED = 'REJECTED';
    case AUTH_FAIL = 'AUTH_FAIL';

    public function is(self $type): bool
    {
        return $this === $type;
    }

    public function isCanceled(): bool
    {
        return $this->is(self::CANCELED);
    }

    public function isReversed(): bool
    {
        return $this->is(self::REVERSED);
    }

    public function toPaymentStatus(): ?PaymentStatusEnum
    {
        return match ($this) {
            self::CONFIRMED => PaymentStatusEnum::PAID,
            self::AUTHORIZED => PaymentStatusEnum::AUTHORIZED,
            self::REFUNDED => PaymentStatusEnum::REFUNDED,
            self::REFUNDING, self::ASYNC_REFUNDING, self::REVERSING => PaymentStatusEnum::REFUND_PENDING,
            self::CANCELED, self::REVERSED, self::DEADLINE_EXPIRED => PaymentStatusEnum::CANCELLED,
            self::REJECTED, self::AUTH_FAIL => PaymentStatusEnum::FAILED,
            self::NEW, self::FORM_SHOWED, self::AUTHORIZING, self::THREEDS_CHECKING,
            self::THREEDS_CHECKED, self::CONFIRMING => PaymentStatusEnum::PENDING,
            default => null,
        };
    }
}
