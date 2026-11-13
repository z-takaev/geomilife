<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Enums\Products\ProductUnitEnum;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Cities\Pages\EditCity;
use App\Filament\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Resources\FilamentUsers\Pages\CreateFilamentUser;
use App\Filament\Resources\News\Pages\CreateNews;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Promos\Pages\CreatePromo;
use App\Filament\Resources\Reviews\Pages\CreateReview;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\City;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
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

it('не принимает будущую дату отзыва', function (): void {
    Livewire::test(CreateReview::class)->fillForm([
        'author_name' => 'Покупатель', 'reviewed_at' => today()->addDay()->toDateString(), 'rating' => '5', 'text' => 'Отзыв',
    ])->call('create')->assertHasFormErrors(['reviewed_at' => 'before_or_equal']);
});

it('проверяет телефон пользователя на сервере', function (): void {
    Livewire::test(CreateUser::class)->fillForm([
        'first_name' => 'Покупатель', 'email' => 'buyer@example.com', 'phone' => 'не телефон', 'password' => 'Password123',
    ])->call('create')->assertHasFormErrors(['phone' => 'regex']);
});

it('показывает регион в вариантах выбора города и ищет по названию и региону', function (): void {
    $city = new City([
        'name' => 'Москва',
        'region' => 'Псковская область',
        'sub_region' => 'Порховский район',
    ]);

    Livewire::test(CreateUser::class)
        ->assertFormFieldExists('city_id', fn (Select $field): bool => $field->getOptionLabelFromRecord($city) === 'Москва, Псковская область'
            && $field->getSearchColumns() === ['name', 'region']);
});

it('ограничивает длину паролей в административных формах', function (string $component): void {
    Livewire::test($component)->fillForm([
        'first_name' => 'Покупатель', 'name' => 'Администратор', 'email' => 'person@example.com',
        'password' => str_repeat('a', 51), 'password_confirmation' => str_repeat('a', 51),
    ])->call('create')->assertHasFormErrors(['password' => 'max']);
})->with([[CreateUser::class], [CreateFilamentUser::class]]);

it('проверяет максимальные значения варианта товара', function (string $field, int $value): void {
    $variant = filamentProductWithImage();
    Livewire::test(EditProduct::class, ['record' => $variant->product->getRouteKey()])->fillForm([
        'variants' => ['record-'.$variant->id => validFilamentVariant([$field => $value])],
    ])->call('save')->assertHasFormErrors(['variants.record-'.$variant->id.'.'.$field => 'max']);
})->with([
    ['price', 100000], ['old_price', 100000], ['weight', 100000],
    ['length', 5001], ['width', 5001], ['height', 5001],
]);

it('ограничивает товар десятью вариантами', function (): void {
    $variant = filamentProductWithImage();
    $variants = [];
    foreach (range(1, 11) as $index) {
        $variants['variant-'.$index] = validFilamentVariant(['sku' => 'SKU-'.$index]);
    }

    Livewire::test(EditProduct::class, ['record' => $variant->product->getRouteKey()])
        ->fillForm(['variants' => $variants])
        ->call('save')
        ->assertHasFormErrors(['variants' => 'max']);
});

it('ограничивает целевую страницу пятью изображениями', function (): void {
    $page = Page::query()->create([
        'key' => 'validation', 'title' => 'Проверка', 'slug' => 'validation', 'content' => 'Текст', 'is_active' => true,
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertFormFieldExists('images', fn ($field): bool => $field->getMaxFiles() === 5);
});

it('задает ограничения текстовым полям и вложениям редактора', function (): void {
    $page = Page::query()->create([
        'key' => 'content-limits', 'title' => 'Проверка', 'slug' => 'content-limits', 'content' => 'Текст', 'is_active' => true,
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertFormFieldExists('content', fn ($field): bool => $field->getMaxLength() === 50000
            && $field->getFileAttachmentsMaxSize() === 5120);
    foreach ([CreateArticle::class, CreateNews::class, CreatePromo::class] as $component) {
        Livewire::test($component)
            ->assertFormFieldExists('excerpt', fn ($field): bool => $field->getMaxLength() === 500)
            ->assertFormFieldExists('content', fn ($field): bool => $field->getMaxLength() === 50000
                && $field->getFileAttachmentsMaxSize() === 5120);
    }
    Livewire::test(CreateFaq::class)
        ->assertFormFieldExists('answer', fn ($field): bool => $field->getMaxLength() === 9999
            && $field->getFileAttachmentsMaxSize() === 5120);
    Livewire::test(CreateReview::class)
        ->assertFormFieldExists('text', fn ($field): bool => $field->getMaxLength() === 5000);
    Livewire::test(CreateProduct::class)
        ->assertFormFieldExists('description', fn ($field): bool => $field->getMaxLength() === 5000)
        ->assertFormFieldExists('variants', fn ($field): bool => $field->getMaxItems() === 10);
});

it('проверяет уникальность email после нормализации регистра', function (): void {
    User::query()->create(['first_name' => 'Первый', 'email' => 'buyer@example.com', 'password' => 'Password123']);

    Livewire::test(CreateUser::class)->fillForm([
        'first_name' => 'Второй', 'email' => ' BUYER@EXAMPLE.COM ', 'password' => 'Password123',
    ])->call('create')->assertHasFormErrors(['email' => 'unique']);
});

it('не записывает количество вне допустимого диапазона напрямую в базу', function (int $value): void {
    expect(fn () => DB::transaction(fn () => CheckoutFixtures::variant(['value' => $value])))
        ->toThrow(QueryException::class);
})->with([0, -1, 1000000000]);
