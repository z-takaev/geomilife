<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Resources\Pages\EditRecord;

final class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected static ?string $title = 'Редактирование целевой страницы';
}
