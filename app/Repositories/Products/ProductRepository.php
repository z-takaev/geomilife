<?php

declare(strict_types=1);

namespace App\Repositories\Products;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

final class ProductRepository
{
    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function getCatalogProducts(int $perPage = 9): LengthAwarePaginator
    {
        return Product::query()
            ->select(['id', 'name', 'slug', 'is_new', 'is_hit'])
            ->available()
            ->with([
                'variants' => function (HasMany $query): void {
                    $query
                        ->select(['id', 'product_id', 'unit', 'value', 'price', 'discount_percent', 'is_preorder'])
                        ->active();
                },
                'media' => function (MorphMany $query): void {
                    $query->where('collection_name', 'image');
                },
            ])
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, Product>
     */
    public function getHitProducts(int $limit = 10): Collection
    {
        return $this->getProductCardsQuery()
            ->where('is_hit', true)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function getNewProducts(int $limit = 10): Collection
    {
        return $this->getProductCardsQuery()
            ->where('is_new', true)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function getDiscountedProducts(int $limit = 10): Collection
    {
        return $this->getProductCardsQuery()
            ->discounted()
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function getRecommendedProducts(Product $product, int $limit = 10): Collection
    {
        return $this->getProductCardsQuery()
            ->whereKeyNot($product->getKey())
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    private function getProductCardsQuery(): Builder
    {
        return Product::query()
            ->select(['id', 'name', 'slug', 'is_new', 'is_hit'])
            ->available()
            ->with([
                'variants' => function (HasMany $query): void {
                    $query
                        ->select(['id', 'product_id', 'unit', 'value', 'price', 'discount_percent', 'is_preorder'])
                        ->active();
                },
                'media' => function (MorphMany $query): void {
                    $query->where('collection_name', 'image');
                },
            ]);
    }
}
