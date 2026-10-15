<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductAvailability;
use App\Enums\ProductUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'unit',
        'value',
        'price',
        'discount_percent',
        'status',
        'is_active',
        'order',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    protected function casts(): array
    {
        return [
            'value' => 'decimal:3',
            'price' => 'decimal:2',
            'discount_percent' => 'integer',
            'status' => ProductAvailability::class,
            'unit' => ProductUnit::class,
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }
}
