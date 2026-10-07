<?php

declare(strict_types=1);

namespace App\Filament\Resources\Promos\Pages;

use App\Filament\Resources\Promos\PromoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditPromo extends EditRecord
{
    protected static string $resource = PromoResource::class;

    protected static ?string $title = 'Редактирование акции';

    protected ?bool $hasDatabaseTransactions = true;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
