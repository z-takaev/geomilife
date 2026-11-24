<?php

declare(strict_types=1);

namespace App\ViewModel\Orders;

use App\DTO\Orders\PackedPackageDto;
use App\Models\Order;
use App\Models\OrderItem;
use LogicException;

final readonly class PackingSlipViewModel
{
    /**
     * @var list<array<string, mixed>>
     */
    public array $packages;

    public string $address;

    public string $deliveryLabel;

    public function __construct(public Order $order)
    {
        if (! $order->relationLoaded('items') || ! $order->relationLoaded('shipment')) {
            throw new LogicException('Связи items и shipment должны быть загружены до создания PackingSlipViewModel.');
        }

        $delivery = new OrderDeliveryViewModel($order);

        $this->packages = $this->makePackages($order);
        $this->address = $delivery->address;
        $this->deliveryLabel = $delivery->label;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function makePackages(Order $order): array
    {
        $items = $order->items->keyBy('product_variant_id');
        $packages = [];

        foreach ($order->packages ?? [] as $packageData) {
            $package = PackedPackageDto::fromArray($packageData);
            $contents = [];

            foreach ($package->items as $packedItem) {
                $id = $packedItem->productVariantId;
                /** @var ?OrderItem $snapshot */
                $snapshot = $items->get($id);

                $contents[$id] ??= [
                    'label' => trim(($snapshot?->product_name ?? __('Товар')).' '.($snapshot?->variant_name ?? '')),
                    'sku' => $snapshot?->sku ?? (string) $id,
                    'quantity' => 0,
                ];

                $contents[$id]['quantity'] += $packedItem->quantity;
            }

            $packages[] = [...$package->toArray(), 'contents' => array_values($contents)];
        }

        return $packages;
    }
}
