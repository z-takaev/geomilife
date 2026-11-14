<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderNotificationEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'event',
        'event_key',
        'dispatched_at',
    ];

    protected function casts(): array
    {
        return ['dispatched_at' => 'immutable_datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
