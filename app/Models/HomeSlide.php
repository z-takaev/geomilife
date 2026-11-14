<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Media\ImageMimeTypes;
use App\Support\Traits\HasOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class HomeSlide extends Model implements HasMedia
{
    use HasFactory;
    use HasOrder;
    use InteractsWithMedia;

    protected $fillable = [
        'link_url', 'order',
        'open_in_new_tab', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'is_active' => 'boolean',
            'open_in_new_tab' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        self::saved(static function (): void {
            Cache::forget((string) config('cache.items.home_slides.key'));
        });

        self::deleted(static function (): void {
            Cache::forget((string) config('cache.items.home_slides.key'));
        });
    }

    public static function cached(): Collection
    {
        return Cache::remember(
            (string) config('cache.items.home_slides.key'),
            (int) config('cache.items.home_slides.ttl'),
            static fn (): Collection => self::query()
                ->where('is_active', true)
                ->with('media')
                ->orderBy('order')
                ->get(),
        );
    }

    public function registerMediaCollections(): void
    {
        foreach (['desktop_image', 'mobile_image'] as $collection) {
            $this->addMediaCollection($collection)
                ->acceptsMimeTypes(ImageMimeTypes::ALLOWED)
                ->singleFile();
        }
    }
}
