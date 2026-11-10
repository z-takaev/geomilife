<?php

declare(strict_types=1);

use App\Enums\Catalog\CatalogSortEnum;
use App\Enums\Reviews\ReviewSourceEnum;
use Filament\Support\Contracts\HasLabel;

it('предоставляет подписи сортировки через контракт Filament', function (CatalogSortEnum $sort, string $label): void {
    expect($sort)->toBeInstanceOf(HasLabel::class)
        ->and($sort->getLabel())->toBe($label);
})->with([
    'популярность' => [CatalogSortEnum::POPULAR, 'Сначала хиты'],
    'новизна' => [CatalogSortEnum::NEW, 'По новизне'],
    'возрастание цены' => [CatalogSortEnum::PRICE_ASC, 'Сначала дешевле'],
    'убывание цены' => [CatalogSortEnum::PRICE_DESC, 'Сначала дороже'],
    'название' => [CatalogSortEnum::NAME, 'По названию'],
]);

it('предоставляет подпись и путь логотипа источника отзыва', function (ReviewSourceEnum $source, string $label, string $logoPath): void {
    expect($source)->toBeInstanceOf(HasLabel::class)
        ->and($source->getLabel())->toBe($label)
        ->and($source->getLogoPath())->toBe($logoPath);
})->with([
    'Яндекс' => [ReviewSourceEnum::YANDEX, 'Яндекс', 'assets/images/reviews/ya.webp'],
    'Google' => [ReviewSourceEnum::GOOGLE, 'Google', 'assets/images/reviews/google.webp'],
]);
