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
    use HasFactory, HasSeo, HasSlug, InteractsWithMedia, InteractsWithRichContent;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'start_at',
        'end_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'date',
            'end_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        self::saved(static function (): void {
            Cache::forget((string) config('cache.items.promos.key'));
        });

        self::deleted(static function (): void {
            Cache::forget((string) config('cache.items.promos.key'));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeCurrent(Builder $query): void
    {
        $query
            ->where('is_active', true)
            ->where('start_at', '<=', today())
            ->where('end_at', '>=', today());
    }

    public function isAvailable(): bool
    {
        $today = today();

        return $this->is_active
            && $this->start_at !== null
            && $this->end_at !== null
            && $this->start_at->lte($today)
            && $this->end_at->gte($today);
    }

    /**
     * @return Collection<int, self>
     */
    public static function cached(): Collection
    {
        return Cache::remember(
            (string) config('cache.items.promos.key'),
            (int) config('cache.items.promos.ttl'),
            static fn (): Collection => self::query()
                ->current()
                ->with('media')
                ->latest('start_at')
                ->latest('id')
                ->limit(3)
                ->get(),
        );
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
