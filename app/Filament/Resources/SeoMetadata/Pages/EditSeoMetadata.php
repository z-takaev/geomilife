<?php

declare(strict_types=1);

namespace App\Filament\Resources\SeoMetadata\Pages;

use App\Filament\Resources\SeoMetadata\SeoMetadataResource;
use Filament\Resources\Pages\EditRecord;

final class EditSeoMetadata extends EditRecord
{
    protected static string $resource = SeoMetadataResource::class;
}
