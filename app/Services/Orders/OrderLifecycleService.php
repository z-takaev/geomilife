<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Jobs\Orders\CreateCdekShipmentJob;
use App\Models\FilamentUser;
use App\Models\Order;
use App\Models\User;
use App\Services\Cdek\Exceptions\CdekApiException;
use App\Support\Traits\Orders\ValidatesOrderManagement;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OrderLifecycleService
{
    use ValidatesOrderManagement;

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
        if ($order->cancellation_state !== null
            || ($order->payment_method->isOnline() && ! $order->payment_status->isPaid())) {
            return null;
        }

        $stages = $this->stages($order);
        $index = array_search($order->status, $stages, true);
        $next = $index === false ? null : ($stages[$index + 1] ?? null);

        if (($order->delivery_method->isCdek() && in_array($next, [OrderStatusEnum::SHIPPED, OrderStatusEnum::DELIVERED], true))
            || (($next?->isDelivered() ?? false) && ! $order->payment_status->isPaid())) {
            return null;
        }

        return $next;
    }

    /**
     * Переводит заказ в разрешённый статус сотрудником и запускает связанные действия.
     */
    public function transition(Order $order, OrderStatusEnum $status, FilamentUser $actor): void
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

            $lockedOrder->update(['status' => $status]);
            $this->notify($lockedOrder, $status->value, 'status:'.$status->value);

            return true;
        });

        if ($changed && $status->isReadyToShip()) {
            CreateCdekShipmentJob::dispatch($order->id);
        }
    }

    /**
     * Обновляет статус заказа по данным об отправке или вручении СДЭК.
     */
    public function applyShipmentStatus(Order $order, OrderStatusEnum $status): void
    {
        if (! in_array($status, [OrderStatusEnum::SHIPPED, OrderStatusEnum::DELIVERED], true)) {
            return;
        }

        DB::transaction(function () use ($order, $status): void {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if (! $lockedOrder->delivery_method->isCdek() || $lockedOrder->cancellation_state !== null
                || ! in_array($lockedOrder->status, [OrderStatusEnum::READY_TO_SHIP, OrderStatusEnum::SHIPPED], true)
                || $lockedOrder->status->is($status)) {
                return;
            }

            $lockedOrder->update(['status' => $status]);
            $this->notify($lockedOrder, $status->value, 'status:'.$status->value);

        });
    }

    /**
     * Проверяет возможность отмены заказа и запускает её выполнение.
     */
    public function cancel(Order $order, User|FilamentUser|null $actor, string $reason = '', bool $onlyIfNew = false): void
    {
        DB::transaction(function () use ($order, $actor, $reason, $onlyIfNew): void {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($onlyIfNew && ! $lockedOrder->status->isNew()) {
                throw new AuthorizationException;
            }

            $this->assertCancellationAllowed($lockedOrder, $actor, $reason);

            if ($lockedOrder->status->isCancelled()) {
                return;
            }

            $lockedOrder->update([
                'cancellation_state' => 'PENDING',
                'cancellation_reason' => $reason,
            ]);
        });

        $this->resumeCancellation($order);
    }

    /**
     * Продолжает отмену заказа, согласует отмену отправления и запускает возврат оплаты.
     */
    public function resumeCancellation(Order $order): void
    {
        $order->refresh();

        if ($order->status->isCancelled()) {
            app(OrderPaymentService::class)->refund($order);

            return;
        }

        if (! in_array($order->cancellation_state, ['PENDING', 'FAILED'], true)) {
            return;
        }

        if ($order->delivery_method->isCdek() && ! $this->cancelShipment($order)) {
            return;
        }

        $this->completeCancellation($order);

        app(OrderPaymentService::class)->refund($order->fresh());
    }

    private function notify(Order $order, string $event, string $key): void
    {
        app(OrderNotificationService::class)->send($order->fresh(), $event, $key);
    }

    private function assertCancellationAllowed(Order $order, User|FilamentUser|null $actor, string $reason): void
    {
        if ($actor instanceof User) {
            if ($order->user_id !== $actor->id) {
                throw new AuthorizationException;
            }

            if (! $order->status->isNew()) {
                throw ValidationException::withMessages(['order' => 'Покупатель может отменить только новый заказ.']);
            }
        } elseif ($actor instanceof FilamentUser) {
            $this->assertStaff($actor);

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
            if (! app(OrderShipmentService::class)->cancel($order)) {
                return false;
            }
        } catch (ValidationException $exception) {
            DB::transaction(function () use ($order): void {
                $order->update(['cancellation_state' => null]);

                $this->notify($order, 'CANCELLATION_REJECTED', 'cancel-rejected:'.$order->id.':'.now()->format('Y-m-d-H'));
            });
            throw $exception;
        } catch (CdekApiException $exception) {
            DB::transaction(function () use ($order): void {
                $order->update(['cancellation_state' => 'FAILED']);

                $this->notify($order, 'CANCELLATION_FAILED', 'cancel-error:'.$order->id.':'.now()->format('Y-m-d-H'));
            });
            report($exception);

            return false;
        }

        return true;
    }

    private function completeCancellation(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($lockedOrder->status->isCancelled()
                || ! in_array($lockedOrder->cancellation_state, ['PENDING', 'FAILED'], true)) {
                return;
            }

            $lockedOrder->update(['status' => OrderStatusEnum::CANCELLED, 'cancellation_state' => 'COMPLETED', 'cancelled_at' => now()]);

            $this->notify($lockedOrder, 'CANCELLED', 'status:CANCELLED');
        });
    }
}
