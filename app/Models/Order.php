<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentMethodEnum;
use App\Enums\Orders\PaymentStatusEnum;
use App\Support\Email\NormalizedEmailCast;
use App\Support\Values\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

final class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'city_id',
        'payment_method',
        'subtotal',
        'delivery_details',
        'packages',
        'is_preorder',
        'idempotency_key',
        'checkout_session_hash',
        'cancellation_state',
        'cancellation_reason',
        'cancelled_at',
        'number',
        'status',
        'payment_status',
        'delivery_method',
        'delivery_price',
        'total',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_address',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'customer_email' => NormalizedEmailCast::class,
            'status' => OrderStatusEnum::class,
            'payment_status' => PaymentStatusEnum::class,
            'payment_method' => PaymentMethodEnum::class,
            'delivery_method' => DeliveryMethodEnum::class,
            'subtotal' => Money::class,
            'delivery_details' => 'array',
            'packages' => 'array',
            'is_preorder' => 'boolean',
            'cancelled_at' => 'immutable_datetime',
            'delivery_price' => Money::class,
            'total' => Money::class,
            'delivery_address' => 'array',
        ];
    }

    protected static function booted(): void
    {
        self::creating(static function (self $order): void {
            $order->idempotency_key ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(OrderShipment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OrderDocument::class);
    }
}
