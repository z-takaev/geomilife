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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Openplain\FilamentTreeView\Concerns\HasTreeStructure;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Category extends Model implements HasMedia, Seoable
{
    use HasFactory, HasSeo, HasSlug, HasTreeStructure, InteractsWithMedia;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        self::saved(static function (): void {
            app(CategoryRepository::class)->forget();
        });

        self::deleted(static function (): void {
            app(CategoryRepository::class)->forget();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function activeProducts(): HasMany
    {
        return $this->products()->available();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    public function canBeDeleted(): bool
    {
        return ! ($this->children_exists ?? $this->children()->exists())
            && ! ($this->products_exists ?? $this->products()->exists());
    }

    public function totalActiveProductsCount(): int
    {
        return (int) (
            $this->active_products_count
            + $this->children->sum('active_products_count')
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->acceptsMimeTypes(ImageMimeTypes::ALLOWED)
            ->singleFile();
    }

    protected function getSlugSourceAttribute(): string
    {
        return 'name';
    }
}
