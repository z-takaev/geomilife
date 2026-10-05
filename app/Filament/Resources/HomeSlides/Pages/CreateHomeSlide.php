<?php

declare(strict_types=1);

namespace App\Filament\Resources\HomeSlides\Pages;

use App\Filament\Resources\HomeSlides\HomeSlideResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateHomeSlide extends CreateRecord
{
    protected static string $resource = HomeSlideResource::class;
}
