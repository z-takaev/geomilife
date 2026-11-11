<?php

declare(strict_types=1);

namespace App\Filament\Resources\Cities\Schemas;

use App\Support\Phone\PhoneFormat;
use App\Support\Values\Money;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

final class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Название')->disabled(),
            TextInput::make('cdek_code')->label('Код СДЭК')->disabled(),
            TextInput::make('region')->label('Регион')->disabled(),
            TextInput::make('sub_region')->label('Район')->disabled(),
            Toggle::make('is_active')->label('Активен'),
            Toggle::make('is_suggested')->label('Показывать в подсказках'),
            Toggle::make('is_pickup_available')->label('Доступен самовывоз')->live(),
            Toggle::make('is_cdek_excluded')->label('Исключить доставку СДЭК'),
            Toggle::make('is_courier_available')->label('Доступна курьерская доставка')->live(),
            Section::make('Самовывоз')
                ->key('pickup-fields')
                ->schema(self::pickupFields())
                ->visible(static fn (Get $get): bool => (bool) $get('is_pickup_available'))
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Курьерская доставка')
                ->key('courier-fields')
                ->schema(self::courierFields())
                ->visible(static fn (Get $get): bool => (bool) $get('is_courier_available'))
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    public static function validationRules(bool $pickupAvailable, bool $courierAvailable): array
    {
        return [
            'pickup_name' => [Rule::requiredIf($pickupAvailable), 'nullable', 'string', 'max:100'],
            'pickup_address' => [Rule::requiredIf($pickupAvailable), 'nullable', 'string', 'max:200'],
            'pickup_working_hours' => [Rule::requiredIf($pickupAvailable), 'nullable', 'string', 'max:50'],
            'pickup_phone' => [Rule::requiredIf($pickupAvailable), 'nullable', 'string', 'max:40', 'regex:'.PhoneFormat::FORMATTED_OR_COMPACT_PATTERN],
            'courier_delivery_price' => [Rule::requiredIf($courierAvailable), 'nullable', 'integer', 'min:0', 'max:9999'],
            'door_delivery_price' => [Rule::requiredIf($courierAvailable), 'nullable', 'integer', 'min:0', 'max:9999'],
            'floor_lifting_price' => [Rule::requiredIf($courierAvailable), 'nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    private static function pickupFields(): array
    {
        $fields = [
            TextInput::make('pickup_name')->label('Название точки самовывоза'),
            Textarea::make('pickup_address')->label('Адрес самовывоза'),
            TextInput::make('pickup_working_hours')->label('График самовывоза'),
            TextInput::make('pickup_phone')
                ->label('Телефон точки самовывоза')
                ->tel()
                ->telRegex(PhoneFormat::FORMATTED_OR_COMPACT_PATTERN),
        ];

        foreach ($fields as $field) {
            $name = $field->getName();
            $field->rules(static fn (Get $get): array => self::validationRules((bool) $get('is_pickup_available'), (bool) $get('is_courier_available'))[$name])
                ->markAsRequired(static fn (Get $get): bool => (bool) $get('is_pickup_available'));
        }

        return $fields;
    }

    private static function courierFields(): array
    {
        $fields = [];

        foreach (['courier_delivery_price' => 'Стоимость курьерской доставки', 'door_delivery_price' => 'Доплата за доставку до двери', 'floor_lifting_price' => 'Стоимость подъёма за один этаж'] as $name => $label) {
            $fields[] = TextInput::make($name)
                ->label($label)
                ->inputMode('numeric')
                ->suffix('₽')
                ->formatStateUsing(static fn (mixed $state): mixed => is_string($state) || $state instanceof Money
                    ? preg_replace('/\.00$/', '', $state instanceof Money ? $state->toRubles() : $state)
                    : $state)
                ->rules(static fn (Get $get): array => self::validationRules((bool) $get('is_pickup_available'), (bool) $get('is_courier_available'))[$name])
                ->markAsRequired(static fn (Get $get): bool => (bool) $get('is_courier_available'));
        }

        return $fields;
    }
}
