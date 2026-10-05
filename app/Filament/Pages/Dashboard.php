<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

final class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Обзор продаж')
                ->description('Демонстрационные данные.')
                ->schema([
                    Select::make('period')
                        ->label('Период заказов и выручки')
                        ->options([
                            'day' => 'Сегодня',
                            'week' => 'Последние 7 дней',
                            'month' => 'Текущий месяц',
                            'custom' => 'Произвольный период',
                        ])
                        ->default('day')
                        ->selectablePlaceholder(false)
                        ->live(),
                    Select::make('topLimit')
                        ->label('Самые продаваемые товары')
                        ->options([10 => 'Топ-10', 20 => 'Топ-20'])
                        ->default(10)
                        ->selectablePlaceholder(false),
                    DatePicker::make('startDate')
                        ->label('Начало периода')
                        ->default(now()->startOfMonth()->toDateString())
                        ->maxDate(now())
                        ->required()
                        ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                    DatePicker::make('endDate')
                        ->label('Конец периода')
                        ->default(now()->toDateString())
                        ->minDate(fn (Get $get): ?string => $get('startDate'))
                        ->maxDate(now())
                        ->required()
                        ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
