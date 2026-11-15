<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Enums\Products\ProductUnitEnum;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\ShippingBoxes\Pages\EditShippingBox;
use App\Models\Category;
use App\Models\FilamentUser;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingBox;
use App\Support\Values\Money;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake(config('media-library.disk_name'));
    Filament::setCurrentPanel('admin');
    $this->actingAs(FilamentUser::query()->create([
        'name' => 'Администратор',
        'email' => 'packaging-admin@example.test',
        'password' => 'password',
        'role' => FilamentUserRoleEnum::ADMIN,
    ]), 'filament');
});

function packagingBox(array $attributes = []): ShippingBox
{
    return ShippingBox::query()->create(array_replace([
        'name' => 'Основная коробка',
        'length' => 110,
        'width' => 110,
        'height' => 110,
        'weight' => 10,
        'is_active' => true,
    ], $attributes));
}

function packagingVariant(array $attributes = []): ProductVariant
{
    $category = Category::query()->firstOrCreate([
        'slug' => 'packaging-validation',
    ], [
        'name' => 'Проверка упаковки',
        'is_active' => true,
    ]);
    $product = Product::query()->create([
        'category_id' => $category->getKey(),
        'name' => 'Проверяемый товар',
        'slug' => 'packaging-product-'.uniqid(),
        'is_active' => true,
    ]);

    return $product->variants()->create(array_replace([
        'sku' => 'PACKAGING-'.uniqid(),
        'unit' => ProductUnitEnum::PIECE,
        'value' => 1,
        'price' => Money::fromRubles(1000),
        'weight' => 100,
        'length' => 100,
        'width' => 100,
        'height' => 100,
        'is_active' => true,
        'is_preorder' => false,
    ], $attributes));
}

function packagingProductForm(array $variant): array
{
    $category = Category::query()->create([
        'name' => 'Категория товара',
        'slug' => 'packaging-product-category-'.uniqid(),
        'is_active' => true,
    ]);

    return [
        'image' => [UploadedFile::fake()->image('product.png')],
        'name' => 'Товар для проверки упаковки',
        'category_id' => $category->getKey(),
        'is_active' => true,
        'variants' => [[
            'sku' => 'PRODUCT-PACKAGING',
            'value' => 1,
            'unit' => ProductUnitEnum::PIECE->value,
            'price' => 1000,
            'weight' => 100,
            'is_active' => true,
            'is_preorder' => false,
            ...$variant,
        ]],
    ];
}

it('не создает активный вариант который не помещается ни в одну упаковку', function (): void {
    packagingBox();

    Livewire::test(CreateProduct::class)
        ->fillForm(packagingProductForm([
            'length' => 101,
            'width' => 100,
            'height' => 100,
        ]))
        ->call('create')
        ->assertNotified('Товар нельзя сохранить');

    $this->assertDatabaseCount('products', 0);
});

it('учитывает поворот варианта при создании товара', function (): void {
    packagingBox([
        'length' => 210,
        'width' => 110,
        'height' => 60,
    ]);

    Livewire::test(CreateProduct::class)
        ->fillForm(packagingProductForm([
            'length' => 50,
            'width' => 200,
            'height' => 100,
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseCount('products', 1);
});

it('не уменьшает упаковку если активный вариант перестанет помещаться', function (): void {
    $box = packagingBox();
    $variant = packagingVariant();

    Livewire::test(EditShippingBox::class, ['record' => $box->getKey()])
        ->fillForm(['length' => 109])
        ->call('save')
        ->assertNotified('Упаковку нельзя изменить');

    expect($box->fresh()->length)->toBe(110)
        ->and($variant->fresh()->is_active)->toBeTrue();
});

it('не отключает упаковку если активный вариант останется без упаковки', function (): void {
    $box = packagingBox();
    packagingVariant();

    Livewire::test(EditShippingBox::class, ['record' => $box->getKey()])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertNotified('Упаковку нельзя изменить');

    expect($box->fresh()->is_active)->toBeTrue();
});

it('не удаляет упаковку если активный вариант останется без упаковки', function (): void {
    $box = packagingBox();
    packagingVariant();

    Livewire::test(EditShippingBox::class, ['record' => $box->getKey()])
        ->callAction(TestAction::make('delete'))
        ->assertNotified('Упаковку нельзя удалить');

    $this->assertDatabaseHas('shipping_boxes', ['id' => $box->getKey()]);
});

it('разрешает изменить и удалить упаковку если вариант помещается в другую', function (): void {
    $box = packagingBox(['name' => 'Первая коробка']);
    packagingBox(['name' => 'Вторая коробка']);
    packagingVariant();

    Livewire::test(EditShippingBox::class, ['record' => $box->getKey()])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($box->fresh()->is_active)->toBeFalse();

    Livewire::test(EditShippingBox::class, ['record' => $box->getKey()])
        ->callAction(TestAction::make('delete'));

    $this->assertDatabaseMissing('shipping_boxes', ['id' => $box->getKey()]);
});
