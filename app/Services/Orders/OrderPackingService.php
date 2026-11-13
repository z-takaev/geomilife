<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\ShippingBox;
use App\Support\Shipping\PackingBoxAdapter;
use App\Support\Shipping\PackingItemAdapter;
use DVDoug\BoxPacker\Packer;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class OrderPackingService
{
    /**
     * Распределяет товары по доступным коробкам и проверяет ограничения СДЭК.
     */
    public function pack(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages(['delivery' => __('В заказе нет товаров для упаковки.')]);
        }

        $itemsWeightGrams = 0;
        $itemCount = 0;

        foreach ($items as $item) {
            foreach (['product_variant_id', 'quantity', 'length', 'width', 'height', 'weight'] as $field) {
                if (! isset($item[$field]) || ! is_int($item[$field]) || $item[$field] < 1) {
                    throw ValidationException::withMessages(['delivery' => __('Заполните вес и габариты всех товаров перед расчётом СДЭК.')]);
                }
            }

            $itemsWeightGrams += $item['weight'] * $item['quantity'];
            $itemCount += $item['quantity'];
        }

        $boxes = ShippingBox::query()
            ->where('is_active', true)
            ->orderByRaw('CAST(length AS UNSIGNED) * width * height')
            ->orderBy('id')
            ->get();

        if ($boxes->isEmpty()) {
            throw ValidationException::withMessages(['delivery' => __('Нет доступных коробок для отправки СДЭК.')]);
        }

        $packer = new Packer;

        foreach ($boxes as $box) {
            if (min($box->length, $box->width, $box->height) > 10 && $box->weight > 0) {
                $packer->addBox(new PackingBoxAdapter($box, $itemsWeightGrams));
            }
        }

        foreach ($items as $item) {
            $packer->addItem(new PackingItemAdapter($item), $item['quantity']);
        }

        try {
            $packedBoxes = $packer->pack();
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['delivery' => __('Товары не помещаются в доступные коробки. Выберите другой способ получения.')]);
        }

        $packages = $this->packageSnapshots($packedBoxes);
        $packedItemCount = array_sum(array_map(static fn (array $package): int => count($package['items']), $packages));

        if ($packedItemCount !== $itemCount || $packages === []) {
            throw ValidationException::withMessages(['delivery' => __('Не удалось упаковать все товары заказа.')]);
        }

        $this->assertCdekLimits($packages);

        return $packages;
    }

    private function packageSnapshots(iterable $packedBoxes): array
    {
        $packages = [];

        foreach ($packedBoxes as $packedBox) {
            $boxAdapter = $packedBox->box;
            $contents = [];

            foreach ($packedBox->items as $packedItem) {
                $contents[] = array_replace($packedItem->item->itemData, [
                    'quantity' => 1,
                    'x' => $packedItem->x,
                    'y' => $packedItem->y,
                    'z' => $packedItem->z,
                    'packed_width' => $packedItem->width,
                    'packed_length' => $packedItem->length,
                    'packed_height' => $packedItem->depth,
                ]);
            }

            $packages[] = [
                'box_id' => $boxAdapter->box->id,
                'box_name' => $boxAdapter->box->name,
                'number' => (string) (count($packages) + 1),
                'length' => $boxAdapter->getOuterLength(),
                'width' => $boxAdapter->getOuterWidth(),
                'height' => $boxAdapter->getOuterDepth(),
                'inner_length' => $boxAdapter->getInnerLength(),
                'inner_width' => $boxAdapter->getInnerWidth(),
                'inner_height' => $boxAdapter->getInnerDepth(),
                'packaging_weight' => $boxAdapter->getEmptyWeight(),
                'weight' => $packedBox->getWeight(),
                'items' => $contents,
            ];
        }

        return $packages;
    }

    private function assertCdekLimits(array $packages): void
    {
        if (count($packages) > 255) {
            throw ValidationException::withMessages(['delivery' => __('Заказ превышает допустимое число грузовых мест СДЭК.')]);
        }

        foreach ($packages as $package) {
            $positions = collect($package['items'])->groupBy('product_variant_id')
                ->sum(static fn ($items): int => (int) ceil($items->sum('quantity') / 999));

            if ($positions > 126) {
                throw ValidationException::withMessages(['delivery' => __('В коробке слишком много товарных позиций для отправления СДЭК.')]);
            }
        }
    }
}
