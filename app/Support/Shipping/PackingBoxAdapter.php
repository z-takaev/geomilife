<?php

declare(strict_types=1);

namespace App\Support\Shipping;

use App\Models\ShippingBox;
use DVDoug\BoxPacker\Box;

final readonly class PackingBoxAdapter implements Box
{
    public function __construct(public ShippingBox $box, private int $maximumWeight) {}

    public function getReference(): string
    {
        return (string) $this->box->id;
    }

    public function getOuterWidth(): int
    {
        return (int) $this->box->width;
    }

    public function getOuterLength(): int
    {
        return (int) $this->box->length;
    }

    public function getOuterDepth(): int
    {
        return (int) $this->box->height;
    }

    public function getEmptyWeight(): int
    {
        return (int) $this->box->weight;
    }

    public function getInnerWidth(): int
    {
        return $this->getOuterWidth() - 10;
    }

    public function getInnerLength(): int
    {
        return $this->getOuterLength() - 10;
    }

    public function getInnerDepth(): int
    {
        return $this->getOuterDepth() - 10;
    }

    public function getMaxWeight(): int
    {
        return $this->maximumWeight + $this->getEmptyWeight();
    }
}
