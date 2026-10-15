<?php

declare(strict_types=1);

namespace App\Repositories\Products;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

final class ProductRepository
{
    /**
     * @return Collection<int, Product>
     */
    public function getHits(int $limit = 10): Collection
    {
        return $this->query()
            ->where('is_hit', true)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function getNewProducts(int $limit = 10): Collection
    {
        return $this->query()
            ->where('is_new', true)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function getDiscountedProducts(int $limit = 10): Collection
    {
        return $this->query()
            ->whereHas('variants', function (Builder $query): void {
                $query
                    ->where('is_active', true)
                    ->where('discount_percent', '>', 0);
            })
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Builder<Product>
     */
    private function query(): Builder
    {
        return Product::query()
            ->select(['id', 'name', 'is_new', 'is_hit'])
            ->where('is_active', true)
            ->whereHas('variants', function (Builder $query): void {
                $query->where('is_active', true);
            })
            ->with([
                'variants' => function (HasMany $query): void {
                    $query
                        ->select(['id', 'product_id', 'unit', 'value', 'price', 'discount_percent'])
                        ->where('is_active', true);
                },
                'media' => function (MorphMany $query): void {
                    $query
                        ->select([
                            'id',
                            'model_type',
                            'model_id',
                            'collection_name',
                            'file_name',
                            'disk',
                            'conversions_disk',
                            'manipulations',
                            'generated_conversions',
                            'order_column',
                        ])
                        ->where('collection_name', 'image');
                },
            ]);
    }
}
