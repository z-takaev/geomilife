<?php

declare(strict_types=1);

namespace App\Console\Commands\Orders;

use App\Actions\Orders\CancelOrderAction;
use App\Enums\Cdek\CdekShipmentOperationStateEnum;
use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\OrderCancellationStateEnum;
use App\Enums\Orders\OrderNotificationEventEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentStatusEnum;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Orders\CdekShipmentService;
use App\Services\Orders\OrderNotificationService;
use App\Services\Orders\OrderPaymentService;
use Illuminate\Console\Command;
use Throwable;

final class ReconcileOrdersCommand extends Command
{
    protected $signature = 'orders:reconcile';

    protected $description = 'Сверить платежи, отмены, возвраты и отправления заказов';

    public function handle(
        CancelOrderAction $cancellations,
        OrderPaymentService $payments,
        CdekShipmentService $shipments,
        OrderNotificationService $notifications,
    ): int {
        $this->reconcileCancellations($cancellations, $notifications);
        $this->reconcilePayments($payments, $notifications);
        $this->reconcileShipments($shipments, $notifications);

        return self::SUCCESS;
    }

    private function reconcileCancellations(
        CancelOrderAction $cancellations,
        OrderNotificationService $notifications,
    ): void {
        Order::query()
            ->whereIn('cancellation_state', [
                OrderCancellationStateEnum::PENDING->value,
                OrderCancellationStateEnum::FAILED->value,
            ])
            ->chunkById(100, function ($orders) use ($cancellations, $notifications): void {
                foreach ($orders as $order) {
                    try {
                        $cancellations->resume($order);
                    } catch (Throwable $exception) {
                        report($exception);

                        $notifications->queue(
                            $order,
                            OrderNotificationEventEnum::CANCELLATION_FAILED->value,
                            'cancel-reconcile-error:'.$order->id.':'.now()->format('Y-m-d-H'),
                        );
                    }
                }
            });
    }

    private function reconcilePayments(
        OrderPaymentService $payments,
        OrderNotificationService $notifications,
    ): void {
        Payment::query()->where('provider', 'TBANK')
            ->whereIn('status', [PaymentStatusEnum::PENDING, PaymentStatusEnum::AUTHORIZED, PaymentStatusEnum::REFUND_PENDING, PaymentStatusEnum::REFUND_FAILED])
            ->where(function ($query): void {
                $query->whereNull('last_checked_at')
                    ->orWhere('last_checked_at', '<=', now()->subMinutes((int) config('orders.reconciliation_stale_minutes')));
            })->chunkById(100, function ($attempts) use ($payments, $notifications): void {
                foreach ($attempts as $payment) {
                    try {
                        $payments->syncPayment($payment);
                        $order = $payment->order()->useWritePdo()->firstOrFail();

                        if ($order->status->isCancelled()) {
                            $payments->cancelOrRefundPayments($order);
                        }
                    } catch (Throwable $exception) {
                        report($exception);

                        $notifications->queue(
                            $payment->order,
                            OrderNotificationEventEnum::PAYMENT_RECONCILE_FAILED->value,
                            'payment-reconcile-error:'.$payment->id.':'.now()->format('Y-m-d-H'),
                        );
                    }
                }
            });
    }

    private function reconcileShipments(
        CdekShipmentService $shipments,
        OrderNotificationService $notifications,
    ): void {
        Order::query()
            ->where('delivery_method', DeliveryMethodEnum::CDEK)
            ->where('payment_status', PaymentStatusEnum::PAID)
            ->whereIn('status', [OrderStatusEnum::READY_TO_SHIP, OrderStatusEnum::SHIPPED])
            ->whereNull('cancellation_state')
            ->where(function ($query): void {
                // TODO: Для чего whereDoesntHave и orWhereHas
                $query->whereDoesntHave('shipment')->orWhereHas('shipment', function ($shipment): void {
                    $shipment->whereNull('last_checked_at')
                        ->orWhere('last_checked_at', '<=', now()->subMinutes((int) config('orders.reconciliation_stale_minutes')));
                });
            })->with('shipment')->chunkById(100, function ($orders) use ($shipments, $notifications): void {
                foreach ($orders as $order) {
                    try {
                        if ($order->status->isReadyToShip()
                            && ($order->shipment === null || $order->shipment->operation_state?->is(CdekShipmentOperationStateEnum::ABSENT))) {
                            $shipments->create($order);
                        } else {
                            $shipments->sync($order);
                        }
                    } catch (Throwable $exception) {
                        report($exception);

                        $notifications->queue(
                            $order,
                            OrderNotificationEventEnum::SHIPPING_FAILED->value,
                            'shipment-check:'.$order->id.':'.now()->format('Y-m-d-H'),
                        );
                    }
                }
            });
    }
}
