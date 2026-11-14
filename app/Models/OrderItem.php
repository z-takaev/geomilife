<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Values\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

final class OrderItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id',
        'sku', 'product_name', 'variant_name',
        'price', 'old_price',
        'quantity', 'total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => Money::class,
            'old_price' => Money::class,
            'total' => Money::class,
        ];
    }

    protected static function booted(): void
    {
        self::updating(static function (): void {
            throw ValidationException::withMessages(['order' => __('Состав оформленного заказа изменять нельзя.')]);
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
