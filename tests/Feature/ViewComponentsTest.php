<?php

declare(strict_types=1);

use App\Enums\ProductUnit;
use App\Models\Category;
use App\Models\City;
use App\Models\Product;
use App\Support\Values\Money;
use Livewire\Livewire;

it('подключает общие интерактивные компоненты по новым именам', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSeeLivewire('favorites')
        ->assertSeeLivewire('city-selector')
        ->assertSeeLivewire('product-quick-view');
});

it('открывает быстрый просмотр товара с выбранной фасовкой', function (): void {
    $category = Category::query()->create(['name' => 'Масла', 'slug' => 'oils', 'is_active' => true]);
    $product = Product::query()->create([
        'category_id' => $category->getKey(),
        'name' => 'Льняное масло',
        'slug' => 'flaxseed-oil',
        'description' => 'Описание льняного масла',
        'is_active' => true,
        'is_hit' => true,
    ]);
    $variant = $product->variants()->create([
        'sku' => 'FLAXSEED-OIL-250',
        'unit' => ProductUnit::MILLILITER,
        'value' => 250,
        'price' => Money::fromRubles(890),
        'is_active' => true,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee($product->name)
        ->assertSee("Livewire.dispatchTo('product-quick-view', 'open-quick-view'", false);

    Livewire::test('product-quick-view')
        ->dispatch('open-quick-view', productId: $product->getKey(), variantId: $variant->getKey())
        ->assertSet('productId', $product->getKey())
        ->assertSet('variantId', $variant->getKey())
        ->assertSee($product->name)
        ->assertSee($product->description)
        ->assertSee('890 ₽');
});

it('находит город и сохраняет выбор через переименованный компонент', function (): void {
    $city = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => true]);

    Livewire::test('city-selector')
        ->set('query', 'Моск')
        ->assertSee($city->name)
        ->call('select', $city->getKey())
        ->assertSet('query', '')
        ->assertDispatched('city-selected', id: $city->getKey(), name: $city->name);

    expect(session('city.id'))->toBe($city->getKey());
});
