<?php

declare(strict_types=1);

namespace App\Filament\Resources\Seo\Tables;

use App\Models\Seo;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class SeoTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('path')
                    ->label('Страница')
                    ->url(static fn (Seo $record): ?string => $record->url)
                    ->openUrlInNewTab()
                    ->placeholder('Страница недоступна'),
                TextColumn::make('title')
                    ->label('SEO-заголовок')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('SEO-описание')
                    ->limit(80)
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
