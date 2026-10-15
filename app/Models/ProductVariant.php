<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductAvailability;
use App\Enums\ProductUnit;
use Illuminate\Database\Eloquent\Builder;
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function label(): string
    {
        $value = rtrim(rtrim($this->value, '0'), '.');

        return sprintf('%s %s', $value === '' ? '0' : $value, $this->unit->getLabel());
    }
}
