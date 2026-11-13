<?php

declare(strict_types=1);

namespace App\Notifications\Orders;

use App\Enums\Orders\OrderStatusEnum;
use App\Models\Order;
use App\Notifications\Channels\TelegramChannel;
use App\ViewModel\Orders\OrderViewModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        $mail = (new MailMessage)
            ->subject(__('Заказ №:number — :event', ['number' => $this->order->number, 'event' => $this->message()]))
            ->greeting(__('Здравствуйте!'))
            ->line($this->message())
            ->line(__('Заказ №:number, сумма :total.', ['number' => $this->order->number, 'total' => $this->order->total->format()]));

        if ($this->order->user_id !== null) {
            $mail->action(__('Открыть заказ'), route('orders.show', $this->order));
        }

        $mail->line(__('Спасибо за заказ в GeoMiLife!'));

        if ($this->event === 'CREATED' && $this->audience === 'BUYER') {
            $this->order->loadMissing('items');

            return $mail->markdown('mail.orders.created', [
                'order' => $this->order,
                'presentation' => OrderViewModel::confirmation($this->order),
            ]);
        }

        if ($this->event === 'SHIPPED' && filled($this->order->shipment?->cdek_number)) {
            $mail->line(__('Трек-номер СДЭК: :number', ['number' => $this->order->shipment->cdek_number]));
        }

        return $mail;
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
            'REFUNDED' => __('Банк подтвердил полный возврат оплаты заказа.'),
            'REFUND_FAILED' => __('Возврат оплаты не завершён. Требуется проверка.'),
            'CANCELLATION_FAILED' => __('Не удалось завершить отмену заказа. Требуется проверка.'),
            'CANCELLATION_REJECTED' => __('СДЭК отклонил отмену отправления. Требуется проверка.'),
            'SHIPPING_FAILED', 'DOCUMENT_FAILED' => __('Ошибка операции СДЭК. Откройте заказ для проверки.'),
            'PAYMENT_RECONCILE_FAILED' => __('Не удалось сверить платёж с банком. Требуется проверка.'),
            default => OrderStatusEnum::tryFrom($this->event)?->getLabel() ?? __('Обновление заказа. Проверьте состояние в личном кабинете.'),
        };
    }
}
