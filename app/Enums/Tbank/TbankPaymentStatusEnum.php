<?php

declare(strict_types=1);

namespace App\Enums\Tbank;

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
}
