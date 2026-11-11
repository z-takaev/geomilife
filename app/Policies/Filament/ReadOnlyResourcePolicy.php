<?php

declare(strict_types=1);

namespace App\Policies\Filament;

use App\Models\FilamentUser;

final class ReadOnlyResourcePolicy extends AdminResourcePolicy
{
    public function viewAny(FilamentUser $user): bool
    {
        return true;
    }
}
