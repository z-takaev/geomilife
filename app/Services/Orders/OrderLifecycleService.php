<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\DeliveryMethodEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
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
            || ($order->payment_method === PaymentMethodEnum::ONLINE && $order->payment_status !== PaymentStatusEnum::PAID)) {
            return null;
        }

        $stages = $this->stages($order);
        $index = array_search($order->status, $stages, true);
        $next = $index === false ? null : ($stages[$index + 1] ?? null);

        if (($order->delivery_method === DeliveryMethodEnum::CDEK && in_array($next, [OrderStatusEnum::SHIPPED, OrderStatusEnum::DELIVERED], true))
            || ($next === OrderStatusEnum::DELIVERED && $order->payment_status !== PaymentStatusEnum::PAID)) {
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

        if ($status === OrderStatusEnum::CANCELLED) {
            throw ValidationException::withMessages(['status' => 'Для отмены укажите причину отдельным действием.']);
        }

        $changed = DB::transaction(function () use ($order, $status, $actor): bool {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($lockedOrder->status === $status) {
                return false;
            }

            $this->assertNotCancelling($lockedOrder);

            if ($this->nextManualStatus($lockedOrder) !== $status) {
                throw ValidationException::withMessages(['status' => 'Этот переход статуса недоступен.']);
            }

            $this->saveTransition($lockedOrder, $status, 'STAFF', $actor);
            $this->notify($lockedOrder, $status->value, 'status:'.$status->value);

            return true;
        });

        if ($changed && $status === OrderStatusEnum::READY_TO_SHIP) {
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

            if ($lockedOrder->delivery_method !== DeliveryMethodEnum::CDEK || $lockedOrder->cancellation_state !== null
                || ! in_array($lockedOrder->status, [OrderStatusEnum::READY_TO_SHIP, OrderStatusEnum::SHIPPED], true)
                || $lockedOrder->status === $status) {
                return;
            }

            $this->saveTransition($lockedOrder, $status, 'CDEK');
            $this->notify($lockedOrder, $status->value, 'status:'.$status->value);

        });
    }

    /**
     * Проверяет возможность отмены заказа и запускает её выполнение.
     */
    public function cancel(Order $order, User|FilamentUser|null $actor, string $reason = '', bool $onlyIfUnpaid = false, bool $onlyIfNew = false): void
    {
        DB::transaction(function () use ($order, $actor, $reason, $onlyIfUnpaid, $onlyIfNew): void {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($onlyIfNew && $lockedOrder->status !== OrderStatusEnum::NEW) {
                throw new AuthorizationException;
            }

            if ($onlyIfUnpaid && ($lockedOrder->payment_method !== PaymentMethodEnum::ONLINE
                || $lockedOrder->status !== OrderStatusEnum::NEW || $lockedOrder->payment_status === PaymentStatusEnum::PAID
                || $lockedOrder->payment_expires_at === null || $lockedOrder->payment_expires_at->gt(now()))) {
                return;
            }

            $this->assertCancellationAllowed($lockedOrder, $actor, $reason);

            if ($lockedOrder->status === OrderStatusEnum::CANCELLED) {
                return;
            }

            $lockedOrder->update([
                'cancellation_state' => 'PENDING',
                'cancellation_reason' => $reason,
                'cancellation_actor_type' => $actor === null ? null : $actor::class,
                'cancellation_actor_id' => $actor?->id,
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

        if ($order->status === OrderStatusEnum::CANCELLED) {
            app(OrderPaymentService::class)->refund($order);

            return;
        }

        if (! in_array($order->cancellation_state, ['PENDING', 'FAILED'], true)) {
            return;
        }

        if ($order->delivery_method === DeliveryMethodEnum::CDEK && ! $this->cancelShipment($order)) {
            return;
        }

        $this->completeCancellation($order);

        app(OrderPaymentService::class)->refund($order->fresh());
    }

    private function saveTransition(Order $order, OrderStatusEnum $status, string $source, ?FilamentUser $actor = null): void
    {
        $previous = $order->status;

        $order->update(['status' => $status]);

        $order->history()->firstOrCreate(['event_key' => 'status:'.$status->value], [
            'from_status' => $previous->value,
            'to_status' => $status->value,
            'source' => $source,
            'actor_type' => $actor === null ? null : $actor::class,
            'actor_id' => $actor?->id,
            'created_at' => now(),
        ]);
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

            if ($order->status !== OrderStatusEnum::NEW) {
                throw ValidationException::withMessages(['order' => 'Покупатель может отменить только новый заказ.']);
            }
        } elseif ($actor instanceof FilamentUser) {
            $this->assertStaff($actor);

            if (trim($reason) === '') {
                throw ValidationException::withMessages(['reason' => 'Укажите причину отмены заказа.']);
            }
        }

        if ($order->status === OrderStatusEnum::DELIVERED) {
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

            if ($lockedOrder->status === OrderStatusEnum::CANCELLED
                || ! in_array($lockedOrder->cancellation_state, ['PENDING', 'FAILED'], true)) {
                return;
            }

            $previous = $lockedOrder->status;

            $lockedOrder->update(['status' => OrderStatusEnum::CANCELLED, 'cancellation_state' => 'COMPLETED', 'cancelled_at' => now()]);

            $lockedOrder->history()->firstOrCreate(['event_key' => 'status:CANCELLED'], [
                'from_status' => $previous->value,
                'to_status' => OrderStatusEnum::CANCELLED->value,
                'source' => match ($lockedOrder->cancellation_actor_type) {
                    null => 'SYSTEM',
                    FilamentUser::class => 'STAFF',
                    default => 'USER',
                },
                'actor_type' => $lockedOrder->cancellation_actor_type,
                'actor_id' => $lockedOrder->cancellation_actor_id,
                'reason' => $lockedOrder->cancellation_reason,
                'created_at' => now(),
            ]);
            $this->notify($lockedOrder, 'CANCELLED', 'status:CANCELLED');
        });
    }
}
