<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Orders\ExportOrdersAction;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('exportExcel')->label('Экспорт в Excel')->authorize(fn (): bool => OrderResource::canViewAny())
            ->action(fn (): StreamedResponse => app(ExportOrdersAction::class)->handle($this->getFilteredSortedTableQuery()))];
    }
}
