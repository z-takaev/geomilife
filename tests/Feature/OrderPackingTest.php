<?php

declare(strict_types=1);

use App\DTO\Orders\CheckoutItemDto;
use App\Models\ShippingBox;
use App\Services\Orders\OrderPackingService;
use App\Support\Values\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

function fulfillmentBox(array $attributes = []): ShippingBox
{
    return ShippingBox::query()->create(array_replace([
        'name' => 'Коробка 260 × 75 × 70', 'length' => 260, 'width' => 75, 'height' => 70,
        'weight' => 100, 'is_active' => true,
    ], $attributes));
}

function fulfillmentPackingItem(array $attributes = []): CheckoutItemDto
{
    $data = array_replace([
        'product_variant_id' => 1, 'quantity' => 1, 'length' => 250, 'width' => 65,
        'height' => 60, 'weight' => 500,
    ], $attributes);

    return new CheckoutItemDto(
        productId: 1,
        productVariantId: $data['product_variant_id'],
        productName: 'Товар',
        variantName: 'Вариант',
        sku: 'SKU-'.$data['product_variant_id'],
        quantity: $data['quantity'],
        price: Money::fromMinor(10000),
        oldPrice: null,
        total: Money::fromMinor(10000 * $data['quantity']),
        weight: $data['weight'],
        length: $data['length'],
        width: $data['width'],
        height: $data['height'],
        image: '',
        url: '',
    );
}

it('учитывает пять миллиметров с каждой стороны и один вес коробки с защитой', function (): void {
    $box = fulfillmentBox();
    $packages = app(OrderPackingService::class)->pack([fulfillmentPackingItem()]);

    expect($packages)->toHaveCount(1)
        ->and($packages[0]->boxId)->toBe($box->id)
        ->and($packages[0]->length)->toBe(260)
        ->and($packages[0]->width)->toBe(75)
        ->and($packages[0]->height)->toBe(70)
        ->and($packages[0]->innerLength)->toBe(250)
        ->and($packages[0]->innerWidth)->toBe(65)
        ->and($packages[0]->innerHeight)->toBe(60)
        ->and($packages[0]->weight)->toBe(600);
});

it('выбирает меньшую по объёму подходящую коробку', function (): void {
    fulfillmentBox(['name' => 'Большая коробка', 'length' => 500, 'width' => 500, 'height' => 500]);
    $smallerBox = fulfillmentBox();

    $packages = app(OrderPackingService::class)->pack([fulfillmentPackingItem()]);

    expect($packages)->toHaveCount(1)
        ->and($packages[0]->boxId)->toBe($smallerBox->id);
});

it('бессрочно кеширует активные коробки и сбрасывает кеш после изменения', function (): void {
    $box = fulfillmentBox();

    expect(ShippingBox::cached())->toHaveCount(1);

    DB::enableQueryLog();
    expect(ShippingBox::cached())->toHaveCount(1);
    $boxQueries = collect(DB::getQueryLog())->filter(
        fn (array $query): bool => str_contains($query['query'], 'from `shipping_boxes`'),
    );
    DB::disableQueryLog();

    expect($boxQueries)->toBeEmpty();

    $box->update(['is_active' => false]);

    expect(ShippingBox::cached())->toBeEmpty();
});

it('не использует внешние габариты как полезное пространство', function (): void {
    fulfillmentBox();

    expect(fn () => app(OrderPackingService::class)->pack([
        fulfillmentPackingItem(['length' => 251]),
    ]))->toThrow(ValidationException::class);
});

it('размещает бутылку лёжа когда стоя она не помещается', function (): void {
    fulfillmentBox(['length' => 270, 'width' => 90, 'height' => 90]);
    $packages = app(OrderPackingService::class)->pack([
        fulfillmentPackingItem(['length' => 70, 'width' => 70, 'height' => 250]),
    ]);

    expect($packages)->toHaveCount(1)
        ->and(collect($packages[0]->items)->sum('quantity'))->toBe(1);
});

it('размещает товары слоями и считает все единицы без повторного веса защиты', function (): void {
    fulfillmentBox(['length' => 110, 'width' => 110, 'height' => 210, 'weight' => 80]);
    $packages = app(OrderPackingService::class)->pack([
        fulfillmentPackingItem(['length' => 100, 'width' => 100, 'height' => 100, 'quantity' => 2, 'weight' => 300]),
    ]);

    expect($packages)->toHaveCount(1)
        ->and($packages[0]->weight)->toBe(680)
        ->and(collect($packages[0]->items)->sum('quantity'))->toBe(2);
});

it('использует несколько коробок одного типа если все товары не помещаются в одну', function (): void {
    $box = fulfillmentBox(['length' => 110, 'width' => 110, 'height' => 110, 'weight' => 80]);
    $packages = app(OrderPackingService::class)->pack([
        fulfillmentPackingItem(['length' => 100, 'width' => 100, 'height' => 100, 'quantity' => 3, 'weight' => 300]),
    ]);

    expect($packages)->toHaveCount(3)
        ->and(array_map(static fn ($package): int => $package->boxId, $packages))->toBe([$box->id, $box->id, $box->id])
        ->and(array_sum(array_map(static fn ($package): int => $package->weight, $packages)))->toBe(1140)
        ->and(array_sum(array_map(static fn ($package): int => collect($package->items)->sum('quantity'), $packages)))->toBe(3);
});

it('не возвращает частично упакованный заказ если одна позиция не помещается', function (): void {
    fulfillmentBox(['length' => 110, 'width' => 110, 'height' => 110]);

    expect(fn () => app(OrderPackingService::class)->pack([
        fulfillmentPackingItem(['length' => 50, 'width' => 50, 'height' => 50]),
        fulfillmentPackingItem(['product_variant_id' => 2, 'length' => 500, 'width' => 500, 'height' => 500]),
    ]))->toThrow(ValidationException::class);
});

it('не выбирает выключенную коробку даже если товар в неё помещается', function (): void {
    fulfillmentBox(['is_active' => false]);

    expect(fn () => app(OrderPackingService::class)->pack([fulfillmentPackingItem()]))
        ->toThrow(ValidationException::class);
});

it('отклоняет неполные транспортные характеристики без выдуманных размеров', function (string $field): void {
    fulfillmentBox();

    expect(fn () => app(OrderPackingService::class)->pack([fulfillmentPackingItem([$field => 0])]))
        ->toThrow(ValidationException::class);
})->with(['length', 'width', 'height', 'weight', 'quantity']);
