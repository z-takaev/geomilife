<?php

declare(strict_types=1);

use App\Actions\Products\ExportProductsAction;
use App\Actions\Products\ImportProductsAction;
use App\Enums\FilamentUserRoleEnum;
use App\Enums\ProductUnit;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\FilamentUser;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Values\Money;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function pricingVariant(?int $oldPrice = null): ProductVariant
{
    $category = Category::query()->create(['name' => 'Масла', 'is_active' => true]);
    $product = Product::query()->create(['category_id' => $category->id, 'name' => 'Масло', 'is_active' => false]);

    return $product->variants()->create([
        'sku' => 'PRICING', 'unit' => ProductUnit::GRAM, 'value' => 500,
        'price' => Money::fromRubles(750),
        'old_price' => $oldPrice === null ? null : Money::fromRubles($oldPrice),
        'is_active' => true,
    ]);
}

function pricingSpreadsheet(ProductVariant $variant, array $prices): string
{
    $path = storage_path('framework/testing/pricing.xlsx');
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet()->setTitle('Товары');
    $sheet->fromArray(ExportProductsAction::HEADERS);
    $sheet->fromArray([
        'Вариация', $variant->product_id, $variant->id, null, null, null, null,
        $variant->sku, 500, $variant->unit->getLabel(), $prices['price'], $prices['old_price'], 'Да', 'Нет',
    ], null, 'A2', true);
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return $path;
}

afterEach(function (): void {
    $path = storage_path('framework/testing/pricing.xlsx');
    if (is_file($path)) {
        unlink($path);
    }
});

it('сохраняет обе цены в копейках и возвращает фиксированную актуальную цену', function (): void {
    $variant = pricingVariant(1000)->fresh();
    expect($variant->getRawOriginal('price'))->toBe(75000)
        ->and($variant->getRawOriginal('old_price'))->toBe(100000)
        ->and($variant->price->toRubles())->toBe('750.00')
        ->and($variant->old_price?->toRubles())->toBe('1000.00')
        ->and($variant->hasDiscount())->toBeTrue();
    $variant->update(['old_price' => Money::fromRubles(1500)]);
    expect($variant->fresh()->price->toRubles())->toBe('750.00');
});

it('убирает скидку при очистке старой цены', function (): void {
    $variant = pricingVariant(1000);
    $variant->update(['old_price' => null]);
    expect($variant->fresh()->hasDiscount())->toBeFalse()
        ->and($variant->fresh()->old_price)->toBeNull()
        ->and($variant->fresh()->price->toRubles())->toBe('750.00');
});

it('отклоняет некорректные цены при создании вариации', function (string $price, ?string $oldPrice): void {
    $variant = pricingVariant();
    expect(fn () => DB::transaction(fn () => $variant->product->variants()->create([
        'sku' => 'INVALID', 'unit' => ProductUnit::GRAM, 'value' => 100,
        'price' => Money::fromRubles($price),
        'old_price' => $oldPrice === null ? null : Money::fromRubles($oldPrice),
    ])))->toThrow(QueryException::class);
    expect($variant->product->variants()->count())->toBe(1);
})->with([
    'равные цены' => ['750', '750'],
    'старая ниже' => ['750', '749'],
    'дробная актуальная' => ['750.50', '1000'],
    'дробная старая' => ['750', '1000.50'],
    'нулевая актуальная' => ['0', null],
]);

it('не позволяет повысить актуальную цену до старой или выше', function (int $price): void {
    $variant = pricingVariant(1000);
    expect(fn () => DB::transaction(fn () => $variant->update(['price' => Money::fromRubles($price)])))->toThrow(QueryException::class);
    expect($variant->fresh()->price->toRubles())->toBe('750.00');
})->with([1000, 1100]);

it('позволяет одновременно изменить цены или очистить старую', function (): void {
    $variant = pricingVariant(1000);
    $variant->update(['price' => Money::fromRubles(1200), 'old_price' => Money::fromRubles(1500)]);
    expect($variant->fresh()->price->toRubles())->toBe('1200.00');
    $variant->update(['price' => Money::fromRubles(2000), 'old_price' => null]);
    expect($variant->fresh()->price->toRubles())->toBe('2000.00')
        ->and($variant->fresh()->hasDiscount())->toBeFalse();
});

it('выбирает скидочные товары по наличию старой цены у активной вариации', function (): void {
    $variant = pricingVariant(1000);
    $product = $variant->product;
    $product->update(['is_active' => true]);
    expect(Product::query()->discounted()->pluck('id')->all())->toBe([$product->id]);
    $variant->update(['is_active' => false]);
    expect(Product::query()->discounted()->exists())->toBeFalse()
        ->and($product->fresh()->hasDiscount())->toBeFalse();
    $variant->update(['is_active' => true, 'old_price' => null]);
    expect(Product::query()->discounted()->exists())->toBeFalse();
});

