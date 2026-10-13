<?php

declare(strict_types=1);

namespace App\Filament\Resources\SeoMetadata;

use App\Filament\Resources\SeoMetadata\Pages\EditSeoMetadata;
use App\Filament\Resources\SeoMetadata\Pages\ListSeoMetadata;
use App\Filament\Resources\SeoMetadata\Schemas\SeoMetadataForm;
use App\Filament\Resources\SeoMetadata\Tables\SeoMetadataTable;
use App\Models\SeoMetadata;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class SeoMetadataResource extends Resource
{
    protected static ?string $model = SeoMetadata::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'SEO страниц';

    protected static ?string $modelLabel = 'SEO страницы';

    protected static ?string $pluralModelLabel = 'SEO страниц';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return SeoMetadataForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeoMetadataTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('route_name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeoMetadata::route('/'),
            'edit' => EditSeoMetadata::route('/{record}/edit'),
        ];
    }
}
