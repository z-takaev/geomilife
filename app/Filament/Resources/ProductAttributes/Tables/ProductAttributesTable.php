<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductAttributes\Tables;

use App\Models\ProductAttribute;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ProductAttributesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(static fn (Builder $query): Builder => $query->withCount('productValues'))
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product_values_count')
                    ->label('Товаров')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Характеристики не найдены')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->authorize(static fn (ProductAttribute $record): bool => $record->canBeDeleted()),
            ]);
    }
}
