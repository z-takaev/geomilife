<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Seoable;
use App\Support\Media\ImageMimeTypes;
use App\Support\Traits\HasSlug;
use App\Support\Traits\WithSeo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class Product extends Model implements HasMedia, Seoable
{
    use HasFactory, HasSlug, InteractsWithMedia, WithSeo;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'is_new',
        'is_hit',
        'is_active',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('order');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
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

    protected function casts(): array
    {
        return [
            'is_new' => 'boolean',
            'is_hit' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
