<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Media\ImageMimeTypes;
use App\Support\Seo\Contracts\Seoable;
use App\Support\Seo\Traits\HasSeo;
use App\Support\Traits\HasSlug;
use Filament\Forms\Components\RichEditor\FileAttachmentProviders\SpatieMediaLibraryFileAttachmentProvider;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class News extends Model implements HasMedia, HasRichContent, Seoable
{
    use HasFactory, HasSeo, HasSlug, InteractsWithMedia, InteractsWithRichContent;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'published_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): void
    {
        $query
            ->where('is_active', true)
            ->where('published_at', '<=', today());
    }

    public function isAvailable(): bool
    {
        return $this->is_active
            && $this->published_at !== null
            && $this->published_at->lte(today());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->acceptsMimeTypes(ImageMimeTypes::ALLOWED)
            ->singleFile();

        $this->addMediaCollection('content')
            ->acceptsMimeTypes(ImageMimeTypes::ALLOWED);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 820, 546)
            ->performOnCollections('image')
            ->nonQueued();
    }

    public function setUpRichContent(): void
    {
        $this->registerRichContent('content')
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentProvider(SpatieMediaLibraryFileAttachmentProvider::make());
    }
}
