<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
            'imageUrls' => $this->imageUrls(),
        ]);
    }

    protected function getListeners(): array
    {
        return [
            'open-quick-view' => 'open',
        ];
    }

    private function imageUrls(): array
    {
        if ($this->product === null) {
            return [];
        }

        return $this->product
            ->getMedia('image')
            ->merge($this->product->getMedia('gallery'))
            ->map(static fn (Media $media): string => $media->getUrl())
            ->unique()
            ->values()
            ->all();
    }
}
