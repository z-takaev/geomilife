<?php

declare(strict_types=1);

namespace App\Filament\Resources\HomeSlides\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class HomeSlidesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('desktop_image')
                    ->label('Изображение')
                    ->collection('desktop_image')
                    ->imageWidth(160)
                    ->imageHeight(60),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->emptyStateHeading('Слайды не найдены')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность')
                    ->placeholder('Все слайды')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->paginated(false);
    }
}
