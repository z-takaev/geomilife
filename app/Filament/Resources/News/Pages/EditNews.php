<?php

declare(strict_types=1);

namespace App\Filament\Resources\News\Pages;

use App\Filament\Resources\News\NewsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditNews extends EditRecord
{
    protected static string $resource = NewsResource::class;

    protected static ?string $title = 'Редактирование новости';

    protected ?bool $hasDatabaseTransactions = true;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
