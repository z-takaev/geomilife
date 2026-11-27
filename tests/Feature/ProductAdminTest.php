<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Enums\Products\ProductUnitEnum;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
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

it('ограничивает значение характеристики 255 символами', function (int $length): void {
    $category = Category::query()->create(['name' => 'Категория', 'slug' => 'attributes-limit', 'is_active' => true]);
    $attribute = ProductAttribute::query()->create(['name' => 'Состав', 'slug' => 'composition']);

    $component = Livewire::test(CreateProduct::class)->fillForm([
        'image' => [UploadedFile::fake()->image('product.png')],
        'name' => 'Товар с характеристикой', 'category_id' => $category->id,
        'is_active' => true,
        'attributeValues' => [['product_attribute_id' => $attribute->id, 'value' => str_repeat('я', $length)]],
        'variants' => [[
            'sku' => 'ATTRIBUTE-LIMIT', 'value' => 100, 'unit' => ProductUnitEnum::GRAM->value,
            'price' => 500, 'weight' => 100, 'length' => 100, 'width' => 100, 'height' => 100,
            'is_active' => true, 'is_preorder' => false,
        ]],
    ])->call('create');

    if ($length === 255) {
        $component->assertHasNoFormErrors();
        expect(ProductAttributeValue::query()->sole()->value)->toHaveLength(255);
    } else {
        $component->assertHasFormErrors();
        expect(Product::query()->count())->toBe(0);
    }
})->with([255, 256]);
