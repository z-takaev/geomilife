<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Seoable;
use App\Support\Media\ImageMimeTypes;
use App\Support\Traits\HasSlug;
use App\Support\Traits\InteractsWithSeoMetadata;
use Filament\Forms\Components\RichEditor\FileAttachmentProviders\SpatieMediaLibraryFileAttachmentProvider;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class Promo extends Model implements HasMedia, HasRichContent, Seoable
{
    use HasFactory, HasSlug, InteractsWithMedia, InteractsWithRichContent, InteractsWithSeoMetadata;

    private const string CACHE_KEY = 'latest_promos';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'start_at',
        'end_at',
        'is_active',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return Collection<int, self>
     */
    public static function cached(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addHours(2),
            static fn (): Collection => self::query()
                ->where('is_active', true)
                ->with('media')
                ->latest('start_at')
                ->latest('id')
                ->limit(3)
                ->get(),
        );
    }

    protected static function booted(): void
    {
        self::saved(static function (): void {
            Cache::forget(self::CACHE_KEY);
        });

        self::deleted(static function (): void {
            Cache::forget(self::CACHE_KEY);
        });
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
            'start_at' => 'date',
            'end_at' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
