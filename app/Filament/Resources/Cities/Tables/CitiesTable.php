<?php

declare(strict_types=1);

namespace App\Filament\Resources\Cities\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class CitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cdek_code')
                    ->label('Код СДЭК')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('region')
                    ->label('Регион')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_pickup_available')
                    ->label('Самовывоз')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_courier_available')
                    ->label('Курьер')
                    ->boolean()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Активен')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_suggested')
                    ->label('В подсказках'),
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
            ->defaultSort(static fn (Builder $query): Builder => $query
                ->orderByRaw('CASE WHEN is_courier_available OR is_pickup_available THEN 0 ELSE 1 END')
                ->orderBy('name')
                ->orderBy('id'))
            ->emptyStateHeading('Города не найдены')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
