<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Orders\PaymentStatusEnum;
use App\Support\Values\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'bank_order_id',
        'payment_url',
        'provider_status',
        'operation_state',
        'error',
        'refund_requested_at',
        'refunded_at',
        'last_checked_at',
        'transaction_id',
        'amount',
        'status',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatusEnum::class,
            'refund_requested_at' => 'immutable_datetime',
            'refunded_at' => 'immutable_datetime',
            'last_checked_at' => 'immutable_datetime',
            'amount' => Money::class,
            'payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
