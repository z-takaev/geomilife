<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Values\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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
            'delivery_price' => Money::class,
            'total' => Money::class,
            'delivery_address' => 'array',
        ];
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
}
