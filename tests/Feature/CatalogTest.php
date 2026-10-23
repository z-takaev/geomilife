<?php

declare(strict_types=1);

use App\Enums\ProductUnit;
use App\Livewire\CatalogProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Values\Money;
use Livewire\Livewire;

function createCatalogCategory(string $name, string $slug, bool $isActive = true, ?Category $parent = null): Category
{
    return Category::query()->create([
        'parent_id' => $parent?->getKey(),
        'name' => $name,
        'slug' => $slug,
        'is_active' => $isActive,
    ]);
}

function createCatalogProduct(
    Category $category,
    string $name,
    string $slug,
    int $price,
    bool $isActive = true,
    bool $variantIsActive = true,
    bool $isPreorder = false,
    int $discountPercent = 0,
): Product {
    $product = Product::query()->create([
        'category_id' => $category->getKey(),
        'name' => $name,
        'slug' => $slug,
        'is_active' => $isActive,
    ]);

    ProductVariant::query()->create([
        'product_id' => $product->getKey(),
        'sku' => 'SKU-'.$product->getKey(),
        'unit' => ProductUnit::GRAM,
        'value' => 500,
        'price' => Money::fromRubles($price),
        'discount_percent' => $discountPercent > 0 ? $discountPercent : null,
        'is_active' => $variantIsActive,
        'is_preorder' => $isPreorder,
    ]);

    return $product;
}

it('показывает в каталоге только доступные товары', function (): void {
    $category = createCatalogCategory('Масла', 'oils');
    createCatalogProduct($category, 'Доступное масло', 'available-oil', 500);
    createCatalogProduct($category, 'Скрытое масло', 'hidden-oil', 500, false);
    createCatalogProduct($category, 'Без активной фасовки', 'unavailable-oil', 500, true, false);

    $this->get(route('catalog.index'))
        ->assertOk()
        ->assertSee('Доступное масло')
        ->assertDontSee('Скрытое масло')
        ->assertDontSee('Без активной фасовки');
});

it('открывает только доступный товар', function (): void {
    $category = createCatalogCategory('Масла', 'oils');
    $availableProduct = createCatalogProduct($category, 'Доступное масло', 'available-oil', 500);
    $inactiveProduct = createCatalogProduct($category, 'Скрытое масло', 'hidden-oil', 500, false);
    $productWithoutActiveVariant = createCatalogProduct($category, 'Без фасовки', 'without-variant', 500, true, false);

    $this->get(route('products.show', $availableProduct))->assertOk();
    $this->get(route('products.show', $inactiveProduct))->assertNotFound();
    $this->get(route('products.show', $productWithoutActiveVariant))->assertNotFound();
});

it('не открывает категорию с неактивным родителем', function (): void {
    $parent = createCatalogCategory('Родитель', 'parent', false);
    $category = createCatalogCategory('Дочерняя', 'child', true, $parent);

    $this->get(route('catalog.category', $category))->assertNotFound();
});

it('фильтрует каталог по цене, наличию и фасовке', function (): void {
    $category = createCatalogCategory('Масла', 'oils');
    createCatalogProduct($category, 'Подходящий товар', 'matching-product', 1000, true, true, false, 20);
    createCatalogProduct($category, 'Предзаказ', 'preorder-product', 800, true, true, true);
    createCatalogProduct($category, 'Дорогой товар', 'expensive-product', 2000);

    Livewire::test(CatalogProducts::class, ['category' => $category])
        ->set('priceFrom', '700')
        ->set('priceTo', '900')
        ->set('availability', ['in-stock'])
        ->set('packageValues', ['GRAM|500.000'])
        ->call('applyFilters')
        ->assertHasNoErrors()
        ->assertViewHas(
            'products',
            static fn ($products): bool => $products->pluck('name')->all() === ['Подходящий товар'],
        );
});

it('проверяет диапазон цены и сбрасывает фильтры', function (): void {
    Livewire::test(CatalogProducts::class)
        ->set('priceFrom', '1000')
        ->set('priceTo', '500')
        ->call('applyFilters')
        ->assertHasErrors('priceTo')
        ->call('resetFilters')
        ->assertSet('priceFrom', '')
        ->assertSet('priceTo', '')
        ->assertSet('availability', [])
        ->assertSet('sort', 'popular')
        ->assertHasNoErrors();
});
