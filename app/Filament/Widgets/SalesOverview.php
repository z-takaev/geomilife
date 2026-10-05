<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Carbon\CarbonImmutable;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Validator;

final class SalesOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Основные метрики';

    protected ?string $description = 'Демонстрационные значения, не связанные с реальными заказами.';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $days = $this->getPeriodDays();
        $orders = $days === null ? null : $days * 24;
        $periodLabel = match ($this->pageFilters['period'] ?? 'day') {
            'week' => 'За последние 7 дней',
            'month' => 'С начала текущего месяца',
            'custom' => $days === null ? 'Укажите корректные даты: начало не позже конца, без будущих дат' : 'За выбранный период, включая обе даты',
            default => 'За сегодня',
        };

        return [
            Stat::make('Количество заказов', $orders === null ? '—' : number_format($orders, 0, ',', ' '))
                ->description($periodLabel)
                ->icon(Heroicon::OutlinedShoppingBag)
                ->color('primary'),
            Stat::make('Выручка', $orders === null ? '—' : number_format($orders * 3250, 0, ',', ' ').' ₽')
                ->description($periodLabel)
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success'),
            Stat::make('Средний чек', '3 250 ₽')
                ->description('По всем заказам за всё время')
                ->icon(Heroicon::OutlinedReceiptPercent),
            Stat::make('Доля повторных покупок', '28 %')
                ->description('Повторные заказы / все заказы за всё время')
                ->icon(Heroicon::OutlinedArrowPath),
        ];
    }

    private function getPeriodDays(): ?int
    {
        $period = $this->pageFilters['period'] ?? 'day';

        if ($period !== 'custom') {
            return match ($period) {
                'week' => 7,
                'month' => now()->day,
                default => 1,
            };
        }

        $dates = [
            'startDate' => $this->pageFilters['startDate'] ?? null,
            'endDate' => $this->pageFilters['endDate'] ?? null,
        ];

        if (Validator::make($dates, [
            'startDate' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'endDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:startDate', 'before_or_equal:today'],
        ])->fails()) {
            return null;
        }

        return (int) CarbonImmutable::parse($dates['startDate'])
            ->diffInDays(CarbonImmutable::parse($dates['endDate'])) + 1;
    }
}
