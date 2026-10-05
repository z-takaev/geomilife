<?php

declare(strict_types=1);

namespace App\Filament\Resources\HomeSlides\Pages;

use App\Filament\Resources\HomeSlides\HomeSlideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListHomeSlides extends ListRecords
{
    protected static string $resource = HomeSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
