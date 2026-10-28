<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductUnit;
use App\Repositories\Categories\CategoryRepository;
use App\Support\Values\Money;
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
        'is_active',
        'is_preorder',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:3',
            'price' => Money::class,
            'discount_percent' => 'integer',
            'unit' => ProductUnit::class,
            'is_active' => 'boolean',
            'is_preorder' => 'boolean',
            'order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        self::saved(static function (self $variant): void {
            if ($variant->wasRecentlyCreated || $variant->wasChanged(['product_id', 'is_active'])) {
                app(CategoryRepository::class)->forget();
            }
        });

        self::deleted(static function (): void {
            app(CategoryRepository::class)->forget();
        });
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

    public function hasDiscount(): bool
    {
        return $this->discount_percent > 0;
    }

    public function currentPrice(): Money
    {
        return $this->hasDiscount()
            ? $this->price->discountedBy($this->discount_percent)
            : $this->price;
    }

    public function oldPrice(): ?Money
    {
        return $this->hasDiscount() ? $this->price : null;
    }
}
