<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductAttributes\Pages;

use App\Filament\Resources\ProductAttributes\ProductAttributeResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateProductAttribute extends CreateRecord
{
    protected static string $resource = ProductAttributeResource::class;
}
