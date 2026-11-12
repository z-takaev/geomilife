<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Enums\Products\ProductUnitEnum;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

require_once __DIR__.'/../Support/FulfillmentFixtures.php';

beforeEach(function (): void {
    Storage::fake(config('media-library.disk_name'));
    Filament::setCurrentPanel('admin');
    $this->actingAs(fulfillmentEmployee(FilamentUserRoleEnum::ADMIN), 'filament');
});

it('создает товар без характеристик и открывает его редактирование', function (): void {
    $category = Category::query()->create([
        'name' => 'Категория товара',
        'slug' => 'product-category',
        'is_active' => true,
    ]);

    $component = Livewire::test(CreateProduct::class)
        ->fillForm([
            'image' => [UploadedFile::fake()->image('product.png')],
            'name' => 'Товар без характеристик',
            'category_id' => $category->getKey(),
            'is_active' => true,
            'variants' => [[
                'sku' => 'PRODUCT-WITHOUT-ATTRIBUTES',
                'value' => 100,
                'unit' => ProductUnitEnum::GRAM->value,
                'price' => 500,
                'weight' => 100,
                'length' => 100,
                'width' => 100,
                'height' => 100,
                'is_active' => true,
                'is_preorder' => false,
            ]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::query()->where('name', 'Товар без характеристик')->sole();

    expect($product->attributeValues)->toBeEmpty();
    $component->assertRedirect(ProductResource::getUrl('edit', ['record' => $product]));
});
