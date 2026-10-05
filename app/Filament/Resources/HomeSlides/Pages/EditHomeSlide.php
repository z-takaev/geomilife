<?php

declare(strict_types=1);

namespace App\Filament\Resources\HomeSlides\Pages;

use App\Filament\Resources\HomeSlides\HomeSlideResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditHomeSlide extends EditRecord
{
    protected static string $resource = HomeSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
