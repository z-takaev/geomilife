<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected static ?string $title = 'Редактирование статьи';

    protected ?bool $hasDatabaseTransactions = true;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
