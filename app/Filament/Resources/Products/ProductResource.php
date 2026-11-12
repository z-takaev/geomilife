<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use App\Support\Values\Money;
use BackedEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Товары';

    protected static ?string $modelLabel = 'товар';

    protected static ?string $pluralModelLabel = 'Товары';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Товар')->schema([
                SpatieMediaLibraryImageEntry::make('image')->label('Изображение')->collection('image'),
                TextEntry::make('name')->label('Название'),
                TextEntry::make('category.name')->label('Категория'),
                TextEntry::make('description')->label('Описание')->columnSpanFull(),
                IconEntry::make('is_active')->label('Активен')->boolean(),
                IconEntry::make('is_new')->label('Новинка')->boolean(),
                IconEntry::make('is_hit')->label('Хит')->boolean(),
                SpatieMediaLibraryImageEntry::make('gallery')->label('Галерея')->collection('gallery')->columnSpanFull(),
            ])->columns(3),
            Section::make('Варианты')->schema([
                RepeatableEntry::make('variants')->hiddenLabel()->schema([
                    TextEntry::make('sku')->label('Артикул'),
                    TextEntry::make('value')->label('Количество'),
                    TextEntry::make('unit')->label('Единица'),
                    TextEntry::make('price')->label('Цена')->formatStateUsing(static fn (Money $state): string => $state->format()),
                    TextEntry::make('old_price')->label('Старая цена')->formatStateUsing(static fn (?Money $state): ?string => $state?->format()),
                    IconEntry::make('is_active')->label('Активен')->boolean(),
                    IconEntry::make('is_preorder')->label('Предзаказ')->boolean(),
                    TextEntry::make('weight')->label('Вес, г'),
                    TextEntry::make('length')->label('Длина, мм'),
                    TextEntry::make('width')->label('Ширина, мм'),
                    TextEntry::make('height')->label('Высота, мм'),
                ])->columns(3),
            ]),
            Section::make('Характеристики')->schema([
                RepeatableEntry::make('attributeValues')->hiddenLabel()->schema([
                    TextEntry::make('productAttribute.name')->label('Характеристика'),
                    TextEntry::make('value')->label('Значение'),
                ])->columns(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
            'view' => ViewProduct::route('/{record}'),
        ];
    }
}
