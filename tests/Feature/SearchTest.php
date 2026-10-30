<?php

declare(strict_types=1);

use App\Enums\ProductUnit;
use App\Livewire\SearchSuggestions;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Values\Money;
use Livewire\Livewire;

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

    $this->get(route('search', ['q' => 'масло']))
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

    $this->get(route('search', ['q' => '   ']))
        ->assertOk()
        ->assertSee('Не задан')
        ->assertDontSee('Льняное масло');
});

it('не интерпретирует символы поиска как SQL-шаблон', function (string $query): void {
    $category = Category::query()->create(['name' => 'Масла', 'slug' => 'oils', 'is_active' => true]);
    createSearchProduct($category, 'Масло', 'oil');
    $this->get(route('search', ['q' => $query]))->assertOk()
        ->assertViewHas('products', fn ($products): bool => $products->isEmpty());
})->with(['%', '_']);

it('подсказки исключают скрытые товары и ограничивают количество результатов', function (): void {
    $category = Category::query()->create(['name' => 'Масла', 'slug' => 'oils', 'is_active' => true]);
    for ($index = 1; $index <= 7; $index++) {
        createSearchProduct($category, 'Масло '.$index, 'oil-'.$index);
    }
    createSearchProduct($category, 'Масло скрытое', 'hidden', false);
    Livewire::test(SearchSuggestions::class)->set('query', 'Масло')
        ->assertViewHas('products', fn ($products): bool => $products->count() === 5 && ! $products->contains('slug', 'hidden'))
        ->call('submit')->assertRedirect(route('search', ['q' => 'Масло']));
});
