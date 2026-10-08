<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Traits\HasSlug;
use Filament\Forms\Components\RichEditor\FileAttachmentProviders\SpatieMediaLibraryFileAttachmentProvider;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class Article extends Model implements HasMedia, HasRichContent
{
    use HasFactory, HasSlug, InteractsWithMedia, InteractsWithRichContent;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'published_at',
        'is_active',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->acceptsMimeTypes(['image/webp'])
            ->singleFile();

        $this->addMediaCollection('content')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 410, 273)
            ->performOnCollections('image')
            ->nonQueued();
    }

    public function setUpRichContent(): void
    {
        $this->registerRichContent('content')
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentProvider(SpatieMediaLibraryFileAttachmentProvider::make());
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
