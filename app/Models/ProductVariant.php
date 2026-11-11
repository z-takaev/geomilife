<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Products\ProductUnitEnum;
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
        'old_price',
        'weight',
        'length',
        'width',
        'height',
        'is_active',
        'is_preorder',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'price' => Money::class,
            'old_price' => Money::class,
            'weight' => 'integer',
            'length' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'unit' => ProductUnitEnum::class,
            'is_active' => 'boolean',
            'is_preorder' => 'boolean',
            'order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        self::saved(static function (self $variant): void {
            if ($variant->wasRecentlyCreated || $variant->wasChanged(['product_id', 'is_active'])) {
                app(CategoryRepository::class)->clearCatalogCache();
            }
        });

        self::deleted(static function (): void {
            app(CategoryRepository::class)->clearCatalogCache();
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

    public function scopeAvailable(Builder $query): void
    {
        $query->active()->whereHas('product', static fn (Builder $query): Builder => $query->available());
    }

    public function label(): string
    {
        return sprintf('%d %s', $this->value, $this->unit->getLabel());
    }

    public function hasDiscount(): bool
    {
        return $this->old_price !== null;
    }
}
