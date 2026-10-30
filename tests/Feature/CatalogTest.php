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
        ->assertSet('sort', '')
        ->assertHasNoErrors();
});

it('сортирует по цене со скидкой, а не по исходной цене', function (string $sort, array $names): void {
    $category = createCatalogCategory('Масла', 'oils');
    createCatalogProduct($category, 'Со скидкой', 'discount', 1000, discountPercent: 50);
    createCatalogProduct($category, 'Без скидки', 'regular', 700);
    Livewire::test(CatalogProducts::class)->set('sort', $sort)
        ->assertViewHas('products', fn ($products): bool => $products->pluck('name')->all() === $names);
})->with([
    ['PRICE_ASC', ['Со скидкой', 'Без скидки']],
    ['PRICE_DESC', ['Без скидки', 'Со скидкой']],
]);

it('не объединяет условия фильтра из разных фасовок одного товара', function (): void {
    $category = createCatalogCategory('Масла', 'oils');
    $product = createCatalogProduct($category, 'Разные фасовки', 'packages', 100);
    $product->variants()->first()->update(['value' => 100]);
    $product->variants()->create(['sku' => 'LARGE', 'unit' => ProductUnit::GRAM, 'value' => 500,
        'price' => Money::fromRubles(1000), 'is_active' => true]);
    Livewire::test(CatalogProducts::class, ['category' => $category])
        ->set('priceTo', '200')->set('packageValues', ['GRAM|500.000'])->call('applyFilters')
        ->assertHasNoErrors()->assertViewHas('products', fn ($products): bool => $products->isEmpty());
});

it('показывает товары дочерних категорий и исключает соседние', function (): void {
    $parent = createCatalogCategory('Родитель', 'parent');
    $child = createCatalogCategory('Дочерняя', 'child', parent: $parent);
    $other = createCatalogCategory('Другая', 'other');
    createCatalogProduct($child, 'Дочерний товар', 'child-product', 500);
    createCatalogProduct($other, 'Чужой товар', 'other-product', 500);
    Livewire::test(CatalogProducts::class, ['category' => $parent])
        ->assertViewHas('products', fn ($products): bool => $products->pluck('name')->all() === ['Дочерний товар']);
});

it('догружает товары без дублей и сбрасывает страницу при фильтрации', function (): void {
    config(['pagination.per_page.catalog' => 2]);
    $category = createCatalogCategory('Масла', 'oils');
    $ids = [];
    for ($index = 1; $index <= 5; $index++) {
        $ids[] = createCatalogProduct($category, 'Товар '.$index, 'product-'.$index, 100 * $index)->id;
    }
    Livewire::test(CatalogProducts::class)->assertViewHas('products', fn ($products): bool => $products->count() === 2)
        ->call('loadMore')->assertViewHas('products', fn ($products): bool => $products->pluck('id')->all() === array_slice($ids, 0, 4))
        ->call('loadMore')->assertViewHas('products', fn ($products): bool => $products->pluck('id')->all() === $ids)
        ->assertViewHas('hasMoreProducts', false)
        ->set('priceFrom', '400')->call('applyFilters')->assertSet('additionalPages', 0)
        ->assertViewHas('products', fn ($products): bool => $products->pluck('id')->all() === array_slice($ids, 3));
});

it('выбирает безопасную фасовку при чужом или отключенном идентификаторе', function (string $selection): void {
    $category = createCatalogCategory('Масла', 'oils');
    $product = createCatalogProduct($category, 'Масло', 'oil', 500);
    $default = $product->variants()->first();
    $otherProduct = createCatalogProduct($category, 'Другой', 'other', 900);
    $hidden = $product->variants()->create(['sku' => 'HIDDEN', 'unit' => ProductUnit::GRAM, 'value' => 1000,
        'price' => Money::fromRubles(10), 'is_active' => false]);
    $variantId = $selection === 'foreign' ? $otherProduct->variants()->first()->id : $hidden->id;
    $this->get(route('products.show', [$product, 'variant' => $variantId]))->assertOk()
        ->assertViewHas('productViewModel', fn ($viewModel): bool => $viewModel->selectedVariant['id'] === $default->id);
})->with(['foreign', 'inactive']);

it('публикует в SEO цену, валюту и наличие выбранной фасовки', function (): void {
    $category = createCatalogCategory('Масла', 'oils');
    $product = createCatalogProduct($category, 'Масло', 'oil', 1000, isPreorder: true, discountPercent: 25);
    $variant = $product->variants()->first();
    $this->get(route('products.show', [$product, 'variant' => $variant->id]))->assertOk();
    $schema = collect(seo()->getSchemas())->firstWhere('@type', 'Product');
    expect($schema['sku'])->toBe($variant->sku)
        ->and($schema['offers']['price'])->toBe('750.00')
        ->and($schema['offers']['priceCurrency'])->toBe('RUB')
        ->and($schema['offers']['availability'])->toBe('https://schema.org/PreOrder')
        ->and($schema['offers']['url'])->toBe(route('products.show', [$product, 'variant' => $variant->id]));
});
