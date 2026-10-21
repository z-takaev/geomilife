<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'cdek_code',
        'name',
        'region',
        'sub_region',
        'is_active',
        'is_pickup_available',
        'is_courier_available',
    ];

    protected function casts(): array
    {
        return [
            'cdek_code' => 'integer',
            'is_active' => 'boolean',
            'is_pickup_available' => 'boolean',
            'is_courier_available' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }
}
