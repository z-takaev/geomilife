<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductAttribute extends Model
{
    use HasFactory;
    use HasSlug;

    protected $fillable = [
        'name', 'slug',
    ];

    public function productValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function canBeDeleted(): bool
    {
        if (array_key_exists('product_values_count', $this->attributes)) {
            return (int) $this->product_values_count === 0;
        }

        return ! $this->productValues()->exists();
    }

    protected function getSlugSourceAttribute(): string
    {
        return 'name';
    }
}
