<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ArticlesTable
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
                TextColumn::make('slug')
                    ->label('ЧПУ')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')
                    ->label('Опубликована')
                    ->date('d.m.Y')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Обновлена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность')
                    ->placeholder('Все статьи')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),
            ])
            ->defaultSort('published_at', 'desc')
            ->emptyStateHeading('Статьи не найдены')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
