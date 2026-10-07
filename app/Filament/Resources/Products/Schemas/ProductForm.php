<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use Filament\Schemas\Schema;

final class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
