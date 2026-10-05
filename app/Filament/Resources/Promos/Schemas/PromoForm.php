<?php

declare(strict_types=1);

namespace App\Filament\Resources\Promos\Schemas;

use Filament\Schemas\Schema;

final class PromoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
