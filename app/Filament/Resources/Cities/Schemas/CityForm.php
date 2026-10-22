<?php

declare(strict_types=1);

namespace App\Filament\Resources\Cities\Schemas;

use App\Support\Values\Money;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

final class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->disabled(),
                TextInput::make('cdek_code')
                    ->label('Код СДЭК')
                    ->disabled(),
                TextInput::make('region')
                    ->label('Регион')
                    ->disabled(),
                TextInput::make('sub_region')
                    ->label('Район')
                    ->disabled(),
                Toggle::make('is_active')
                    ->label('Доступен для выбора'),
                Toggle::make('is_pickup_available')
                    ->label('Доступен самовывоз'),
                Toggle::make('is_courier_available')
                    ->label('Доступна курьерская доставка')
                    ->live(),
                TextInput::make('courier_delivery_price')
                    ->label('Стоимость доставки до двери')
                    ->formatStateUsing(static fn (mixed $state): mixed => $state instanceof Money ? $state->toRubles() : $state)
                    ->integer()
                    ->minValue(0)
                    ->suffix('₽')
                    ->required(static fn (Get $get): bool => (bool) $get('is_courier_available'))
                    ->visible(static fn (Get $get): bool => (bool) $get('is_courier_available')),
                TextInput::make('floor_lifting_price')
                    ->label('Стоимость подъёма за один этаж')
                    ->formatStateUsing(static fn (mixed $state): mixed => $state instanceof Money ? $state->toRubles() : $state)
                    ->integer()
                    ->minValue(0)
                    ->suffix('₽')
                    ->required(static fn (Get $get): bool => (bool) $get('is_courier_available'))
                    ->visible(static fn (Get $get): bool => (bool) $get('is_courier_available')),
            ]);
    }
}
