<?php

declare(strict_types=1);

namespace App\Repositories\Products;

use App\Enums\ProductUnit;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

final class ProductRepository
{
    private const int CATALOG_PER_PAGE = 9;

    private const int SEARCH_PER_PAGE = 12;

    private const string CURRENT_PRICE_EXPRESSION = '((product_variants.price * (100 - COALESCE(product_variants.discount_percent, 0)) + 50) / 100)';

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function getCatalogProducts(?Category $category = null, int $perPage = 9): LengthAwarePaginator
    {
        return $this->getCatalogQuery($category)
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @param  array{
     *     priceFrom: string,
     *     priceTo: string,
     *     availability: list<string>,
     *     packageValues: list<string>
     * }  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function getFilteredCatalogProducts(
        ?Category $category,
        array $filters,
        string $sort,
        int $page,
        int $additionalPages = 0,
    ): LengthAwarePaginator {
        $variantCriteria = $this->variantCriteria($filters);
        $query = $this->getCatalogQuery($category);

        $this->applyFilters($query, $variantCriteria);

        $representativeVariantQuery = $this->representativeVariantQuery($variantCriteria);

        $query->addSelect([
            'catalog_variant_id' => (clone $representativeVariantQuery)->select('id'),
            'catalog_price' => (clone $representativeVariantQuery)->selectRaw(self::CURRENT_PRICE_EXPRESSION),
        ]);

        $total = (clone $query)->reorder()->count('products.id');
        $page = max(1, $page);
        $additionalPages = max(0, $additionalPages);

        $this->applySorting($query, $sort);

        $products = $query
            ->offset(($page - 1) * self::CATALOG_PER_PAGE)
            ->limit(self::CATALOG_PER_PAGE * ($additionalPages + 1))
            ->get();

        return (new Paginator(
            items: $products,
            total: $total,
            perPage: self::CATALOG_PER_PAGE,
            currentPage: $page,
            options: [
                'path' => request()->url(),
                'pageName' => 'page',
            ],
        ))->withQueryString();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function getCatalogPackageOptions(Category $category): array
    {
        $categoryIds = $this->getCategoryIds($category);
        $unitOrder = array_flip(array_column(ProductUnit::cases(), 'value'));

        return ProductVariant::query()
            ->select(['unit', 'value'])
            ->active()
            ->whereHas(
                'product',
                static fn (Builder $query): Builder => $query
                    ->active()
                    ->whereIn('category_id', $categoryIds),
            )
            ->distinct()
            ->get()
            ->sortBy(static fn (ProductVariant $variant): string => sprintf(
                '%02d-%020.3f',
                $unitOrder[$variant->unit->value],
                (float) $variant->value,
            ))
            ->map(static fn (ProductVariant $variant): array => [
                'value' => $variant->unit->value.'|'.$variant->value,
                'label' => $variant->label(),
            ])
            ->values()
            ->all();
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

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function search(string $searchQuery): LengthAwarePaginator
    {
        return $this->getSearchQuery($searchQuery)
            ->latest('id')
            ->paginate(self::SEARCH_PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Product>
     */
    public function searchSuggestions(string $searchQuery, int $limit = 5): Collection
    {
        if (trim($searchQuery) === '') {
            return new Collection;
        }

        return $this->getSearchQuery($searchQuery)
            ->latest('id')
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

    private function getSearchQuery(string $searchQuery): Builder
    {
        $searchQuery = trim($searchQuery);
        $query = $this->getProductCardsQuery();

        if ($searchQuery === '') {
            return $query->whereKey([]);
        }

        return $query->where(static function (Builder $query) use ($searchQuery): void {
            $query
                ->whereLike('name', '%'.$searchQuery.'%')
                ->orWhereLike('description', '%'.$searchQuery.'%');
        });
    }

    private function getCatalogQuery(?Category $category): Builder
    {
        return Product::query()
            ->select(['id', 'name', 'slug', 'is_new', 'is_hit'])
            ->available()
            ->when(
                $category,
                fn (Builder $query, Category $category): Builder => $query->whereIn(
                    'category_id',
                    $this->getCategoryIds($category),
                ),
            )
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

    /**
     * @param  array{
     *     priceFrom: int|null,
     *     priceTo: int|null,
     *     availability: list<string>,
     *     packageValues: list<array{unit: string, value: string}>
     * }  $variantCriteria
     */
    private function applyFilters(Builder $query, array $variantCriteria): void
    {
        if (! $this->hasVariantCriteria($variantCriteria)) {
            return;
        }

        $query->whereHas('variants', function (Builder $variantQuery) use ($variantCriteria): void {
            $variantQuery->active();
            $this->applyVariantCriteria($variantQuery, $variantCriteria);
        });
    }

    /**
     * @param  array{
     *     priceFrom: int|null,
     *     priceTo: int|null,
     *     availability: list<string>,
     *     packageValues: list<array{unit: string, value: string}>
     * }  $variantCriteria
     */
    private function representativeVariantQuery(array $variantCriteria): Builder
    {
        $query = ProductVariant::query()
            ->whereColumn('product_variants.product_id', 'products.id')
            ->active();

        $this->applyVariantCriteria($query, $variantCriteria);

        if (count($variantCriteria['availability']) !== 1) {
            $query->orderBy('is_preorder');
        }

        return $query
            ->orderByRaw(self::CURRENT_PRICE_EXPRESSION)
            ->orderBy('order')
            ->orderBy('id')
            ->limit(1);
    }

    private function applySorting(Builder $query, string $sort): void
    {
        match ($sort) {
            'new' => $query->orderByDesc('is_new')->orderByDesc('id'),
            'price-asc' => $query->orderBy('catalog_price')->orderBy('id'),
            'price-desc' => $query->orderByDesc('catalog_price')->orderByDesc('id'),
            'name' => $query->orderBy('name')->orderBy('id'),
            default => $query->orderByDesc('is_hit')->orderByDesc('id'),
        };
    }

    /**
     * @param  array{
     *     priceFrom: string,
     *     priceTo: string,
     *     availability: list<string>,
     *     packageValues: list<string>
     * }  $filters
     * @return array{
     *     priceFrom: int|null,
     *     priceTo: int|null,
     *     availability: list<string>,
     *     packageValues: list<array{unit: string, value: string}>
     * }
     */
    private function variantCriteria(array $filters): array
    {
        return [
            'priceFrom' => $this->priceInMinor($filters['priceFrom']),
            'priceTo' => $this->priceInMinor($filters['priceTo']),
            'availability' => array_values(array_intersect(['in-stock', 'preorder'], $filters['availability'])),
            'packageValues' => $this->parsePackageValues($filters['packageValues']),
        ];
    }

    /**
     * @param  array{
     *     priceFrom: int|null,
     *     priceTo: int|null,
     *     availability: list<string>,
     *     packageValues: list<array{unit: string, value: string}>
     * }  $variantCriteria
     */
    private function hasVariantCriteria(array $variantCriteria): bool
    {
        return $variantCriteria['priceFrom'] !== null
            || $variantCriteria['priceTo'] !== null
            || $variantCriteria['availability'] !== []
            || $variantCriteria['packageValues'] !== [];
    }

    /**
     * @param  array{
     *     priceFrom: int|null,
     *     priceTo: int|null,
     *     availability: list<string>,
     *     packageValues: list<array{unit: string, value: string}>
     * }  $variantCriteria
     */
    private function applyVariantCriteria(Builder $query, array $variantCriteria): void
    {
        if ($variantCriteria['priceFrom'] !== null) {
            $query->whereRaw(self::CURRENT_PRICE_EXPRESSION.' >= ?', [$variantCriteria['priceFrom']]);
        }

        if ($variantCriteria['priceTo'] !== null) {
            $query->whereRaw(self::CURRENT_PRICE_EXPRESSION.' <= ?', [$variantCriteria['priceTo']]);
        }

        if (count($variantCriteria['availability']) === 1) {
            $query->where('is_preorder', $variantCriteria['availability'][0] === 'preorder');
        }

        if ($variantCriteria['packageValues'] !== []) {
            $query->where(static function (Builder $packageQuery) use ($variantCriteria): void {
                foreach ($variantCriteria['packageValues'] as $packageValue) {
                    $packageQuery->orWhere(static fn (Builder $optionQuery): Builder => $optionQuery
                        ->where('unit', $packageValue['unit'])
                        ->where('value', $packageValue['value']));
                }
            });
        }
    }

    /**
     * @param  list<string>  $values
     * @return list<array{unit: string, value: string}>
     */
    private function parsePackageValues(array $values): array
    {
        $allowedUnits = array_column(ProductUnit::cases(), 'value');
        $result = [];

        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }

            [$unit, $packageValue] = array_pad(explode('|', $value, 2), 2, null);

            if (! in_array($unit, $allowedUnits, true)
                || ! is_string($packageValue)
                || preg_match('/^\d+(?:\.\d{1,3})?$/', $packageValue) !== 1) {
                continue;
            }

            $result[] = ['unit' => $unit, 'value' => $packageValue];
        }

        return $result;
    }

    private function priceInMinor(string $price): ?int
    {
        if ($price === '' || preg_match('/^\d{1,10}$/', $price) !== 1) {
            return null;
        }

        return (int) $price * 100;
    }

    /**
     * @return Collection<int, int>
     */
    private function getCategoryIds(Category $category): Collection
    {
        return $category->descendantsAndSelf()->active()->pluck('id');
    }
}
