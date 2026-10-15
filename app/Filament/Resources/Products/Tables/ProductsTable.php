<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductAvailability;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(static fn (Builder $query): Builder => $query
                ->with('media')
                ->withCount([
                    'variants as active_variants_count' => static fn (Builder $query): Builder => $query->where('is_active', true),
                ]))
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->label('Изображение')
                    ->collection('image')
                    ->conversion('card')
                    ->imageWidth(64)
                    ->imageHeight(64),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('category.name')
                    ->label('Категория')
                    ->sortable()
                    ->wrap(),
                IconColumn::make('is_new')
                    ->label('Новинка')
                    ->boolean(),
                IconColumn::make('is_hit')
                    ->label('Хит')
                    ->boolean(),
                ToggleColumn::make('is_active')
                    ->label('Активен')
                    ->disabled(static fn (Product $record): bool => ! $record->is_active
                        && (! $record->hasMedia('image') || $record->active_variants_count === 0)),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('availability')
                    ->label('Доступность вариантов')
                    ->options(ProductAvailability::class)
                    ->query(static fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        static fn (Builder $query): Builder => $query->whereHas(
                            'variants',
                            static fn (Builder $query): Builder => $query->where('status', $data['value']),
                        ),
                    )),
                TernaryFilter::make('is_active')
                    ->label('Активность')
                    ->placeholder('Все товары')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),
                TernaryFilter::make('is_new')
                    ->label('Новинка'),
                TernaryFilter::make('is_hit')
                    ->label('Хит'),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Товары не найдены')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
