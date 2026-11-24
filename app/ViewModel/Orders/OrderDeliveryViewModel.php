<?php

declare(strict_types=1);

namespace App\ViewModel\Orders;

use App\Models\Order;

final readonly class OrderDeliveryViewModel
{
    public string $label;

    public string $address;

    public ?string $workingHours;

    public string $priceLabel;

    public function __construct(Order $order)
    {
        $this->label = $order->delivery_method->getLabel();
        $this->address = $this->formatAddress($order);
        $this->workingHours = $order->delivery_address['working_hours']
            ?? $order->delivery_address['work_time']
            ?? null;
        $this->priceLabel = $order->delivery_price->minorAmount() === 0
            ? __('бесплатно')
            : $order->delivery_price->format();
    }

    private function formatAddress(Order $order): string
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

        return implode(', ', array_unique(array_filter($parts, static fn (mixed $value): bool => filled($value))));
    }
}
