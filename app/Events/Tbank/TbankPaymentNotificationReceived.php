<?php

declare(strict_types=1);

namespace App\Events\Tbank;

use App\DTO\Tbank\TbankPaymentNotificationDto;
use Illuminate\Foundation\Events\Dispatchable;

final class TbankPaymentNotificationReceived
{
    use Dispatchable;

    public function __construct(public readonly TbankPaymentNotificationDto $notification) {}
}
