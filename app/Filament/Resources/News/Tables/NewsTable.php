<?php

declare(strict_types=1);

namespace App\Filament\Resources\News\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class NewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->label('Изображение')
                    ->collection('image')
                    ->conversion('card')
                    ->imageWidth(90)
                    ->imageHeight(60),
                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('published_at')
                    ->label('Дата публикации')
                    ->date('d.m.Y')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность')
                    ->placeholder('Все новости')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),
            ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('Новости не найдены')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
