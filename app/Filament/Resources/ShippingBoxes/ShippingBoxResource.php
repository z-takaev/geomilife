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
use Illuminate\Auth\Access\Response;

final class ShippingBoxResource extends Resource
{
    private const int MAX_SHIPPING_BOXES = 7;

    protected static ?string $model = ShippingBox::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Коробки';

    protected static ?string $modelLabel = 'коробка';

    protected static ?string $pluralModelLabel = 'Коробки';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getCreateAuthorizationResponse(): Response
    {
        $response = parent::getCreateAuthorizationResponse();

        if ($response->denied()) {
            return $response;
        }

        return ShippingBox::query()->count() < self::MAX_SHIPPING_BOXES
            ? $response
            : Response::deny(__('Нельзя добавить больше :limit упаковок.', ['limit' => self::MAX_SHIPPING_BOXES]));
    }

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
