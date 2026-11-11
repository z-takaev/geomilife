<?php

declare(strict_types=1);

namespace App\Policies\Filament;

use App\Models\FilamentUser;
use Illuminate\Database\Eloquent\Model;

class AdminResourcePolicy
{
    public function before(FilamentUser $user): ?bool
    {
        return $user->role->isAdmin() ? true : null;
    }

    public function viewAny(FilamentUser $user): bool
    {
        return false;
    }

    public function view(FilamentUser $user, Model $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(FilamentUser $user): bool
    {
        return false;
    }

    public function update(FilamentUser $user, Model $record): bool
    {
        return false;
    }

    public function delete(FilamentUser $user, Model $record): bool
    {
        return false;
    }

    public function deleteAny(FilamentUser $user): bool
    {
        return false;
    }

    public function forceDelete(FilamentUser $user, Model $record): bool
    {
        return false;
    }

    public function forceDeleteAny(FilamentUser $user): bool
    {
        return false;
    }

    public function restore(FilamentUser $user, Model $record): bool
    {
        return false;
    }

    public function restoreAny(FilamentUser $user): bool
    {
        return false;
    }

    public function replicate(FilamentUser $user, Model $record): bool
    {
        return false;
    }

    public function reorder(FilamentUser $user): bool
    {
        return false;
    }

    public function export(FilamentUser $user): bool
    {
        return false;
    }
}
