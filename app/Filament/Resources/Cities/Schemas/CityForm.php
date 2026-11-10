<?php

declare(strict_types=1);

namespace App\Filament\Resources\Cities\Schemas;

use App\Support\Values\Money;
use Filament\Forms\Components\Textarea;
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
                Toggle::make('is_suggested')
                    ->label('Показывать в подсказках'),
                Toggle::make('is_pickup_available')
                    ->label('Доступен самовывоз')
                    ->live(),
                ...self::pickupFields(),
                Toggle::make('is_cdek_excluded')->label('Исключить доставку СДЭК'),
                Toggle::make('is_courier_available')
                    ->label('Доступна курьерская доставка')
                    ->live(),
                TextInput::make('courier_delivery_price')
                    ->label('Стоимость курьерской доставки')
                    ->formatStateUsing(static fn (mixed $state): mixed => $state instanceof Money ? $state->toRubles() : $state)
                    ->integer()
                    ->minValue(0)
                    ->suffix('₽')
                    ->required(static fn (Get $get): bool => (bool) $get('is_courier_available'))
                    ->visible(static fn (Get $get): bool => (bool) $get('is_courier_available')),
                TextInput::make('door_delivery_price')
                    ->label('Доплата за доставку до двери')
                    ->formatStateUsing(static fn (mixed $state): mixed => $state instanceof Money ? $state->toRubles() : $state)
                    ->integer()->minValue(0)->suffix('₽')
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

    private static function pickupFields(): array
    {
        $fields = [
            TextInput::make('pickup_name')->label('Название точки самовывоза')->maxLength(255),
            Textarea::make('pickup_address')->label('Адрес самовывоза')->maxLength(1000),
            TextInput::make('pickup_working_hours')->label('График самовывоза')->maxLength(255),
            TextInput::make('pickup_phone')->label('Телефон точки самовывоза')->tel()->maxLength(40),
        ];
        foreach ($fields as $field) {
            $field->required(static fn (Get $get): bool => (bool) $get('is_pickup_available'))
                ->visible(static fn (Get $get): bool => (bool) $get('is_pickup_available'));
        }

        return $fields;
    }
}
