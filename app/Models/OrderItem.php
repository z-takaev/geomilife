<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Values\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_name',
        'sku',
        'price',
        'quantity',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'price' => Money::class,
            'quantity' => 'integer',
            'total' => Money::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
