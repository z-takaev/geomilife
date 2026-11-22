<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Actions\Orders\CancelOrderAction;
use App\Actions\Orders\UpdateOrderStatusFromShipmentAction;
use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentStatusEnum;
use App\Jobs\Orders\CreateCdekShipmentJob;
use App\Models\FilamentUser;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OrderLifecycleService
{
    public function __construct(
        private readonly CancelOrderAction $cancelOrder,
        private readonly UpdateOrderStatusFromShipmentAction $shipmentStatusUpdater,
        private readonly OrderNotificationService $notifications,
    ) {}

    /**
     * Возвращает этапы обработки заказа для выбранного способа получения.
     */
    public function stages(Order $order): array
    {
        return match ($order->delivery_method) {
            DeliveryMethodEnum::PICKUP => [OrderStatusEnum::NEW, OrderStatusEnum::CONFIRMED, OrderStatusEnum::READY_FOR_PICKUP, OrderStatusEnum::DELIVERED],
            DeliveryMethodEnum::CDEK => [OrderStatusEnum::NEW, OrderStatusEnum::CONFIRMED, OrderStatusEnum::READY_TO_SHIP, OrderStatusEnum::SHIPPED, OrderStatusEnum::DELIVERED],
            DeliveryMethodEnum::COURIER => [OrderStatusEnum::NEW, OrderStatusEnum::CONFIRMED, OrderStatusEnum::DELIVERED],
        };
    }

    /**
     * Возвращает следующий статус, доступный для ручного перевода заказа.
     */
    public function nextManualStatus(Order $order): ?OrderStatusEnum
    {
        if (
            $order->cancellation_state !== null
            || ($order->payment_method->isOnline() && ! $order->payment_status->isPaid())
        ) {
            return null;
        }

        $stages = $this->stages($order);
        $index = array_search($order->status, $stages, true);
        $next = $index === false ? null : ($stages[$index + 1] ?? null);

        $collectsPickupPayment = ($next?->isDelivered() ?? false)
            && $order->delivery_method->isPickup()
            && $order->payment_method->isPickup();

        if (($order->delivery_method->isCdek() && in_array($next, [OrderStatusEnum::SHIPPED, OrderStatusEnum::DELIVERED], true))
            || (($next?->isDelivered() ?? false) && ! $order->payment_status->isPaid() && ! $collectsPickupPayment)) {
            return null;
        }

        return $next;
    }

    /**
     * Переводит заказ в разрешённый статус сотрудником и запускает связанные действия.
     */
    public function advanceStatus(Order $order, OrderStatusEnum $status, FilamentUser $actor): void
    {
        $this->assertStaff($actor);

        if ($status->isCancelled()) {
            throw ValidationException::withMessages(['status' => 'Для отмены укажите причину отдельным действием.']);
        }

        $changed = DB::transaction(function () use ($order, $status): bool {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($lockedOrder->status->is($status)) {
                return false;
            }

            $this->assertNotCancelling($lockedOrder);

            if (! $this->nextManualStatus($lockedOrder)?->is($status)) {
                throw ValidationException::withMessages(['status' => 'Этот переход статуса недоступен.']);
            }

            $changes = ['status' => $status];

            if ($status->isDelivered() && $lockedOrder->delivery_method->isPickup()
                && $lockedOrder->payment_method->isPickup()) {
                $changes['payment_status'] = PaymentStatusEnum::PAID;
            }

            $lockedOrder->update($changes);
            $this->notifications->queue($lockedOrder, $status->value, 'status:'.$status->value);

            return true;
        });

        if ($changed && $status->isReadyToShip()) {
            CreateCdekShipmentJob::dispatch($order->id);
        }
    }

    /**
     * Обновляет статус заказа по данным об отправке или вручении СДЭК.
     */
    public function updateStatusFromShipment(Order $order, OrderStatusEnum $status): void
    {
        $this->shipmentStatusUpdater->run($order, $status);
    }

    /**
     * Проверяет возможность отмены заказа и запускает её выполнение.
     */
    public function cancel(Order $order, User|FilamentUser|null $actor, string $reason = '', bool $onlyIfNew = false): void
    {
        $this->cancelOrder->run($order, $actor, $reason, $onlyIfNew);
    }

    /**
     * Продолжает отмену заказа, согласует отмену отправления и запускает возврат оплаты.
     */
    public function resumeCancellation(Order $order): void
    {
        $this->cancelOrder->resume($order);
    }

    private function assertStaff(FilamentUser $actor): void
    {
        if (! $actor->canManageOrders()) {
            throw new AuthorizationException;
        }
    }

    private function assertNotCancelling(Order $order): void
    {
        if ($order->cancellation_state !== null) {
            throw ValidationException::withMessages(['order' => 'Изменение заказа во время отмены недоступно.']);
        }
    }
}
