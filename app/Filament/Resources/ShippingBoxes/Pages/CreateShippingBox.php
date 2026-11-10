<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingBoxes\Pages;

use App\Filament\Resources\ShippingBoxes\ShippingBoxResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateShippingBox extends CreateRecord
{
    protected static string $resource = ShippingBoxResource::class;
}
