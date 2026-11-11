<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Enums\Products\ProductUnitEnum;
use App\Filament\Resources\Cities\Pages\EditCity;
use App\Filament\Resources\Cities\Pages\ListCities;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Reviews\Pages\CreateReview;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

require_once __DIR__.'/../Support/FulfillmentFixtures.php';

beforeEach(function (): void {
    Storage::fake(config('media-library.disk_name'));
    Filament::setCurrentPanel('admin');
    $this->actingAs(fulfillmentEmployee(FilamentUserRoleEnum::ADMIN), 'filament');
});

function validFilamentVariant(array $attributes = []): array
{
    return array_replace([
        'sku' => 'UNIQUE-SKU', 'value' => 500, 'unit' => ProductUnitEnum::GRAM->value,
        'price' => 750, 'old_price' => null, 'weight' => 550, 'length' => 70,
        'width' => 70, 'height' => 120, 'is_active' => true, 'is_preorder' => false,
    ], $attributes);
}

function filamentProductWithImage(): ProductVariant
{
    $variant = CheckoutFixtures::variant(validFilamentVariant());
    $variant->product->addMedia(database_path('fixtures/images/Product/oil-product-card.png'))
        ->preservingOriginal()->toMediaCollection('image');

    return $variant;
}

it('сохраняет все настройки доставки города', function (): void {
    $city = CheckoutFixtures::city();
    $data = [
        'is_suggested' => true, 'is_cdek_excluded' => false,
        'pickup_name' => 'Новая точка', 'pickup_address' => 'Мира, 100',
        'pickup_working_hours' => 'Ежедневно', 'pickup_phone' => '+7 (999) 123-45-67',
        'courier_delivery_price' => 500, 'door_delivery_price' => 200, 'floor_lifting_price' => 80,
    ];
    Livewire::test(EditCity::class, ['record' => $city->id])->fillForm($data)->call('save')->assertHasNoFormErrors();
    $city->refresh();
    foreach (array_diff_key($data, array_flip(['courier_delivery_price', 'door_delivery_price', 'floor_lifting_price'])) as $field => $value) {
        expect($city->{$field})->toBe($value);
    }
    foreach (['courier_delivery_price', 'door_delivery_price', 'floor_lifting_price'] as $field) {
        expect($city->{$field}->minorAmount())->toBe($data[$field] * 100);
    }
});

it('показывает блоки доставки только при активных способах получения', function (): void {
    $city = CheckoutFixtures::city([
        'is_pickup_available' => false,
        'is_courier_available' => false,
    ]);
    $component = Livewire::test(EditCity::class, ['record' => $city->id])
        ->assertSchemaComponentHidden('pickup-fields')
        ->assertSchemaComponentHidden('courier-fields');

    $component->fillForm(['is_pickup_available' => true])
        ->assertSchemaComponentVisible('pickup-fields')
        ->assertSchemaComponentHidden('courier-fields');

    $component->fillForm(['is_courier_available' => true])
        ->assertSchemaComponentVisible('pickup-fields')
        ->assertSchemaComponentVisible('courier-fields');
});

it('проверяет реквизиты доставки в форме города', function (string $field, mixed $value, string $rule): void {
    $city = CheckoutFixtures::city();
    Livewire::test(EditCity::class, ['record' => $city->id])->fillForm([$field => $value])
        ->call('save')->assertHasFormErrors([$field => $rule]);
})->with([
    ['pickup_name', '', 'required'], ['pickup_address', '', 'required'],
    ['pickup_working_hours', '', 'required'], ['pickup_phone', '', 'required'],
    ['pickup_phone', 'не телефон', 'regex'], ['door_delivery_price', '', 'required'],
    ['courier_delivery_price', -1, 'min'], ['floor_lifting_price', '1.5', 'integer'],
]);

it('не включает доставку через таблицу без реквизитов', function (string $field, array $missing): void {
    $city = CheckoutFixtures::city([$field => false, ...$missing]);
    Livewire::test(ListCities::class)->call('updateTableColumnState', $field, (string) $city->id, true);
    expect($city->fresh()->{$field})->toBeFalse();
})->with([
    ['is_pickup_available', ['pickup_name' => null]],
    ['is_courier_available', ['courier_delivery_price' => null]],
]);

it('включает и выключает настроенную доставку через таблицу', function (string $field): void {
    $city = CheckoutFixtures::city([$field => false]);
    $component = Livewire::test(ListCities::class)->call('updateTableColumnState', $field, (string) $city->id, true);
    expect($city->fresh()->{$field})->toBeTrue();
    $component->call('updateTableColumnState', $field, (string) $city->id, false);
    expect($city->fresh()->{$field})->toBeFalse();
})->with(['is_pickup_available', 'is_courier_available']);

it('отклоняет повтор артикула внутри новых вариантов до записи', function (): void {
    $variant = filamentProductWithImage();
    Livewire::test(EditProduct::class, ['record' => $variant->product->getRouteKey()])->fillForm([
        'variants' => ['first' => validFilamentVariant(['sku' => 'DUPLICATE']), 'second' => validFilamentVariant(['sku' => 'DUPLICATE'])],
    ])->call('save')->assertHasFormErrors(['variants.first.sku' => 'Артикул должен быть уникальным среди вариантов товара.', 'variants.second.sku' => 'Артикул должен быть уникальным среди вариантов товара.']);
    expect($variant->product->variants()->count())->toBe(1);
});

