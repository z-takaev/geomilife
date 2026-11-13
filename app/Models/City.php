<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Values\Money;
use Illuminate\Database\Eloquent\Builder;
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
        'is_suggested',
        'is_pickup_available',
        'is_courier_available',
        'is_cdek_excluded',
        'door_delivery_price',
        'pickup_name',
        'pickup_address',
        'pickup_working_hours',
        'pickup_phone',
        'courier_delivery_price',
        'floor_lifting_price',
    ];

    protected function casts(): array
    {
        return [
            'cdek_code' => 'integer',
            'is_active' => 'boolean',
            'is_suggested' => 'boolean',
            'is_pickup_available' => 'boolean',
            'is_courier_available' => 'boolean',
            'courier_delivery_price' => Money::class,
            'floor_lifting_price' => Money::class,
            'door_delivery_price' => Money::class,
            'is_cdek_excluded' => 'boolean',
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

    public function scopeSearchByName(Builder $query, string $search): void
    {
        $query->whereLike('name', '%'.addcslashes(trim($search), '\\%_').'%');
    }

    public function optionLabel(): string
    {
        return blank($this->region)
            ? $this->name
            : $this->name.', '.$this->region;
    }
}
