<?php

declare(strict_types=1);

namespace App\Filament\Resources\Faqs\Pages;

use App\Filament\Resources\Faqs\FaqResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFaq extends CreateRecord
{
    protected static string $resource = FaqResource::class;

    protected static ?string $title = 'Создание вопроса';
}
