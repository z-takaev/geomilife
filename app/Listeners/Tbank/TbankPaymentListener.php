<?php

declare(strict_types=1);

namespace App\Listeners\Tbank;

use App\Events\Tbank\TbankPaymentNotificationReceived;
use App\Services\Orders\OrderPaymentService;

final class TbankPaymentListener
{
    public function __construct(private readonly OrderPaymentService $payments) {}

    public function handle(TbankPaymentNotificationReceived $event): void
    {
        $this->payments->handleBankNotification($event->notification);
    }
}
