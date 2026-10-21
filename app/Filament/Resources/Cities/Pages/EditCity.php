<?php

declare(strict_types=1);

namespace App\Filament\Resources\Cities\Pages;

use App\Filament\Resources\Cities\CityResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

final class EditCity extends EditRecord
{
    protected static string $resource = CityResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Arr::only($data, [
            'is_active',
            'is_pickup_available',
            'is_courier_available',
        ]);
    }
}
