<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingBoxes\Pages;

use App\Filament\Resources\ShippingBoxes\ShippingBoxResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditShippingBox extends EditRecord
{
    protected static string $resource = ShippingBoxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
