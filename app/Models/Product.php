<?php

declare(strict_types=1);

namespace App\Models;

use App\Repositories\Categories\CategoryRepository;
use App\Support\Media\ImageMimeTypes;
use App\Support\Seo\Contracts\Seoable;
use App\Support\Seo\Traits\HasSeo;
use App\Support\Traits\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class Product extends Model implements HasMedia, Seoable
{
    use HasFactory, HasSeo, HasSlug, InteractsWithMedia;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'is_new',
        'is_hit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_new' => 'boolean',
            'is_hit' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        self::saved(static function (self $product): void {
            if ($product->wasRecentlyCreated || $product->wasChanged(['category_id', 'is_active'])) {
                app(CategoryRepository::class)->forget();
            }
        });

        self::deleted(static function (): void {
            app(CategoryRepository::class)->forget();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('order')->orderBy('id');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->active();
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class)->orderBy('order');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('products.is_active', true);
    }

    public function scopeAvailable(Builder $query): void
    {
        $query
            ->active()
            ->whereHas('category', static fn (Builder $query): Builder => $query->visible())
            ->has('activeVariants');
    }

    public function scopeDiscounted(Builder $query): void
    {
        $query
            ->active()
            ->whereHas(
                'activeVariants',
                static fn (Builder $query): Builder => $query
                    ->where('discount_percent', '>', 0),
            );
    }

    public function selectedVariant(?int $variantId = null): ProductVariant
    {
        return $this->activeVariants->firstWhere('id', $variantId)
            ?? $this->activeVariants->firstOrFail();
    }

    public function hasDiscount(): bool
    {
        return $this->activeVariants->contains(
            static fn (ProductVariant $variant): bool => $variant->hasDiscount(),
        );
    }

    /**
     * @return Collection<int, Media>
     */
    public function images(): Collection
    {
        return $this->getMedia('image')
            ->merge($this->getMedia('gallery'))
            ->unique(static fn (Media $media): int => $media->getKey())
            ->values();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->acceptsMimeTypes(ImageMimeTypes::ALLOWED)
            ->singleFile();

        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(ImageMimeTypes::ALLOWED);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')
            ->fit(Fit::Fill, 600, 600)
            ->performOnCollections('image')
            ->nonQueued();
    }

    protected function getSlugSourceAttribute(): string
    {
        return 'name';
    }
}
