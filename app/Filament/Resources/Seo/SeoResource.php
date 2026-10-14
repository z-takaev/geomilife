<?php

declare(strict_types=1);

namespace App\Filament\Resources\Seo;

use App\Filament\Resources\Seo\Pages\EditSeo;
use App\Filament\Resources\Seo\Pages\ListSeo;
use App\Filament\Resources\Seo\Schemas\SeoForm;
use App\Filament\Resources\Seo\Tables\SeoTable;
use App\Models\Seo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class SeoResource extends Resource
{
    protected static ?string $model = Seo::class;

    protected static ?string $slug = 'seo';

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
        return SeoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeoTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('route_name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeo::route('/'),
            'edit' => EditSeo::route('/{record}/edit'),
        ];
    }
}
