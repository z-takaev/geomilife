<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\Orders\OrderNotificationEventEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Models\Order;
use App\Services\Orders\OrderNotificationService;
use Illuminate\Support\Facades\DB;

final class UpdateOrderStatusFromShipmentAction
{
    public function __construct(private readonly OrderNotificationService $notifications) {}

    public function run(Order $order, OrderStatusEnum $status): void
    {
        if (! in_array($status, [OrderStatusEnum::SHIPPED, OrderStatusEnum::DELIVERED], true)) {
            return;
        }

        DB::transaction(function () use ($order, $status): void {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if (! $lockedOrder->delivery_method->isCdek()
                || $lockedOrder->cancellation_state !== null
                || ! in_array($lockedOrder->status, [OrderStatusEnum::READY_TO_SHIP, OrderStatusEnum::SHIPPED], true)
                || $lockedOrder->status->is($status)) {
                return;
            }

            $lockedOrder->update(['status' => $status]);
            $this->notifications->queue(
                $lockedOrder,
                OrderNotificationEventEnum::from($status->value)->value,
                'status:'.$status->value,
            );
        });
    }
}
