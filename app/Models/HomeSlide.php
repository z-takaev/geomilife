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
    use HasFactory, HasOrder, InteractsWithMedia;

    private const string CACHE_KEY = 'home_slides';

    protected $fillable = [
        'link_url',
        'open_in_new_tab',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
            'order' => 'integer',
            'is_active' => 'boolean',
        ];
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
                ->orderBy('order')
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
        foreach (['desktop_image', 'mobile_image'] as $collection) {
            $this->addMediaCollection($collection)
                ->acceptsMimeTypes(ImageMimeTypes::ALLOWED)
                ->singleFile();
        }
    }
}
