<?php

declare(strict_types=1);

namespace App\Jobs\Orders;

use App\Models\Order;
use App\Services\Orders\CdekShipmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncCdekShipmentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $orderId) {}

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(CdekShipmentService $service): void
    {
        $order = Order::query()->find($this->orderId);

        if ($order !== null) {
            $service->sync($order);
        }
    }
}
