<?php

declare(strict_types=1);

use App\Enums\Products\ProductUnitEnum;
use App\Models\Category;
use App\Models\City;
use App\Models\Product;
use App\Support\Values\Money;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

it('подключает общие интерактивные компоненты по новым именам', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSeeLivewire('favorites')
        ->assertSeeLivewire('city-selector')
        ->assertSeeLivewire('product-quick-view');
});

it('не выводит сообщение заказа на главной странице', function (): void {
    $this->withSession(['order_message' => 'Сообщение заказа'])
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('Сообщение заказа');
});

it('не позволяет вводить количество товара вручную', function (): void {
    expect(Blade::render('<x-products.quantity-selector compact />'))
        ->toContain('readonly')
        ->not->toContain('x-on:change');
});

it('ограничивает заголовки карточек новостей и статей двумя строками', function (): void {
    expect(Blade::render('<x-news.card date="15 ноября" description="Описание" image="/news.jpg" title="Новость" />'))
        ->toContain('entry_title h5 text-line-clamp-2')
        ->and(Blade::render('<x-articles.card image="/article.jpg" title="Статья" />'))
        ->toContain('article-list-title h5 text-line-clamp-2');
});

it('показывает в копирайте текущий год', function (): void {
    $this->travelTo(now()->setDate(2031, 4, 5));

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('«Geomilife» 2031 - производитель масла');
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
        'unit' => ProductUnitEnum::MILLILITER,
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

it('не показывает в быстром просмотре пустые сведения о товаре', function (): void {
    $category = Category::query()->create(['name' => 'Масла', 'slug' => 'empty-details', 'is_active' => true]);
    $product = Product::query()->create([
        'category_id' => $category->getKey(),
        'name' => 'Товар без сведений',
        'slug' => 'product-without-details',
        'is_active' => true,
    ]);
    $variant = $product->variants()->create([
        'sku' => 'WITHOUT-DETAILS',
        'unit' => ProductUnitEnum::MILLILITER,
        'value' => 250,
        'price' => Money::fromRubles(500),
        'is_active' => true,
    ]);

    Livewire::test('product-quick-view')
        ->dispatch('open-quick-view', productId: $product->getKey(), variantId: $variant->getKey())
        ->assertDontSee('Описание отсутствует')
        ->assertDontSee('Характеристики отсутствуют')
        ->assertDontSeeHtml('quick-view-tabs');
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
