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

    protected static string $routePath = '/overview';

    public static function canAccess(): bool
    {
        return auth('filament')->user()?->role?->isAdmin() ?? false;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Обзор продаж')
                ->description('Все показатели за выбранный период по дате создания заказа.')
                ->schema([
                    Select::make('period')
                        ->label('Период')
                        ->options([
                            'day' => 'Сегодня',
                            'week' => 'Последние 7 дней',
                            'month' => 'Текущий месяц',
                            'all' => 'За всё время',
                            'custom' => 'Произвольный период',
                        ])
                        ->default('day')
                        ->selectablePlaceholder(false)
                        ->live(),

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
