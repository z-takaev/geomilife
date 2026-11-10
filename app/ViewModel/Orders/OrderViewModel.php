<?php

declare(strict_types=1);

namespace App\ViewModel\Orders;

use App\Enums\Orders\OrderStatusEnum;
use App\Models\Order;
use App\Services\Orders\OrderLifecycleService;

final class OrderViewModel
{
    public static function processingStages(Order $order): array
    {
        $steps = app(OrderLifecycleService::class)->stages($order);
        $position = array_search($order->status, $steps, true);
        $stages = [];

        foreach ($steps as $index => $step) {
            $stages[] = [
                'label' => $step->getLabel(),
                'current' => $step->is($order->status),
                'color' => $step->is($order->status) ? 'primary' : ($position !== false && $index < $position ? 'success' : 'gray'),
            ];
        }

        if ($order->status->isCancelled()) {
            $stages[] = ['label' => OrderStatusEnum::CANCELLED->getLabel(), 'current' => true, 'color' => 'danger'];
        }

        return $stages;
    }

    public static function processingHint(Order $order): string
    {
        if ($order->status->isCancelled()) {
            return __('Заказ отменён. Дальнейшая обработка недоступна.');
        }

        if (in_array($order->cancellation_state, ['PENDING', 'FAILED'], true)) {
            return __('Выполняется отмена заказа. Изменение статуса недоступно.');
        }

        if ($order->status->isDelivered()) {
            return __('Заказ завершён.');
        }

        if ($order->payment_method->isOnline() && ! $order->payment_status->isPaid()) {
            return __('Ожидается онлайн-оплата. Обработка станет доступна после подтверждения оплаты банком.');
        }

        if ($order->delivery_method->isCdek() && in_array($order->status, [OrderStatusEnum::READY_TO_SHIP, OrderStatusEnum::SHIPPED], true)) {
            return __('Статусы отправки и доставки обновляются автоматически по данным СДЭК.');
        }

        if ($order->delivery_method->isPickup() && $order->status->isReadyForPickup()
            && ! $order->payment_status->isPaid()) {
            return __('Подтвердите получение оплаты перед выдачей заказа.');
        }

        return __('Проверьте заказ и выполните следующий шаг. Перед изменением статуса потребуется подтверждение.');
    }

    public static function transitionLabel(Order $order, OrderStatusEnum $status): string
    {
        return match ($status) {
            OrderStatusEnum::CONFIRMED => __('Подтвердить и начать сборку'),
            OrderStatusEnum::READY_FOR_PICKUP => __('Подготовить к выдаче'),
            OrderStatusEnum::READY_TO_SHIP => __('Подготовить к отправке'),
            OrderStatusEnum::DELIVERED => $order->delivery_method->isPickup() ? __('Подтвердить выдачу') : __('Подтвердить доставку'),
            default => $status->getLabel(),
        };
    }

    public static function transitionDescription(Order $order, OrderStatusEnum $status): string
    {
        $description = __(':from → :to. Покупателю будет отправлено уведомление.', [
            'from' => $order->status->getLabel(),
            'to' => $status->getLabel(),
        ]);

        if ($status->isReadyToShip()) {
            $description .= ' '.__('Будет создана заявка СДЭК. Убедитесь, что заказ собран и упакован.');
        }

        if ($status->isDelivered()) {
            $description .= ' '.__('Подтвердите, что покупатель получил заказ.');
        }

        return $description;
    }

    public static function cancellationDescription(Order $order): string
    {
        $description = __('Заказ будет отменён. Укажите причину отмены.');

        if ($order->delivery_method->isCdek()) {
            $description .= ' '.__('Будет отменена заявка СДЭК при её наличии. Отмена недоступна после движения груза.');
        }

        if ($order->payment_method->isOnline()) {
            $description .= ' '.($order->payment_status->isPaid()
                ? __('Будет возвращена полная онлайн-оплата: :amount.', ['amount' => $order->total->format()])
                : __('Если банк подтвердит оплату отменённого заказа, будет выполнен возврат.'));
        }

        return $description;
    }

    public static function address(Order $order): string
    {
        $snapshot = $order->delivery_address ?? [];
        $parts = [$snapshot['city'] ?? null];

        if ($order->delivery_method->isCourier() && ! empty($snapshot['street'])) {
            $parts[] = $snapshot['street'];

            foreach (['house' => __('дом'), 'building' => __('корпус'), 'entrance' => __('подъезд'), 'floor' => __('этаж'), 'apartment' => __('кв.'), 'intercom' => __('домофон')] as $field => $label) {
                if (filled($snapshot[$field] ?? null)) {
                    $parts[] = $label.' '.$snapshot[$field];
                }
            }
        } else {
            $parts[] = $snapshot['address'] ?? $snapshot['street'] ?? null;
        }

        return implode(', ', array_unique(array_filter($parts, static fn ($value): bool => filled($value))));
    }

    public static function deliveryLabel(Order $order): string
    {
        return $order->delivery_method->getLabel();
    }

    public static function confirmation(Order $order): array
    {
        $presentation = self::data($order);
        $dateFormat = 'd.m.Y H:i';
        $timezone = config('app.timezone');

        return $presentation + [
            'createdAtLabel' => $order->created_at->timezone($timezone)->format($dateFormat),
            'deliveryHint' => match (true) {
                $order->is_preorder => __('Точные сроки доставки согласовываются отдельно с менеджером'),
                $order->delivery_method->isPickup() => __('Приходите после уведомления о готовности. Время получения согласует менеджер.'),
                $order->delivery_method->isCourier() => __('Время доставки согласует менеджер.'),
                default => __('Трек-номер появится после подготовки отправления.'),
            },
            'nextStep' => match (true) {
                $order->payment_method->isOnline() && ! $order->payment_status->isPaid() => self::processingHint($order),
                default => __('Мы сообщим вам об изменении статуса заказа отдельным письмом.'),
            },
        ];
    }

    public static function data(Order $order): array
    {
        $order->loadMissing('shipment');

        if (array_key_exists('items_sum_quantity', $order->getAttributes())) {
            $quantity = (int) $order->getAttribute('items_sum_quantity');
        } else {
            $order->loadMissing('items');
            $quantity = $order->items->sum('quantity');
        }

        $canCancel = $order->user_id !== null && $order->status->isNew() && $order->cancellation_state === null;
        $steps = app(OrderLifecycleService::class)->stages($order);
        $position = array_search($order->status, $steps, true);
        $stages = [];

        foreach ($steps as $index => $step) {
            $stages[] = ['label' => $step->getLabel(), 'done' => $position !== false && $index <= $position];
        }

        return [
            'deliveryLabel' => self::deliveryLabel($order),
            'deliveryPriceLabel' => $order->delivery_price->minorAmount() === 0 ? __('бесплатно') : $order->delivery_price->format(),
            'addressLabel' => self::address($order),
            'paymentLabel' => $order->payment_method->getLabel(),
            'canCancel' => $canCancel,
            'stages' => $stages,
            'cancelUrl' => route('orders.cancel', $order),
            'quantity' => $quantity,
            'workingHours' => $order->delivery_address['working_hours'] ?? $order->delivery_address['work_time'] ?? null,
            'isCancelling' => in_array($order->cancellation_state, ['PENDING', 'FAILED'], true),
            'isActive' => ! in_array($order->status, [OrderStatusEnum::CANCELLED, OrderStatusEnum::DELIVERED], true)
                || $order->payment_status->isRefundPending(),
        ];
    }
}
