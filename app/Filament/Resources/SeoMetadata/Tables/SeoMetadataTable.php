<?php

declare(strict_types=1);

namespace App\Filament\Resources\SeoMetadata\Tables;

use App\Enums\SeoRoute;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class SeoMetadataTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('route_name')
                    ->label('Страница')
                    ->formatStateUsing(static fn (string $state): string => SeoRoute::tryFrom($state)?->label() ?? $state)
                    ->sortable(),
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
