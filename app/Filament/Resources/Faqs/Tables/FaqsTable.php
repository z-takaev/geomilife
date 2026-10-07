<?php

declare(strict_types=1);

namespace App\Filament\Resources\Faqs\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')
                    ->label('Вопрос')
                    ->wrap(),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность')
                    ->placeholder('Все вопросы')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->emptyStateHeading('Вопросы не найдены')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
