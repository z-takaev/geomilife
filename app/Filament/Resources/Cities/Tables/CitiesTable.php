<?php

declare(strict_types=1);

namespace App\Filament\Resources\Cities\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class CitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('region')
                    ->label('Регион')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sub_region')
                    ->label('Район')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cdek_code')
                    ->label('Код СДЭК')
                    ->searchable()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Активен')
                    ->sortable(),
                ToggleColumn::make('is_pickup_available')
                    ->label('Самовывоз')
                    ->sortable(),
                ToggleColumn::make('is_courier_available')
                    ->label('Курьер')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность')
                    ->placeholder('Все города')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),
                TernaryFilter::make('is_pickup_available')
                    ->label('Самовывоз')
                    ->placeholder('Все города')
                    ->trueLabel('Доступен')
                    ->falseLabel('Недоступен'),
                TernaryFilter::make('is_courier_available')
                    ->label('Курьерская доставка')
                    ->placeholder('Все города')
                    ->trueLabel('Доступна')
                    ->falseLabel('Недоступна'),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Города не найдены')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
