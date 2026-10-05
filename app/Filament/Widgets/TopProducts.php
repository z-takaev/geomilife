<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

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
            ->description('За всё время, по количеству проданных единиц. Товары и значения — демонстрационные.')
            ->records(function (): array {
                $limit = (int) ($this->pageFilters['topLimit'] ?? 10) === 20 ? 20 : 10;
                $products = [];

                foreach (range(1, $limit) as $position) {
                    $products[$position] = [
                        'position' => $position,
                        'name' => 'Демонстрационный товар '.$position,
                        'quantity' => 420 - ($position - 1) * 18,
                    ];
                }

                return $products;
            })
            ->columns([
                TextColumn::make('position')->label('Место'),
                TextColumn::make('name')->label('Товар')->wrap(),
                TextColumn::make('quantity')->label('Продано, шт.')->numeric()->alignEnd(),
            ])
            ->paginated(false);
    }
}
