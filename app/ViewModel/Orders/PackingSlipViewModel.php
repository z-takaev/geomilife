<?php

declare(strict_types=1);

namespace App\ViewModel\Orders;

use App\Models\Order;

final readonly class PackingSlipViewModel
{
    public function __construct(private Order $order) {}

    public function data(): array
    {
        $order = $this->order;
        $order->loadMissing('items', 'shipment');
        $items = $order->items->keyBy('product_variant_id');
        $packages = [];
        foreach ($order->packages ?? [] as $package) {
            $contents = [];
            foreach ($package['items'] as $packedItem) {
                $id = $packedItem['product_variant_id'];
                $snapshot = $items->get($id);
                $contents[$id] ??= [
                    'label' => trim(($snapshot?->product_name ?? __('Товар')).' '.($snapshot?->variant_name ?? '')),
                    'sku' => $snapshot?->sku ?? (string) $id,
                    'quantity' => 0,
                ];
                $contents[$id]['quantity'] += $packedItem['quantity'];
            }
            $packages[] = [...$package, 'contents' => array_values($contents)];
        }
        $address = OrderViewModel::address($order);
        $deliveryLabel = OrderViewModel::deliveryLabel($order);

        return compact('order', 'packages', 'address', 'deliveryLabel');
    }
}