it('импортирует целые цены и позволяет очистить старую цену', function (?int $oldPrice): void {
    Gate::before(static fn (): bool => true);
    $variant = pricingVariant(1000);
    $path = pricingSpreadsheet($variant, ['price' => 800, 'old_price' => $oldPrice]);
    expect(app(ImportProductsAction::class)->preview($path))->toContain('Старая цена, ₽');
    expect(app(ImportProductsAction::class)->handle($path))->toBe(1);
    expect($variant->fresh()->price->toRubles())->toBe('800.00')
        ->and($variant->fresh()->old_price?->toRubles())->toBe($oldPrice === null ? null : '1200.00');
})->with([1200, null]);

it('отклоняет некорректные цены в Excel без сохранения', function (int|float $price, int|float $oldPrice): void {
    Gate::before(static fn (): bool => true);
    $variant = pricingVariant();
    $path = pricingSpreadsheet($variant, ['price' => $price, 'old_price' => $oldPrice]);
    expect(fn () => app(ImportProductsAction::class)->handle($path))->toThrow(ValidationException::class);
    expect($variant->fresh()->price->toRubles())->toBe('750.00')
        ->and($variant->fresh()->old_price)->toBeNull();
})->with([[800, 800], [800, 799], [800.5, 1000], [800, 1000.5], [800, 0]]);

it('экспортирует старую цену в рублях вместо процента скидки', function (): void {
    $variant = pricingVariant(1000);
    $response = app(ExportProductsAction::class)->handle(Product::query());
    ob_start();
    $response->sendContent();
    $contents = ob_get_clean();
    $path = storage_path('framework/testing/pricing.xlsx');
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }
    file_put_contents($path, $contents);
    $spreadsheet = IOFactory::load($path);
    try {
        $sheet = $spreadsheet->getSheetByName('Товары');
        expect($sheet->getCell('L1')->getValue())->toBe('Старая цена, ₽')
            ->and((int) $sheet->getCell('K3')->getValue())->toBe(750)
            ->and((int) $sheet->getCell('L3')->getValue())->toBe(1000);
    } finally {
        $spreadsheet->disconnectWorksheets();
    }
});

it('показывает ошибки цен в форме вариации', function (string $price, ?string $oldPrice, string $field, string $rule): void {
    $admin = FilamentUser::query()->create([
        'name' => 'Администратор', 'email' => 'pricing@example.com', 'password' => 'password',
        'role' => FilamentUserRoleEnum::ADMIN,
    ]);
    $this->actingAs($admin, 'filament');
    Filament\Facades\Filament::setCurrentPanel('admin');
    $variant = pricingVariant();
    Livewire\Livewire::test(EditProduct::class, ['record' => $variant->product->getRouteKey()])
        ->fillForm([
            'variants' => ['record-'.$variant->id => [
                'sku' => $variant->sku, 'value' => 500, 'unit' => ProductUnit::GRAM->value,
                'price' => $price, 'old_price' => $oldPrice, 'is_active' => true, 'is_preorder' => false,
            ]],
        ])
        ->call('save')
        ->assertHasFormErrors(['variants.record-'.$variant->id.'.'.$field => $rule]);
    expect($variant->fresh()->price->toRubles())->toBe('750.00');
})->with([
    ['800', '800', 'old_price', 'gt'],
    ['800', '799', 'old_price', 'gt'],
    ['800.5', '1000', 'price', 'integer'],
    ['800', '1000.5', 'old_price', 'integer'],
]);

it('защищает целостность цен при прямой записи в базу', function (int $price, ?int $oldPrice): void {
    $variant = pricingVariant();
    expect(fn () => DB::table('product_variants')->where('id', $variant->id)->update([
        'price' => $price, 'old_price' => $oldPrice,
    ]))->toThrow(QueryException::class);
})->with([[75050, null], [75000, 100050], [75000, 75000], [75000, 74900], [0, null]]);

it('сохраняет и очищает старую цену через форму редактирования', function (?int $oldPrice): void {
    Storage::fake(config('media-library.disk_name'));
    $admin = FilamentUser::query()->create([
        'name' => 'Администратор', 'email' => 'pricing@example.com', 'password' => 'password',
        'role' => FilamentUserRoleEnum::ADMIN,
    ]);
    $this->actingAs($admin, 'filament');
    Filament\Facades\Filament::setCurrentPanel('admin');
    $variant = pricingVariant(1000);
    $variant->product->addMedia(database_path('fixtures/images/Product/oil-product-card.png'))
        ->preservingOriginal()->toMediaCollection('image');
    Livewire\Livewire::test(EditProduct::class, ['record' => $variant->product->getRouteKey()])
        ->fillForm([
            'variants' => ['record-'.$variant->id => [
                'sku' => $variant->sku, 'value' => 500, 'unit' => ProductUnit::GRAM->value,
                'price' => 800, 'old_price' => $oldPrice, 'is_active' => true, 'is_preorder' => false,
            ]],
        ])
        ->call('save')->assertHasNoFormErrors();
    expect($variant->fresh()->price->toRubles())->toBe('800.00')
        ->and($variant->fresh()->old_price?->toRubles())->toBe($oldPrice === null ? null : '1200.00');
})->with([1200, null]);
