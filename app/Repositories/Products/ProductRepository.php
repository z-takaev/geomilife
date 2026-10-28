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
    private const int SEARCH_PER_PAGE = 12;

    public function getDetails(int|string $identifier): ?Product
    {
        return Product::query()
            ->available()
            ->with(['activeVariants', 'attributeValues.productAttribute', 'category', 'media'])
            ->where(is_int($identifier) ? 'id' : 'slug', $identifier)
            ->first();
    }

    public function getHitProducts(int $limit = 10): Collection
    {
        return $this->cardQuery()
            ->where('is_hit', true)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function getNewProducts(int $limit = 10): Collection
    {
        return $this->cardQuery()
            ->where('is_new', true)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function getDiscountedProducts(int $limit = 10): Collection
    {
        return $this->cardQuery()
            ->discounted()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function getRecommendedProducts(Product $product, int $limit = 10): Collection
    {
        return $this->cardQuery()
            ->whereKeyNot($product->getKey())
            ->orderByRaw('case when category_id = ? then 0 else 1 end', [$product->category_id])
            ->orderByDesc('is_hit')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function search(string $searchQuery): LengthAwarePaginator
    {
        return $this->applySearch($this->cardQuery(), $searchQuery)
            ->latest('id')
            ->paginate(self::SEARCH_PER_PAGE)
            ->withQueryString();
    }

    public function searchSuggestions(string $searchQuery, int $limit = 5): Collection
    {
        if ($searchQuery === '') {
            return collect();
        }

        return $this->applySearch(Product::query()->available(), $searchQuery)
            ->select(['id', 'name', 'slug'])
            ->with(['media' => static fn (MorphMany $query): MorphMany => $query->where('collection_name', 'image')])
            ->latest('id')->limit($limit)->get();
    }

    public function cardQuery(): Builder
    {
        return Product::query()
            ->select(['products.id', 'name', 'slug', 'is_new', 'is_hit'])
            ->available()
            ->with([
                'activeVariants:id,product_id,unit,value,price,discount_percent,is_preorder',
                'media' => static fn (MorphMany $query): MorphMany => $query->where('collection_name', 'image'),
            ]);
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
