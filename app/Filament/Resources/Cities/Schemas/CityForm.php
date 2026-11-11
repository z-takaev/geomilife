<?php

declare(strict_types=1);

namespace App\Filament\Resources\Cities\Schemas;

use App\Support\Values\Money;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

final class CityForm
{
    private const string PHONE_PATTERN = '/^\+7(?: \(\d{3}\) \d{3}-\d{2}-\d{2}|\d{10})$/';

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Название')->disabled(),
            TextInput::make('cdek_code')->label('Код СДЭК')->disabled(),
            TextInput::make('region')->label('Регион')->disabled(),
            TextInput::make('sub_region')->label('Район')->disabled(),
            Toggle::make('is_active')->label('Доступен для выбора'),
            Toggle::make('is_suggested')->label('Показывать в подсказках'),
            Toggle::make('is_pickup_available')->label('Доступен самовывоз')->live(),
            ...self::pickupFields(),
            Toggle::make('is_cdek_excluded')->label('Исключить доставку СДЭК'),
            Toggle::make('is_courier_available')->label('Доступна курьерская доставка')->live(),
            ...self::courierFields(),
        ]);
    }

    public static function validationRules(bool $pickupAvailable, bool $courierAvailable): array
    {
        return [
            'pickup_name' => [Rule::requiredIf($pickupAvailable), 'nullable', 'string', 'max:255'],
            'pickup_address' => [Rule::requiredIf($pickupAvailable), 'nullable', 'string', 'max:1000'],
            'pickup_working_hours' => [Rule::requiredIf($pickupAvailable), 'nullable', 'string', 'max:255'],
            'pickup_phone' => [Rule::requiredIf($pickupAvailable), 'nullable', 'string', 'max:40', 'regex:'.self::PHONE_PATTERN],
            'courier_delivery_price' => [Rule::requiredIf($courierAvailable), 'nullable', 'integer', 'min:0', 'max:'.intdiv(PHP_INT_MAX, 100)],
            'door_delivery_price' => [Rule::requiredIf($courierAvailable), 'nullable', 'integer', 'min:0', 'max:'.intdiv(PHP_INT_MAX, 100)],
            'floor_lifting_price' => [Rule::requiredIf($courierAvailable), 'nullable', 'integer', 'min:0', 'max:'.intdiv(PHP_INT_MAX, 100)],
        ];
    }

    private static function pickupFields(): array
    {
        $fields = [
            TextInput::make('pickup_name')->label('Название точки самовывоза'),
            Textarea::make('pickup_address')->label('Адрес самовывоза'),
            TextInput::make('pickup_working_hours')->label('График самовывоза'),
            TextInput::make('pickup_phone')->label('Телефон точки самовывоза')->tel()->telRegex(self::PHONE_PATTERN),
        ];
        foreach ($fields as $field) {
            $name = $field->getName();
            $field->rules(static fn (Get $get): array => self::validationRules((bool) $get('is_pickup_available'), (bool) $get('is_courier_available'))[$name])
                ->markAsRequired(static fn (Get $get): bool => (bool) $get('is_pickup_available'))
                ->visible(static fn (Get $get): bool => (bool) $get('is_pickup_available'));
        }

        return $fields;
    }

    private static function courierFields(): array
    {
        $fields = [];
        foreach (['courier_delivery_price' => 'Стоимость курьерской доставки', 'door_delivery_price' => 'Доплата за доставку до двери', 'floor_lifting_price' => 'Стоимость подъёма за один этаж'] as $name => $label) {
            $fields[] = TextInput::make($name)->label($label)->inputMode('numeric')->suffix('₽')
                ->formatStateUsing(static fn (mixed $state): mixed => is_string($state) || $state instanceof Money
                    ? preg_replace('/\.00$/', '', $state instanceof Money ? $state->toRubles() : $state)
                    : $state)
                ->rules(static fn (Get $get): array => self::validationRules((bool) $get('is_pickup_available'), (bool) $get('is_courier_available'))[$name])
                ->markAsRequired(static fn (Get $get): bool => (bool) $get('is_courier_available'))
                ->visible(static fn (Get $get): bool => (bool) $get('is_courier_available'));
        }

        return $fields;
    }
}
