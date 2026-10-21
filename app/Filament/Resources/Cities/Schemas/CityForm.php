<?php

declare(strict_types=1);

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                    ->label('Доступна курьерская доставка'),
            ]);
    }
}
