<?php

declare(strict_types=1);

namespace App\Filament\Resources\News\Schemas;

use Filament\Schemas\Schema;

final class NewsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
