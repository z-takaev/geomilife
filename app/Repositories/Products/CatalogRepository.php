<?php

declare(strict_types=1);

namespace App\Repositories\Products;

use App\DTO\Products\CatalogFiltersDto;
use App\Enums\ProductUnit;
use App\Models\Category;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class CatalogRepository
{
    private const int PER_PAGE = 9;

    private const string PRICE_IN_RUBLES = 'ROUND(ROUND(product_variants.price * (100 - COALESCE(product_variants.discount_percent, 0)) / 100.0) / 100)';

    public function __construct(private readonly ProductRepository $products) {}

    public function getProducts(
        ?Category $category,
        CatalogFiltersDto $filters,
        string $sort,
        int $page,
        int $additionalPages = 0,
    ): LengthAwarePaginator {
        $variants = $this->matchingVariants($filters);
        $query = $this->products->cardQuery()
            ->when($category, fn (Builder $query): Builder => $query->whereIn('category_id', $this->categoryIds($category)));
        $total = (clone $query)->whereExists(clone $variants)->count();

        $variants->selectRaw('id as catalog_variant_id, '.self::PRICE_IN_RUBLES.' as catalog_price');

        match ($sort) {
            'price-asc' => $variants->orderBy('catalog_price'),
            'price-desc' => $variants->orderByDesc('catalog_price'),
            default => null,
        };

        $variants->orderBy('order')->orderBy('id')->limit(1);
        $query->joinLateral($variants, 'selected_variant')
            ->addSelect(['selected_variant.catalog_variant_id', 'selected_variant.catalog_price']);
        $this->sortProducts($query, $sort);

        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $page), $lastPage);
        $additionalPages = min(max(0, $additionalPages), $lastPage - $page);
        $products = $query->offset(($page - 1) * self::PER_PAGE)
            ->limit(self::PER_PAGE * ($additionalPages + 1))
            ->get();

        return new LengthAwarePaginator($products, $total, self::PER_PAGE, $page);
    }

    public function getPackageOptions(Category $category): array
    {
        return once(function () use ($category): array {
            $categoryIds = $this->categoryIds($category);
            $unitOrder = array_flip(array_column(ProductUnit::cases(), 'value'));

            return ProductVariant::query()
                ->select(['unit', 'value'])
                ->active()
                ->whereHas('product', static fn (Builder $query): Builder => $query
                    ->active()
                    ->whereIn('category_id', $categoryIds)
                )
                ->distinct()
                ->get()
                ->sortBy(static fn (ProductVariant $variant): string => sprintf(
                    '%02d-%020.3f', $unitOrder[$variant->unit->value], (float) $variant->value,
                ))
                ->map(static fn (ProductVariant $variant): array => [
                    'value' => $variant->unit->value.'|'.$variant->value,
                    'label' => $variant->label(),
                ])
                ->values()
                ->all();
        });
    }

    private function matchingVariants(CatalogFiltersDto $filters): Builder
    {
        $query = ProductVariant::query()->active()->whereColumn('product_id', 'products.id');

        if ($filters->priceFrom !== null) {
            $query->whereRaw(self::PRICE_IN_RUBLES.' >= ?', [$filters->priceFrom]);
        }

        if ($filters->priceTo !== null) {
            $query->whereRaw(self::PRICE_IN_RUBLES.' <= ?', [$filters->priceTo]);
        }

        if (count($filters->availability) === 1) {
            $query->where('is_preorder', $filters->availability[0] === 'preorder');
        }

        if ($filters->packageValues !== []) {
            $query->where(static function (Builder $query) use ($filters): void {
                foreach ($filters->packageValues as $package) {
                    [$unit, $value] = explode('|', $package);
                    $query->orWhere(static fn (Builder $query): Builder => $query->where('unit', $unit)->where('value', $value));
                }
            });
        }

        return $query;
    }

    private function sortProducts(Builder $query, string $sort): void
    {
        match ($sort) {
            'popular' => $query->orderByDesc('is_hit')->orderByDesc('products.id'),
            'new' => $query->orderByDesc('is_new')->orderByDesc('products.id'),
            'price-asc' => $query->orderBy('catalog_price')->orderBy('products.id'),
            'price-desc' => $query->orderByDesc('catalog_price')->orderByDesc('products.id'),
            'name' => $query->orderBy('name')->orderBy('products.id'),
            default => $query->orderBy('products.id'),
        };
    }

    private function categoryIds(Category $category): Collection
    {
        return once(static fn (): Collection => $category->descendantsAndSelf()->visible()->pluck('id'));
    }
}
