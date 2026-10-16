<?php

declare(strict_types=1);

namespace App\Repositories\Products;

use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

final class ProductRepository
{
    /**
     * @return array{
     *     hitProducts: Collection<int, Product>,
     *     newProducts: Collection<int, Product>,
     *     discountedProducts: Collection<int, Product>
     * }
     */
    public function getHomeProducts(int $limit = 10): array
    {
        $productsQuery = Product::query()
            ->available();

        $hitProductIds = $productsQuery
            ->where('is_hit', true)
            ->latest('id')
            ->limit($limit)
            ->pluck('id');

        $newProductIds = $productsQuery
            ->where('is_new', true)
            ->latest('id')
            ->limit($limit)
            ->pluck('id');

        $discountedProductIds = Product::query()
            ->discounted()
            ->latest('id')
            ->limit($limit)
            ->pluck('id');

        $productIds = $hitProductIds
            ->concat($newProductIds)
            ->concat($discountedProductIds)
            ->unique()
            ->values();

        $products = Product::query()
            ->select(['id', 'name', 'is_new', 'is_hit'])
            ->active()
            ->with([
                'variants' => function (HasMany $query): void {
                    $query
                        ->select(['id', 'product_id', 'unit', 'value', 'price', 'discount_percent', 'status'])
                        ->active();
                },
                'media' => function (MorphMany $query): void {
                    $query->where('collection_name', 'image');
                },
            ])
            ->whereKey($productIds)
            ->get()
            ->keyBy('id');

        return [
            'hitProducts' => $this->restoreProductOrder($hitProductIds, $products),
            'newProducts' => $this->restoreProductOrder($newProductIds, $products),
            'discountedProducts' => $this->restoreProductOrder($discountedProductIds, $products),
        ];
    }

    /**
     * @param  Collection<int, int>  $productIds
     * @param  Collection<int, Product>  $products
     * @return Collection<int, Product>
     */
    private function restoreProductOrder(Collection $productIds, Collection $products): Collection
    {
        return $productIds
            ->map(static fn (int $productId): ?Product => $products->get($productId))
            ->filter()
            ->values();
    }
}
