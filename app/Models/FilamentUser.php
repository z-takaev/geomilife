<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Support\Email\NormalizedEmailCast;
use Filament\Models\Contracts\FilamentUser as FilamentUserContract;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class FilamentUser extends Authenticatable implements FilamentUserContract
{
    use Notifiable;

    protected $fillable = [
        'name', 'email',
        'password', 'role',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email' => NormalizedEmailCast::class,
            'password' => 'hashed',
            'role' => FilamentUserRoleEnum::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin';
    }
}
