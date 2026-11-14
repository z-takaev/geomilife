<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderNotificationEvent;
use App\Notifications\Orders\DeliveryQuoteErrorNotification;
use App\Notifications\Orders\OrderUpdateNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

final class OrderNotificationService
{
    /**
     * Регистрирует событие заказа и ставит уведомления в очередь после фиксации транзакции.
     */
    public function queue(Order $order, string $event, string $eventKey): void
    {
        $eventRecord = OrderNotificationEvent::query()->firstOrCreate(
            ['order_id' => $order->id, 'event_key' => hash('sha256', $eventKey)],
            ['event' => $event],
        );

        if ($eventRecord->dispatched_at !== null) {
            return;
        }

        DB::afterCommit(fn () => $this->queueEvent($eventRecord));
    }

    /**
     * Уведомляет менеджера об ошибке расчёта доставки с ограничением частоты по городу.
     */
    public function notifyQuoteError(int $cityId): void
    {
        $chatId = config('telegram.manager_chat_id');

        if (! filled($chatId) || ! Cache::add('orders:quote-error:'.$cityId, true, now()->addMinutes(5))) {
            return;
        }
        Notification::route('telegram', (string) $chatId)->notify(new DeliveryQuoteErrorNotification($cityId));
    }

    /**
     * Повторяет постановку в очередь ещё не отправленных уведомлений о заказах.
     */
    public function retryPending(): void
    {
        OrderNotificationEvent::query()
            ->whereNull('dispatched_at')
            ->eachById(
                fn (OrderNotificationEvent $eventRecord) => $this->queueEvent($eventRecord),
            );
    }

    private function queueEvent(OrderNotificationEvent $eventRecord): void
    {
        try {
            DB::transaction(function () use ($eventRecord): void {
                $eventRecord = OrderNotificationEvent::query()
                    ->lockForUpdate()
                    ->findOrFail($eventRecord->id);

                if ($eventRecord->dispatched_at !== null) {
                    return;
                }

                $order = $eventRecord->order;
                $event = $eventRecord->event;
                $buyerEvents = ['CREATED', 'CONFIRMED', 'READY_FOR_PICKUP',
                    'READY_TO_SHIP', 'SHIPPED', 'DELIVERED', 'CANCELLED', 'REFUNDED'];

                if (in_array($event, $buyerEvents, true)) {
                    $recipient = Notification::route('mail', $order->customer_email);
                    $user = $order->user;

                    if ($user?->telegram_notifications_enabled && filled($user->telegram_chat_id)) {
                        $recipient->route('telegram', (string) $user->telegram_chat_id);
                    }

                    $recipient->notify((new OrderUpdateNotification($order, $event, 'BUYER'))->beforeCommit());
                }

                $managerChat = config('telegram.manager_chat_id');
                $managerEvents = ['CREATED', 'REFUND_FAILED', 'CANCELLATION_FAILED', 'CANCELLATION_REJECTED',
                    'SHIPPING_FAILED', 'DOCUMENT_FAILED', 'PAYMENT_RECONCILE_FAILED'];

                if (filled($managerChat) && in_array($event, $managerEvents, true)) {
                    Notification::route('telegram', (string) $managerChat)
                        ->notify((new OrderUpdateNotification($order, $event, 'MANAGER'))->beforeCommit());
                }

                $eventRecord->update(['dispatched_at' => now()]);
            });
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
