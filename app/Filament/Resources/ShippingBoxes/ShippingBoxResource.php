<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingBoxes;

use App\Filament\Resources\ShippingBoxes\Pages\CreateShippingBox;
use App\Filament\Resources\ShippingBoxes\Pages\EditShippingBox;
use App\Filament\Resources\ShippingBoxes\Pages\ListShippingBoxes;
use App\Filament\Resources\ShippingBoxes\Schemas\ShippingBoxForm;
use App\Filament\Resources\ShippingBoxes\Tables\ShippingBoxesTable;
use App\Models\ShippingBox;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class ShippingBoxResource extends Resource
{
    protected static ?string $model = ShippingBox::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Коробки';

    protected static ?string $modelLabel = 'коробка';

    protected static ?string $pluralModelLabel = 'Коробки';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ShippingBoxForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShippingBoxesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingBoxes::route('/'),
            'create' => CreateShippingBox::route('/create'),
            'edit' => EditShippingBox::route('/{record}/edit'),
        ];
    }
}
