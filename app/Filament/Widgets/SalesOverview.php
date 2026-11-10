<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardMetricsService;
use App\Support\Values\Money;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class SalesOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Основные метрики';

    protected function getStats(): array
    {
        $service = app(DashboardMetricsService::class);
        $filters = $this->pageFilters ?? [];
        $metrics = $service->overview($filters);

        return [
            Stat::make('Количество заказов', $metrics === null ? '—' : number_format($metrics['orders'], 0, ',', ' '))
                ->icon(Heroicon::OutlinedShoppingBag)
                ->color('primary'),
            Stat::make('Выручка', $metrics === null ? '—' : Money::fromMinor($metrics['revenue'])->format())
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success'),
            Stat::make('Средний чек', $metrics === null ? '—' : Money::fromMinor($metrics['average'])->format())
                ->icon(Heroicon::OutlinedReceiptPercent),
            Stat::make('Доля повторных покупок', $metrics === null ? '—' : str_replace('.', ',', (string) $metrics['repeatPercentage']).' %')
                ->icon(Heroicon::OutlinedArrowPath),
        ];
    }
}
