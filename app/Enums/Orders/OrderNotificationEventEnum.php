<?php

declare(strict_types=1);

namespace App\Enums\Orders;

enum OrderNotificationEventEnum: string
{
    case CREATED = 'CREATED';
    case CONFIRMED = 'CONFIRMED';
    case READY_FOR_PICKUP = 'READY_FOR_PICKUP';
    case READY_TO_SHIP = 'READY_TO_SHIP';
    case SHIPPED = 'SHIPPED';
    case DELIVERED = 'DELIVERED';
    case CANCELLED = 'CANCELLED';
    case REFUNDED = 'REFUNDED';
    case REFUND_FAILED = 'REFUND_FAILED';
    case CANCELLATION_FAILED = 'CANCELLATION_FAILED';
    case CANCELLATION_REJECTED = 'CANCELLATION_REJECTED';
    case SHIPPING_FAILED = 'SHIPPING_FAILED';
    case DOCUMENT_FAILED = 'DOCUMENT_FAILED';
    case PAYMENT_RECONCILE_FAILED = 'PAYMENT_RECONCILE_FAILED';

    public function isForBuyer(): bool
    {
        return in_array($this, [
            self::CREATED,
            self::CONFIRMED,
            self::READY_FOR_PICKUP,
            self::READY_TO_SHIP,
            self::SHIPPED,
            self::DELIVERED,
            self::CANCELLED,
            self::REFUNDED,
        ], true);
    }

    public function isForManager(): bool
    {
        return in_array($this, [
            self::CREATED,
            self::REFUND_FAILED,
            self::CANCELLATION_FAILED,
            self::CANCELLATION_REJECTED,
            self::SHIPPING_FAILED,
            self::DOCUMENT_FAILED,
            self::PAYMENT_RECONCILE_FAILED,
        ], true);
    }
}
