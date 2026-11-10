<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardMetricsService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

final class TopProducts extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Самые продаваемые товары')
            ->records(fn (): array => app(DashboardMetricsService::class)->topProducts($this->pageFilters ?? []))
            ->columns([
                TextColumn::make('position')->label('Место'),
                TextColumn::make('name')->label('Товар')->wrap(),
                TextColumn::make('quantity')->label('Продано, шт.')->numeric()->alignEnd(),
            ])
            ->paginated(false);
    }
}
