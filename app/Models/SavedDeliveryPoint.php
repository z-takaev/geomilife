<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SavedDeliveryPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'city_id',
        'code',
        'type',
        'address',
        'work_time',
        'is_default',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'last_used_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
