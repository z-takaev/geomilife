<?php

declare(strict_types=1);

namespace App\Filament\Resources\FilamentUsers\Pages;

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Filament\Resources\FilamentUsers\FilamentUserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

final class CreateFilamentUser extends CreateRecord
{
    protected static string $resource = FilamentUserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...Arr::only($data, ['name', 'email', 'password']),
            'role' => FilamentUserRoleEnum::MANAGER,
        ];
    }
}
