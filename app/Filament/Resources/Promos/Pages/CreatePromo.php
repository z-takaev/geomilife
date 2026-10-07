<?php

declare(strict_types=1);

namespace App\Filament\Resources\Promos\Pages;

use App\Filament\Resources\Promos\PromoResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePromo extends CreateRecord
{
    protected static string $resource = PromoResource::class;

    protected static ?string $title = 'Создание акции';

    protected ?bool $hasDatabaseTransactions = true;
}
