<?php

declare(strict_types=1);

use App\Enums\ProductUnit;
use App\Models\ProductVariant;
use App\Support\Values\Money;

it('формирует название фасовки', function (string $value, ProductUnit $unit, string $label): void {
    $variant = new ProductVariant(['value' => $value, 'unit' => $unit]);

    expect($variant->label())->toBe($label);
})->with([
    'граммы' => ['500.000', ProductUnit::GRAM, '500 г'],
    'миллилитры' => ['250.500', ProductUnit::MILLILITER, '250.5 мл'],
    'штуки' => ['1.000', ProductUnit::PIECE, '1 шт'],
]);

it('возвращает цену со скидкой и старую цену', function (): void {
    $variant = new ProductVariant([
        'price' => Money::fromRubles(750),
        'old_price' => Money::fromRubles(1000),
    ]);

    expect($variant->hasDiscount())->toBeTrue()
        ->and($variant->price->toRubles())->toBe('750.00')
        ->and($variant->old_price?->toRubles())->toBe('1000.00');
});

it('возвращает обычную цену без скидки', function (): void {
    $variant = new ProductVariant([
        'price' => Money::fromRubles(1000),
        'old_price' => null,
    ]);

    expect($variant->hasDiscount())->toBeFalse()
        ->and($variant->price->toRubles())->toBe('1000.00')
        ->and($variant->old_price)->toBeNull();
});
