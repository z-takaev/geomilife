<?php

declare(strict_types=1);

namespace App\Repositories\Products;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ProductRepository
{
    public function getProduct(int|string $identifier): ?Product
    {
        return Product::query()
            ->available()
            ->with(['activeVariants', 'attributeValues.productAttribute', 'category', 'media'])
            ->where(is_int($identifier) ? 'id' : 'slug', $identifier)
            ->first();
    }

    public function getHitProducts(int $limit = 10): Collection
    {
        return Product::query()
            ->forCard()
            ->where('is_hit', true)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getNewProducts(int $limit = 10): Collection
    {
        return Product::query()
            ->forCard()
            ->where('is_new', true)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getDiscountedProducts(int $limit = 10): Collection
    {
        return Product::query()
            ->forCard()
            ->discounted()
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getRecommendedProducts(Product $product, int $limit = 10): Collection
    {
        return Product::query()
            ->forCard()
            ->whereKeyNot($product->getKey())
            ->orderByRaw('case when category_id = ? then 0 else 1 end', [$product->category_id])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function search(string $searchQuery): LengthAwarePaginator
    {
        return $this->applySearch(Product::query()->forCard(), $searchQuery)
            ->latest()
            ->paginate(config('pagination.per_page.search'))
            ->withQueryString();
    }

    public function searchSuggestions(string $searchQuery, int $limit = 5): Collection
    {
        if ($searchQuery === '') {
            return new Collection;
        }

        return $this->applySearch(Product::query()->available(), $searchQuery)
            ->select(['id', 'name', 'slug'])
            ->with(['media' => static fn (MorphMany $query): MorphMany => $query->where('collection_name', 'image')])
            ->latest()
            ->limit($limit)
            ->get();
    }

    private function applySearch(Builder $query, string $searchQuery): Builder
    {
        if ($searchQuery === '') {
            return $query->whereKey([]);
        }

        $pattern = '%'.addcslashes($searchQuery, '\\%_').'%';

        return $query->where(static fn (Builder $query): Builder => $query
            ->whereLike('name', $pattern)->orWhereLike('description', $pattern));
    }
}
