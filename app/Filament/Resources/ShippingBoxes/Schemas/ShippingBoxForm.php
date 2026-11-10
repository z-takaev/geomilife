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
            TextInput::make('name')->label('Название')->required()->maxLength(255),
            ...self::dimensions(),
            TextInput::make('weight')->label('Вес коробки с защитой')->integer()->minValue(1)->required()->suffix('г'),
            Toggle::make('is_active')->label('Доступна для упаковки'),
        ])->columns(2);
    }

    private static function dimensions(): array
    {
        $fields = [];
        foreach (['length' => 'Внешняя длина', 'width' => 'Внешняя ширина', 'height' => 'Внешняя высота'] as $name => $label) {
            $fields[] = TextInput::make($name)->label($label)->integer()->minValue(11)->required()->suffix('мм')
                ->helperText('Полезный размер вычисляется автоматически: внешняя сторона минус 10 мм.');
        }

        return $fields;
    }
}
