<?php

declare(strict_types=1);

namespace App\Filament\Resources\Seo\Pages;

use App\Filament\Resources\Seo\SeoResource;
use Filament\Resources\Pages\EditRecord;

final class EditSeo extends EditRecord
{
    protected static string $resource = SeoResource::class;
}
