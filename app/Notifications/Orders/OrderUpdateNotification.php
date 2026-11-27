<?php

declare(strict_types=1);

namespace App\Notifications\Orders;

use App\Enums\Orders\OrderNotificationAudienceEnum;
use App\Enums\Orders\OrderNotificationEventEnum;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Notifications\Channels\TelegramChannel;
use App\ViewModel\Orders\OrderConfirmationViewModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [10, 60, 300, 900];

    public function __construct(
        public Order $order,
        public OrderNotificationEventEnum $event,
        public OrderNotificationAudienceEnum $audience = OrderNotificationAudienceEnum::BUYER,
    ) {
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
        if ($this->audience === OrderNotificationAudienceEnum::MANAGER) {
            $mail = (new MailMessage)
                ->subject(__('Новый заказ №:number', ['number' => $this->order->number]))
                ->greeting('')
                ->action(__('Открыть заказ'), OrderResource::getUrl('view', ['record' => $this->order], panel: 'admin'));

            if ($this->event === OrderNotificationEventEnum::CREATED) {
                return $mail->markdown('mail.orders.manager-created', ['order' => $this->order]);
            }

            return $mail->line($this->managerTelegramMessage());
        }

        if ($this->event === OrderNotificationEventEnum::CREATED && $this->audience === OrderNotificationAudienceEnum::BUYER) {
            $this->order->loadMissing('items');

            $mail = (new MailMessage)
                ->subject($this->mailSubject());

            if ($this->order->user_id !== null) {
                $mail->action(__('Открыть заказ'), route('orders.show', $this->order));
            }

            return $mail->markdown('mail.orders.created', [
                'order' => $this->order,
                'viewModel' => new OrderConfirmationViewModel($this->order),
            ]);
        }

        $mail = (new MailMessage)
            ->subject($this->mailSubject())
            ->greeting('')
            ->line($this->message());

        if ($this->event === OrderNotificationEventEnum::CANCELLED && filled($this->order->cancellation_reason)) {
            $mail->line(__('Причина: :reason', ['reason' => $this->order->cancellation_reason]));
        }

        if ($this->event === OrderNotificationEventEnum::SHIPPED && filled($this->order->shipment?->cdek_number)) {
            $mail->line(__('Трек-номер СДЭК: :number', ['number' => $this->order->shipment->cdek_number]));
        }

        if ($this->order->user_id !== null) {
            $mail->action(__('Открыть заказ'), route('orders.show', $this->order));
        }

        return $mail;
    }

    public function toTelegram(object $notifiable): string
    {
        if ($this->audience === OrderNotificationAudienceEnum::MANAGER) {
            return $this->managerTelegramMessage();
        }

        $text = $this->message();

        if ($this->event === OrderNotificationEventEnum::CANCELLED && filled($this->order->cancellation_reason)) {
            $text .= "\n".__('Причина: :reason', ['reason' => $this->order->cancellation_reason]);
        }

        if ($this->event === OrderNotificationEventEnum::SHIPPED && filled($this->order->shipment?->cdek_number)) {
            $text .= "\n".__('Трек-номер СДЭК: :number', ['number' => $this->order->shipment->cdek_number]);
        }

        return $text;
    }

    public function telegramAction(): ?array
    {
        $url = match (true) {
            $this->audience === OrderNotificationAudienceEnum::MANAGER => OrderResource::getUrl('view', ['record' => $this->order], panel: 'admin'),
            $this->order->user_id !== null => route('orders.show', $this->order),
            default => null,
        };

        return $url === null ? null : ['text' => __('Открыть заказ'), 'url' => $url];
    }

    private function message(): string
    {
        return match ($this->event) {
            OrderNotificationEventEnum::CREATED => __('Ваш заказ №:number принят.', ['number' => $this->order->number]),
            OrderNotificationEventEnum::CONFIRMED => __('Мы подтвердили заказ №:number и начали его собирать.', ['number' => $this->order->number]),
            OrderNotificationEventEnum::READY_FOR_PICKUP => __('Заказ №:number готов к выдаче. Вы сможете забрать его по указанному адресу.', ['number' => $this->order->number]),
            OrderNotificationEventEnum::READY_TO_SHIP => __('Заказ №:number собран и готовится к передаче в СДЭК.', ['number' => $this->order->number]),
            OrderNotificationEventEnum::SHIPPED => __('Заказ №:number передан в СДЭК.', ['number' => $this->order->number]),
            OrderNotificationEventEnum::DELIVERED => __('Заказ №:number доставлен.', ['number' => $this->order->number]),
            OrderNotificationEventEnum::CANCELLED => __('Ваш заказ №:number был отменён.', ['number' => $this->order->number]),
            OrderNotificationEventEnum::REFUNDED => __('Возврат оплаты по заказу №:number подтверждён банком.', ['number' => $this->order->number]),
            OrderNotificationEventEnum::REFUND_FAILED => __('Возврат оплаты не завершён. Требуется проверка.'),
            OrderNotificationEventEnum::CANCELLATION_FAILED => __('Не удалось завершить отмену заказа. Требуется проверка.'),
            OrderNotificationEventEnum::CANCELLATION_REJECTED => __('СДЭК отклонил отмену отправления. Требуется проверка.'),
            OrderNotificationEventEnum::SHIPPING_FAILED,
            OrderNotificationEventEnum::DOCUMENT_FAILED => __('Ошибка операции СДЭК. Откройте заказ для проверки.'),
            OrderNotificationEventEnum::PAYMENT_RECONCILE_FAILED => __('Не удалось сверить платёж с банком. Требуется проверка.'),
        };
    }

    private function mailSubject(): string
    {
        return __('GeoMiLife — заказ №:number', ['number' => $this->order->number]);
    }

    private function managerTelegramMessage(): string
    {
        if ($this->event === OrderNotificationEventEnum::CREATED) {
            return implode("\n", [
                __('Новый заказ №:number', ['number' => $this->order->number]),
                __('Покупатель: :name', ['name' => $this->order->customer_name]),
                __('Телефон: :phone', ['phone' => $this->order->customer_phone]),
                __('Email: :email', ['email' => $this->order->customer_email]),
                __('Получение: :method', ['method' => $this->order->delivery_method->getLabel()]),
                __('Оплата: :method', ['method' => $this->order->payment_method->getLabel()]),
                __('Сумма: :total', ['total' => $this->order->total->format()]),
            ]);
        }

        return implode("\n", [
            __('Заказ №:number', ['number' => $this->order->number]),
            $this->message(),
            __('Покупатель: :name', ['name' => $this->order->customer_name]),
            __('Телефон: :phone', ['phone' => $this->order->customer_phone]),
        ]);
    }
}
