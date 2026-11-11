<?php

declare(strict_types=1);

namespace App\Filament\Resources\Cities\Tables;

use App\Filament\Resources\Cities\CityResource;
use App\Filament\Resources\Cities\Schemas\CityForm;
use App\Models\City;
use Closure;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;

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
                    ->disabled(static fn (City $record): bool => ! CityResource::canEdit($record))
                    ->label('Активен')
                    ->sortable(),
                ToggleColumn::make('is_suggested')
                    ->disabled(static fn (City $record): bool => ! CityResource::canEdit($record))
                    ->label('В подсказках')
                    ->sortable(),
                ToggleColumn::make('is_pickup_available')
                    ->disabled(static fn (City $record): bool => ! CityResource::canEdit($record))
                    ->rules(static fn (City $record): array => self::deliveryValidationRules($record, 'is_pickup_available'))
                    ->label('Самовывоз')
                    ->sortable(),
                ToggleColumn::make('is_courier_available')
                    ->disabled(static fn (City $record): bool => ! CityResource::canEdit($record))
                    ->rules(static fn (City $record): array => self::deliveryValidationRules($record, 'is_courier_available'))
                    ->label('Курьер')
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
                ->orderBy('name')->orderBy('id'))
            ->emptyStateHeading('Города не найдены')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    private static function deliveryValidationRules(City $city, string $field): array
    {
        return ['boolean', static function (string $attribute, mixed $value, Closure $fail) use ($city, $field): void {
            if (! $value) {
                return;
            }

            $data = $city->attributesToArray();
            $data[$field] = $value;
            foreach (['courier_delivery_price', 'door_delivery_price', 'floor_lifting_price'] as $priceField) {
                $data[$priceField] = preg_replace('/\.00$/', '', $city->{$priceField}?->toRubles() ?? '');
            }
            $validator = Validator::make($data, CityForm::validationRules((bool) $data['is_pickup_available'], (bool) $data['is_courier_available']));
            if ($validator->fails()) {
                $fail(__('Заполните корректные реквизиты и тарифы в карточке города перед включением доставки.'));
            }
        }];
    }
}
