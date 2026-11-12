<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingBoxes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class ShippingBoxForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Название')
                ->required()
                ->maxLength(100)
                ->columnSpan(6),
            TextInput::make('weight')
                ->label('Вес коробки с защитой')
                ->integer()
                ->minValue(1)
                ->maxValue(1000)
                ->required()
                ->suffix('г')
                ->columnSpan(6),
            ...self::dimensions(),
            Toggle::make('is_active')
                ->label('Доступна для упаковки')
                ->columnSpanFull(),
        ])->columns(12);
    }

    private static function dimensions(): array
    {
        $fields = [];
        foreach (['length' => 'Внешняя длина', 'width' => 'Внешняя ширина', 'height' => 'Внешняя высота'] as $name => $label) {
            $fields[] = TextInput::make($name)
                ->label($label)
                ->integer()
                ->minValue(11)
                ->maxValue(5000)
                ->required()
                ->suffix('мм')
                ->columnSpan(4);
        }

        return $fields;
    }
}
