<?php

declare(strict_types=1);

namespace App\Jobs\Orders;

use App\Models\Order;
use App\Services\Orders\OrderDocumentService;
use App\Services\Orders\OrderNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class PrepareCdekDocumentsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $orderId, public int $pollAttempt = 0) {}

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(OrderDocumentService $service): void
    {
        $order = Order::query()->find($this->orderId);

        if ($order !== null && $order->status->value !== 'CANCELLED' && $order->cancellation_state === null) {
            $service->prepareCdekDocuments($order);

            if ($order->documents()->whereIn('state', ['CREATING', 'ACCEPTED', 'PROCESSING'])->exists()) {
                if ($this->pollAttempt < 10) {
                    self::dispatch($order->id, $this->pollAttempt + 1)->delay(now()->addSeconds(30))->afterCommit();
                } else {
                    app(OrderNotificationService::class)->send($order, 'DOCUMENT_FAILED', 'document-preparation-delayed');
                }
            }
        }
    }
}
