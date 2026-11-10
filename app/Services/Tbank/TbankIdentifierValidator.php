<?php

declare(strict_types=1);

namespace App\Services\Tbank;

use InvalidArgumentException;

final class TbankIdentifierValidator
{
    public static function validatePaymentId(string $paymentId): void
    {
        if (preg_match('/^[0-9]{1,20}$/D', $paymentId) !== 1) {
            throw new InvalidArgumentException('Укажите корректный идентификатор платежа Т-Банка.');
        }
    }

    public static function validateOrderId(string $orderId): void
    {
        if (trim($orderId) === '' || mb_strlen($orderId) > 50) {
            throw new InvalidArgumentException('Идентификатор операции должен содержать от 1 до 50 символов.');
        }
    }
}
