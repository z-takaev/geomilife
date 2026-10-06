<?php

declare(strict_types=1);

namespace App\Filament\Resources\HomeSlides;

use App\Filament\Resources\HomeSlides\Pages\CreateHomeSlide;
use App\Filament\Resources\HomeSlides\Pages\EditHomeSlide;
use App\Filament\Resources\HomeSlides\Pages\ListHomeSlides;
use App\Filament\Resources\HomeSlides\Schemas\HomeSlideForm;
use App\Filament\Resources\HomeSlides\Tables\HomeSlidesTable;
use App\Models\HomeSlide;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class HomeSlideResource extends Resource
{
    protected static ?string $model = HomeSlide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Слайды главной';

    protected static ?string $modelLabel = 'слайд';

    protected static ?string $pluralModelLabel = 'Слайды главной';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function form(Schema $schema): Schema
    {
        return HomeSlideForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeSlidesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeSlides::route('/'),
            'create' => CreateHomeSlide::route('/create'),
            'edit' => EditHomeSlide::route('/{record}/edit'),
        ];
    }
}
