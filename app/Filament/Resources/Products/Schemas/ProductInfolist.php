<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Support\Values\Money;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Товар')->schema([
                SpatieMediaLibraryImageEntry::make('image')
                    ->label('Изображение')
                    ->collection('image')
                    ->placeholder('Изображение не загружено'),
                TextEntry::make('name')->label('Название'),
                TextEntry::make('category.name')->label('Категория'),
                TextEntry::make('description')->label('Описание')->placeholder('—')->columnSpanFull(),
                IconEntry::make('is_active')->label('Активен')->boolean(),
                IconEntry::make('is_new')->label('Новинка')->boolean(),
                IconEntry::make('is_hit')->label('Хит')->boolean(),
                SpatieMediaLibraryImageEntry::make('gallery')
                    ->label('Галерея')
                    ->collection('gallery')
                    ->placeholder('Изображения не загружены')
                    ->columnSpanFull(),
            ])->columns(3),
            Section::make('Характеристики')->schema([
                RepeatableEntry::make('attributeValues')
                    ->hiddenLabel()
                    ->placeholder('Характеристики не указаны')
                    ->schema([
                        TextEntry::make('productAttribute.name')->label('Характеристика'),
                        TextEntry::make('value')->label('Значение'),
                    ])
                    ->columns(2),
            ]),
            Section::make('Варианты')->schema([
                RepeatableEntry::make('variants')->hiddenLabel()->schema([
                    TextEntry::make('sku')->label('Артикул'),
                    TextEntry::make('value')->label('Количество'),
                    TextEntry::make('unit')->label('Единица'),
                    TextEntry::make('price')->label('Цена')->formatStateUsing(static fn (Money $state): string => $state->format()),
                    TextEntry::make('old_price')->label('Старая цена')->formatStateUsing(static fn (?Money $state): ?string => $state?->format())->placeholder('—'),
                    IconEntry::make('is_active')->label('Активен')->boolean(),
                    IconEntry::make('is_preorder')->label('Предзаказ')->boolean(),
                    TextEntry::make('weight')->label('Вес, г')->placeholder('—'),
                    TextEntry::make('length')->label('Длина, мм')->placeholder('—'),
                    TextEntry::make('width')->label('Ширина, мм')->placeholder('—'),
                    TextEntry::make('height')->label('Высота, мм')->placeholder('—'),
                ])->columns(3),
            ]),
        ]);
    }
}
