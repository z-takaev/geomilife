<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Cdek\CdekRequestStateEnum;
use App\Enums\Cdek\CdekShipmentWorkflowStateEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderShipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'uuid',
        'cdek_number',
        'status',
        'request_state',
        'status_at',
        'operation_state',
        'error',
        'last_checked_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'request_state' => CdekRequestStateEnum::class,
            'operation_state' => CdekShipmentWorkflowStateEnum::class,
            'status_at' => 'immutable_datetime',
            'last_checked_at' => 'immutable_datetime',
            'payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
