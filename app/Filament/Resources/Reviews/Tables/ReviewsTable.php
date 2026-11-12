<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reviews\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('author_name')
                    ->label('Комментатор')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Источник')
                    ->placeholder('Не указан'),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность')
                    ->placeholder('Все отзывы')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),
            ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('Отзывы не найдены')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->searchable(false);
    }
}
