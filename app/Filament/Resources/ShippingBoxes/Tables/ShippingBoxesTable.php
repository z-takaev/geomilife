<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingBoxes\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ShippingBoxesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Название')->searchable()->sortable(),
            TextColumn::make('length')->label('Длина, мм')->sortable(),
            TextColumn::make('width')->label('Ширина, мм')->sortable(),
            TextColumn::make('height')->label('Высота, мм')->sortable(),
            TextColumn::make('weight')->label('Вес с защитой, г')->sortable(),
            ToggleColumn::make('is_active')->label('Доступна'),
        ])->filters([TernaryFilter::make('is_active')->label('Доступность')])
            ->defaultSort('name')->recordActions([EditAction::make()]);
    }
}
