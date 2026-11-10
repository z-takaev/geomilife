<?php

declare(strict_types=1);

namespace App\Filament\Resources\Cities\Pages;

use App\Filament\Resources\Cities\CityResource;
use App\Jobs\Cdek\SyncDeliveryCitiesJob;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

final class ListCities extends ListRecords
{
    protected static string $resource = CityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncCdekCities')
                ->label(__('Синхронизировать города СДЭК'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function (): void {
                    SyncDeliveryCitiesJob::dispatch();

                    Notification::make()
                        ->title(__('Синхронизация городов СДЭК запущена'))
                        ->body(__('Справочник обновляется в фоне. Обновите страницу через некоторое время.'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
