<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Component;

final class QuickView extends Component
{
    public ?Product $product = null;

    public bool $hasDiscount = false;

    public function open(int $productId): void
    {
        $this->product = Product::query()
            ->available()
            ->with([
                'attributeValues.productAttribute',
                'category',
                'media',
                'variants' => static function (HasMany $query): void {
                    $query->active();
                },
            ])
            ->findOrFail($productId);

        $this->hasDiscount = $this->product
            ->variants()
            ->active()
            ->where('discount_percent', '>', 0)
            ->exists();
    }

    public function render(): View
    {
        return view('livewire.quick-view', [
            'images' => $this->product?->images() ?? collect(),
            'selectedVariant' => $this->product?->variants->first(),
            'variantOptions' => $this->product?->variantOptions() ?? [],
        ]);
    }

    protected function getListeners(): array
    {
        return [
            'open-quick-view' => 'open',
        ];
    }
}
