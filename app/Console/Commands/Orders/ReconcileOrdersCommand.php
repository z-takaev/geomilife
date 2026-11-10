<?php

declare(strict_types=1);

namespace App\Console\Commands\Orders;

use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentStatusEnum;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Orders\CdekShipmentService;
use App\Services\Orders\OrderLifecycleService;
use App\Services\Orders\OrderNotificationService;
use App\Services\Orders\OrderPaymentService;
use Illuminate\Console\Command;
use Throwable;

final class ReconcileOrdersCommand extends Command
{
    protected $signature = 'orders:reconcile';

    protected $description = 'Сверить платежи, отмены, возвраты и отправления заказов';

    public function handle(OrderPaymentService $payments, OrderLifecycleService $statuses): int
    {
        $this->reconcileCancellations($statuses);
        $this->reconcilePayments($payments);
        $this->reconcileShipments();

        return self::SUCCESS;
    }

    private function reconcileCancellations(OrderLifecycleService $statuses): void
    {
        Order::query()
            ->whereIn('cancellation_state', ['PENDING', 'FAILED'])
            ->chunkById(100, function ($orders) use ($statuses): void {
                foreach ($orders as $order) {
                    try {
                        $statuses->resumeCancellation($order);
                    } catch (Throwable $exception) {
                        report($exception);
                        app(OrderNotificationService::class)->queue($order, 'CANCELLATION_FAILED',
                            'cancel-reconcile-error:'.$order->id.':'.now()->format('Y-m-d-H'));
                    }
                }
            });

    }

    private function reconcilePayments(OrderPaymentService $payments): void
    {
        Payment::query()->where('provider', 'TBANK')
            ->whereIn('status', [PaymentStatusEnum::PENDING, PaymentStatusEnum::AUTHORIZED, PaymentStatusEnum::REFUND_PENDING, PaymentStatusEnum::REFUND_FAILED])
            ->where(function ($query): void {
                $query->whereNull('last_checked_at')
                    ->orWhere('last_checked_at', '<=', now()->subMinutes((int) config('orders.reconciliation_stale_minutes')));
            })->chunkById(100, function ($attempts) use ($payments): void {
                foreach ($attempts as $payment) {
                    try {
                        $payments->syncPayment($payment);
                        $order = $payment->order()->useWritePdo()->firstOrFail();

                        if ($order->status->isCancelled()) {
                            $payments->cancelOrRefundPayments($order);
                        }
                    } catch (Throwable $exception) {
                        report($exception);
                        app(OrderNotificationService::class)->queue($payment->order, 'PAYMENT_RECONCILE_FAILED',
                            'payment-reconcile-error:'.$payment->id.':'.now()->format('Y-m-d-H'));
                    }
                }
            });

    }

    private function reconcileShipments(): void
    {
        Order::query()->where('delivery_method', DeliveryMethodEnum::CDEK)->where('payment_status', PaymentStatusEnum::PAID)
            ->whereIn('status', [OrderStatusEnum::READY_TO_SHIP, OrderStatusEnum::SHIPPED])->whereNull('cancellation_state')
            ->where(function ($query): void {
                $query->whereDoesntHave('shipment')->orWhereHas('shipment', function ($shipment): void {
                    $shipment->whereNull('last_checked_at')
                        ->orWhere('last_checked_at', '<=', now()->subMinutes((int) config('orders.reconciliation_stale_minutes')));
                });
            })->with('shipment')->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    try {
                        $shipments = app(CdekShipmentService::class);
                        if ($order->status->isReadyToShip()
                            && ($order->shipment === null || $order->shipment->operation_state === 'ABSENT')) {
                            $shipments->create($order);
                        } else {
                            $shipments->sync($order);
                        }
                    } catch (Throwable $exception) {
                        report($exception);
                        app(OrderNotificationService::class)->queue($order, 'SHIPPING_FAILED',
                            'shipment-check:'.$order->id.':'.now()->format('Y-m-d-H'));
                    }
                }
            });
    }
}
