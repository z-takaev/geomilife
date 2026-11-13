<?php

declare(strict_types=1);

use App\Models\ShippingBox;
use App\Services\Orders\OrderPackingService;
use Illuminate\Validation\ValidationException;

function fulfillmentBox(array $attributes = []): ShippingBox
{
    return ShippingBox::query()->create(array_replace([
        'name' => 'Коробка 260 × 75 × 70', 'length' => 260, 'width' => 75, 'height' => 70,
        'weight' => 100, 'is_active' => true,
    ], $attributes));
}

function fulfillmentPackingItem(array $attributes = []): array
{
    return array_replace([
        'product_variant_id' => 1, 'quantity' => 1, 'length' => 250, 'width' => 65,
        'height' => 60, 'weight' => 500,
    ], $attributes);
}

it('учитывает пять миллиметров с каждой стороны и один вес коробки с защитой', function (): void {
    $box = fulfillmentBox();
    $packages = app(OrderPackingService::class)->pack([fulfillmentPackingItem()]);

    expect($packages)->toHaveCount(1)
        ->and($packages[0]['box_id'])->toBe($box->id)
        ->and($packages[0]['length'])->toBe(260)
        ->and($packages[0]['width'])->toBe(75)
        ->and($packages[0]['height'])->toBe(70)
        ->and($packages[0]['inner_length'])->toBe(250)
        ->and($packages[0]['inner_width'])->toBe(65)
        ->and($packages[0]['inner_height'])->toBe(60)
        ->and($packages[0]['weight'])->toBe(600);
});

it('выбирает меньшую по объёму подходящую коробку', function (): void {
    fulfillmentBox(['name' => 'Большая коробка', 'length' => 500, 'width' => 500, 'height' => 500]);
    $smallerBox = fulfillmentBox();

    $packages = app(OrderPackingService::class)->pack([fulfillmentPackingItem()]);

    expect($packages)->toHaveCount(1)
        ->and($packages[0]['box_id'])->toBe($smallerBox->id);
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
        ->and(array_sum(array_column($packages[0]['items'], 'quantity')))->toBe(1);
});

it('размещает товары слоями и считает все единицы без повторного веса защиты', function (): void {
    fulfillmentBox(['length' => 110, 'width' => 110, 'height' => 210, 'weight' => 80]);
    $packages = app(OrderPackingService::class)->pack([
        fulfillmentPackingItem(['length' => 100, 'width' => 100, 'height' => 100, 'quantity' => 2, 'weight' => 300]),
    ]);

    expect($packages)->toHaveCount(1)
        ->and($packages[0]['weight'])->toBe(680)
        ->and(array_sum(array_column($packages[0]['items'], 'quantity')))->toBe(2);
});

it('использует несколько коробок одного типа если все товары не помещаются в одну', function (): void {
    $box = fulfillmentBox(['length' => 110, 'width' => 110, 'height' => 110, 'weight' => 80]);
    $packages = app(OrderPackingService::class)->pack([
        fulfillmentPackingItem(['length' => 100, 'width' => 100, 'height' => 100, 'quantity' => 3, 'weight' => 300]),
    ]);

    expect($packages)->toHaveCount(3)
        ->and(array_column($packages, 'box_id'))->toBe([$box->id, $box->id, $box->id])
        ->and(array_sum(array_column($packages, 'weight')))->toBe(1140)
        ->and(array_sum(array_map(fn (array $package): int => array_sum(array_column($package['items'], 'quantity')), $packages)))->toBe(3);
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
