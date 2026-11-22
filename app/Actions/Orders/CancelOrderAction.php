<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\Orders\OrderCancellationStateEnum;
use App\Enums\Orders\OrderNotificationEventEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Models\FilamentUser;
use App\Models\Order;
use App\Models\User;
use App\Services\Cdek\Exceptions\CdekApiException;
use App\Services\Orders\CdekShipmentService;
use App\Services\Orders\OrderNotificationService;
use App\Services\Orders\OrderPaymentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelOrderAction
{
    public function __construct(
        private readonly CdekShipmentService $shipments,
        private readonly OrderPaymentService $payments,
        private readonly OrderNotificationService $notifications,
    ) {}

    public function run(
        Order $order,
        User|FilamentUser|null $actor,
        string $reason = '',
        bool $onlyIfNew = false,
    ): void {
        DB::transaction(function () use ($order, $actor, $reason, $onlyIfNew): void {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($onlyIfNew && ! $lockedOrder->status->isNew()) {
                throw new AuthorizationException;
            }

            $this->assertCanCancel($lockedOrder, $actor, $reason);

            if ($lockedOrder->status->isCancelled()) {
                return;
            }

            $lockedOrder->update([
                'cancellation_state' => OrderCancellationStateEnum::PENDING->value,
                'cancellation_reason' => $reason,
            ]);
        });

        $this->resume($order);
    }

    public function resume(Order $order): void
    {
        $order->refresh();

        if ($order->status->isCancelled()) {
            $this->payments->cancelOrRefundPayments($order);

            return;
        }

        if (! in_array($order->cancellation_state, [
            OrderCancellationStateEnum::PENDING->value,
            OrderCancellationStateEnum::FAILED->value,
        ], true)) {
            return;
        }

        if ($order->delivery_method->isCdek() && ! $this->cancelShipment($order)) {
            return;
        }

        $this->complete($order);
        $this->payments->cancelOrRefundPayments($order->fresh());
    }

    private function assertCanCancel(Order $order, User|FilamentUser|null $actor, string $reason): void
    {
        if ($actor instanceof User) {
            if ($order->user_id !== $actor->id) {
                throw new AuthorizationException;
            }

            if (! $order->status->isNew()) {
                throw ValidationException::withMessages(['order' => 'Покупатель может отменить только новый заказ.']);
            }
        } elseif ($actor instanceof FilamentUser) {
            if (! $actor->canManageOrders()) {
                throw new AuthorizationException;
            }

            if (trim($reason) === '') {
                throw ValidationException::withMessages(['reason' => 'Укажите причину отмены заказа.']);
            }
        }

        if ($order->status->isDelivered()) {
            throw ValidationException::withMessages(['order' => 'Полученный заказ отменить нельзя.']);
        }
    }

    private function cancelShipment(Order $order): bool
    {
        try {
            if (! $this->shipments->cancel($order)) {
                return false;
            }
        } catch (ValidationException $exception) {
            DB::transaction(function () use ($order): void {
                $order->update(['cancellation_state' => null]);

                $this->notifications->queue(
                    $order,
                    OrderNotificationEventEnum::CANCELLATION_REJECTED->value,
                    'cancel-rejected:'.$order->id.':'.now()->format('Y-m-d-H'),
                );
            });

            throw $exception;
        } catch (CdekApiException $exception) {
            DB::transaction(function () use ($order): void {
                $order->update(['cancellation_state' => OrderCancellationStateEnum::FAILED->value]);

                $this->notifications->queue(
                    $order,
                    OrderNotificationEventEnum::CANCELLATION_FAILED->value,
                    'cancel-error:'.$order->id.':'.now()->format('Y-m-d-H'),
                );
            });

            report($exception);

            return false;
        }

        return true;
    }

    private function complete(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($lockedOrder->status->isCancelled()
                || ! in_array($lockedOrder->cancellation_state, [
                    OrderCancellationStateEnum::PENDING->value,
                    OrderCancellationStateEnum::FAILED->value,
                ], true)) {
                return;
            }

            $lockedOrder->update([
                'status' => OrderStatusEnum::CANCELLED,
                'cancellation_state' => OrderCancellationStateEnum::COMPLETED->value,
                'cancelled_at' => now(),
            ]);

            $this->notifications->queue(
                $lockedOrder,
                OrderNotificationEventEnum::CANCELLED->value,
                'status:'.OrderNotificationEventEnum::CANCELLED->value,
            );
        });
    }
}
