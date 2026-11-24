<?php

declare(strict_types=1);

namespace App\ViewModel\Orders;

use App\Models\Order;

final readonly class OrderConfirmationViewModel
{
    public OrderDeliveryViewModel $delivery;

    public string $createdAtLabel;

    public string $deliveryHint;

    public function __construct(Order $order)
    {
        $this->delivery = new OrderDeliveryViewModel($order);
        $this->createdAtLabel = $order->created_at
            ->timezone(config('app.timezone'))
            ->format('d.m.Y H:i');
        $this->deliveryHint = match (true) {
            $order->is_preorder => __('Точные сроки доставки согласовываются отдельно с менеджером'),
            $order->delivery_method->isPickup() => __('Вы сможете забрать заказ по указанному адресу после уведомления о готовности.'),
            $order->delivery_method->isCourier() => __('Время доставки согласует менеджер.'),
            default => __('Мы сообщим вам, когда заказ будет передан в СДЭК.'),
        };
    }
}
