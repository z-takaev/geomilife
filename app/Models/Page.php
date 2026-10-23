<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Media\ImageMimeTypes;
use App\Support\Seo\Contracts\Seoable;
use App\Support\Seo\Traits\HasSeo;
use Filament\Forms\Components\RichEditor\FileAttachmentProviders\SpatieMediaLibraryFileAttachmentProvider;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class Page extends Model implements HasMedia, HasRichContent, Seoable
{
    use HasSeo, InteractsWithMedia, InteractsWithRichContent;

    protected $fillable = [
        'key',
        'title',
        'slug',
        'content',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(ImageMimeTypes::ALLOWED);

        $this->addMediaCollection('content')
            ->acceptsMimeTypes(ImageMimeTypes::ALLOWED);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->fit(Fit::Crop, 600, 400)
            ->performOnCollections('images')
            ->nonQueued();
    }

    public function setUpRichContent(): void
    {
        $this->registerRichContent('content')
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentProvider(SpatieMediaLibraryFileAttachmentProvider::make());
    }
}
