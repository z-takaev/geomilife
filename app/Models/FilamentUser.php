<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Filament\FilamentUserRoleEnum;
use Filament\Models\Contracts\FilamentUser as FilamentUserContract;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class FilamentUser extends Authenticatable implements FilamentUserContract
{
    use Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => FilamentUserRoleEnum::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin';
    }
}
