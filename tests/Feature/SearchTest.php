<?php

declare(strict_types=1);

use App\Enums\ProductUnit;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Values\Money;

function createSearchProduct(Category $category, string $name, string $slug, bool $isActive = true): Product
{
    $product = Product::query()->create([
        'category_id' => $category->getKey(),
        'name' => $name,
        'slug' => $slug,
        'description' => 'Натуральный продукт из Осетии',
        'is_active' => $isActive,
    ]);

    ProductVariant::query()->create([
        'product_id' => $product->getKey(),
        'sku' => 'SEARCH-'.$product->getKey(),
        'unit' => ProductUnit::PIECE,
        'value' => 1,
        'price' => Money::fromRubles(500),
        'is_active' => true,
    ]);

    return $product;
}

it('ищет активные категории и доступные товары', function (): void {
    $category = Category::query()->create([
        'name' => 'Натуральные масла',
        'slug' => 'natural-oils',
        'is_active' => true,
    ]);
    createSearchProduct($category, 'Льняное масло', 'linseed-oil');
    createSearchProduct($category, 'Скрытое масло', 'hidden-oil', false);

    $this->get(route('search.index', ['q' => 'масло']))
        ->assertOk()
        ->assertSee('Натуральные масла')
        ->assertSee('Льняное масло')
        ->assertDontSee('Скрытое масло');
});

it('возвращает пустой результат для пустого запроса', function (): void {
    $category = Category::query()->create([
        'name' => 'Масла',
        'slug' => 'oils',
        'is_active' => true,
    ]);
    createSearchProduct($category, 'Льняное масло', 'linseed-oil');

    $this->get(route('search.index', ['q' => '   ']))
        ->assertOk()
        ->assertSee('Не задан')
        ->assertDontSee('Льняное масло');
});
