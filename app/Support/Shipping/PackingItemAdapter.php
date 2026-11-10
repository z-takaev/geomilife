<?php

declare(strict_types=1);

namespace App\Support\Shipping;

use DVDoug\BoxPacker\Item;
use DVDoug\BoxPacker\Rotation;

final readonly class PackingItemAdapter implements Item
{
    public function __construct(public array $itemData) {}

    public function getDescription(): string
    {
        return (string) $this->itemData['product_variant_id'];
    }

    public function getWidth(): int
    {
        return $this->itemData['width'];
    }

    public function getLength(): int
    {
        return $this->itemData['length'];
    }

    public function getDepth(): int
    {
        return $this->itemData['height'];
    }

    public function getWeight(): int
    {
        return $this->itemData['weight'];
    }

    public function getAllowedRotation(): Rotation
    {
        return Rotation::BestFit;
    }
}
