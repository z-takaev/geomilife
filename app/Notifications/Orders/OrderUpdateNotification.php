<?php

declare(strict_types=1);

namespace App\Notifications\Orders;

use App\Enums\Orders\OrderStatusEnum;
use App\Models\Order;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

final class OrderUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [10, 60, 300, 900];

    public function __construct(public Order $order, public string $event, public string $audience = 'BUYER')
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        $channels = [];
        if ($notifiable->routeNotificationFor('mail')) {
            $channels[] = 'mail';
        }
        if ($notifiable->routeNotificationFor('telegram')) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Заказ №:number — :event', ['number' => $this->order->number, 'event' => $this->message()]))
            ->greeting(__('Здравствуйте!'))
            ->line($this->message())
            ->line(__('Заказ №:number, сумма :total.', ['number' => $this->order->number, 'total' => $this->order->total->format()]))
            ->action(__('Открыть заказ'), $this->url())
            ->line(__('Спасибо за заказ в GeoMiLife!'));
    }

    public function toTelegram(object $notifiable): string
    {
        $text = __('Заказ №:number', ['number' => $this->order->number])."\n".$this->message()
            ."\n".__('Сумма: :total', ['total' => $this->order->total->format()]);
        if ($this->audience === 'MANAGER') {
            $text .= "\n".$this->order->customer_name."\n".$this->order->customer_phone;
        }
        if ($this->order->shipment?->cdek_number) {
            $text .= "\n".__('Трек-номер: :number', ['number' => $this->order->shipment->cdek_number]);
        }

        return $text;
    }

    private function message(): string
    {
        return match ($this->event) {
            'CREATED' => $this->audience === 'MANAGER' ? __('Поступил новый заказ.') : __('Ваш заказ принят.'),
            'PAYMENT_PAID', 'PAID' => __('Оплата заказа подтверждена.'),
            'REFUNDED' => __('Банк подтвердил полный возврат оплаты заказа.'),
            'REFUND_FAILED' => __('Возврат оплаты не завершён. Требуется проверка.'),
            'CANCELLATION_FAILED' => __('Не удалось завершить отмену заказа. Требуется проверка.'),
            'CANCELLATION_REJECTED' => __('СДЭК отклонил отмену отправления. Требуется проверка.'),
            'SHIPPING_FAILED', 'DOCUMENT_FAILED' => __('Ошибка операции СДЭК. Откройте заказ для проверки.'),
            'PAYMENT_RECONCILE_FAILED' => __('Не удалось сверить платёж с банком. Требуется проверка.'),
            default => OrderStatusEnum::tryFrom($this->event)?->getLabel() ?? __('Обновление заказа. Проверьте состояние в личном кабинете.'),
        };
    }

    private function url(): string
    {
        return URL::temporarySignedRoute('orders.guest.show',
            now()->addDays((int) config('orders.guest_access_days')), ['token' => $this->order->guest_token]);
    }
}