it('сохраняет неизмененный артикул и целое количество без потери нулей', function (): void {
    $variant = filamentProductWithImage();
    Livewire::test(EditProduct::class, ['record' => $variant->product->getRouteKey()])->fillForm([
        'variants' => ['record-'.$variant->id => validFilamentVariant(['value' => 100])],
    ])->call('save')->assertHasNoFormErrors();
    expect($variant->fresh()->value)->toBe(100)->and($variant->fresh()->label())->toBe('100 г');
});

it('отклоняет дробное и неположительное количество', function (mixed $value, string $rule): void {
    $variant = filamentProductWithImage();
    Livewire::test(EditProduct::class, ['record' => $variant->product->getRouteKey()])->fillForm([
        'variants' => ['record-'.$variant->id => validFilamentVariant(['value' => $value])],
    ])->call('save')->assertHasFormErrors(['variants.record-'.$variant->id.'.value' => $rule]);
})->with([['1.5', 'integer'], ['0.001', 'integer'], ['1.0', 'integer'], [0, 'min'], [-1, 'min']]);

it('не принимает посторонние UUID вместо изображения и сохраняет прежнее медиа', function (string $source): void {
    $variant = filamentProductWithImage();
    $product = $variant->product;
    $original = $product->getFirstMedia('image');
    $uuid = match ($source) {
        'another' => CheckoutFixtures::variant()->product->addMedia(database_path('fixtures/images/Product/oil-product-card.png'))
            ->preservingOriginal()->toMediaCollection('image')->uuid,
        'gallery' => $product->addMedia(database_path('fixtures/images/Product/oil-product-card.png'))
            ->preservingOriginal()->toMediaCollection('gallery')->uuid,
        default => '00000000-0000-4000-8000-000000000000',
    };
    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])->set('data.image', [$uuid => $uuid])
        ->call('save')->assertHasFormErrors(['image']);
    expect($product->fresh()->getFirstMedia('image')->uuid)->toBe($original->uuid);
    Storage::disk($original->disk)->assertExists($original->getPathRelativeToRoot());
})->with(['another', 'gallery', 'missing']);

it('заменяет изображение новой загрузкой и сохраняет его при повторном сохранении', function (): void {
    $variant = filamentProductWithImage();
    $product = $variant->product;
    $oldUuid = $product->getFirstMedia('image')->uuid;
    $component = Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['image' => [UploadedFile::fake()->image('new.png')]])->call('save')->assertHasNoFormErrors();
    $uuid = $product->fresh()->getFirstMedia('image')->uuid;
    expect($uuid)->not->toBe($oldUuid);
    $component->call('save')->assertHasNoFormErrors();
    expect($product->fresh()->getFirstMedia('image')->uuid)->toBe($uuid);
});

it('не позволяет удалить обязательное изображение', function (): void {
    $variant = filamentProductWithImage();
    Livewire::test(EditProduct::class, ['record' => $variant->product->getRouteKey()])
        ->fillForm(['image' => []])->call('save')->assertHasFormErrors(['image' => 'required']);
    expect($variant->product->fresh()->hasMedia('image'))->toBeTrue();
});

it('создает товар с загрузкой и отклоняет неизвестный UUID при создании', function (bool $newUpload): void {
    $category = CheckoutFixtures::variant()->product->category;
    $component = Livewire::test(CreateProduct::class)->fillForm([
        'name' => 'Новый товар', 'category_id' => $category->id, 'is_active' => true,
    ])->set('data.variants', ['first' => validFilamentVariant()])->set('data.attributeValues', []);
    if ($newUpload) {
        $component->fillForm(['image' => [UploadedFile::fake()->image('new.png')]])->call('create')->assertHasNoFormErrors();
        expect(Product::query()->where('name', 'Новый товар')->sole()->hasMedia('image'))->toBeTrue();
    } else {
        $uuid = '00000000-0000-4000-8000-000000000000';
        $component->set('data.image', [$uuid => $uuid])->call('create')->assertHasFormErrors(['image']);
        expect(Product::query()->where('name', 'Новый товар')->exists())->toBeFalse();
    }
})->with([true, false]);

it('не округляет лишние десятичные знаки рейтинга отзыва', function (): void {
    Livewire::test(CreateReview::class)->fillForm([
        'author_name' => 'Покупатель', 'reviewed_at' => today()->toDateString(), 'rating' => '4.55', 'text' => 'Отзыв',
    ])->call('create')->assertHasFormErrors(['rating' => 'decimal']);
});

it('не записывает дробное и неположительное количество напрямую в базу', function (int|string $value): void {
    expect(fn () => DB::transaction(fn () => CheckoutFixtures::variant(['value' => $value])))
        ->toThrow(QueryException::class);
})->with([0, -1, '1.5', '1.0']);
