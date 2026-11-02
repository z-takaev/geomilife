<?php

declare(strict_types=1);

namespace App\DTO\Tbank\Requests;

use InvalidArgumentException;

final readonly class CheckOrderDto
{
    public function __construct(public string $orderId)
    {
        if (trim($orderId) === '' || mb_strlen($orderId) > 50) {
            throw new InvalidArgumentException('Идентификатор операции должен содержать от 1 до 50 символов.');
        }
    }

    public function toArray(): array
    {
        return ['OrderId' => $this->orderId];
    }
}
