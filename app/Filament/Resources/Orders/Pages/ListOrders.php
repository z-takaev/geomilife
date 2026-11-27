<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Orders\ExportOrdersAction;
use App\Enums\Orders\PaymentMethodEnum;
use App\Enums\Orders\PaymentStatusEnum;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        $unpaidStatuses = [
            PaymentStatusEnum::UNPAID,
            PaymentStatusEnum::PENDING,
            PaymentStatusEnum::AUTHORIZED,
            PaymentStatusEnum::FAILED,
            PaymentStatusEnum::CANCELLED,
        ];

        return [
            'main' => Tab::make('Основные')->modifyQueryUsing(
                fn (Builder $query): Builder => $query->where(
                    fn (Builder $query): Builder => $query
                        ->where('payment_method', '!=', PaymentMethodEnum::ONLINE)
                        ->orWhereNotIn('payment_status', $unpaidStatuses),
                ),
            ),
            'unpaid_online' => Tab::make('Неоплаченные онлайн заказы')->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->where('payment_method', PaymentMethodEnum::ONLINE)
                    ->whereIn('payment_status', $unpaidStatuses),
            ),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('exportExcel')->label('Экспорт в Excel')->authorize(fn (): bool => OrderResource::canViewAny())
            ->action(fn (): StreamedResponse => app(ExportOrdersAction::class)->handle($this->getFilteredSortedTableQuery()))];
    }
}
