<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\FilamentUserRoleEnum;
use App\Models\FilamentUser;

final class FilamentUserPolicy
{
    public function viewAny(FilamentUser $user): bool
    {
        return $user->role === FilamentUserRoleEnum::ADMIN;
    }

    public function view(FilamentUser $user, FilamentUser $filamentUser): bool
    {
        return $this->viewAny($user) && $filamentUser->role === FilamentUserRoleEnum::MANAGER;
    }

    public function create(FilamentUser $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(FilamentUser $user, FilamentUser $filamentUser): bool
    {
        return $this->view($user, $filamentUser);
    }

    public function delete(FilamentUser $user, FilamentUser $filamentUser): bool
    {
        return $this->view($user, $filamentUser);
    }

    public function deleteAny(FilamentUser $user): bool
    {
        return false;
    }

    public function restore(FilamentUser $user, FilamentUser $filamentUser): bool
    {
        return false;
    }

    public function forceDelete(FilamentUser $user, FilamentUser $filamentUser): bool
    {
        return false;
    }
}
