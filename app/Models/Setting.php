<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Media\ImageMimeTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Setting extends Model implements HasMedia
{
    use InteractsWithMedia;

    private const string CACHE_KEY = 'site_setting';

    /**
     * @var list<string>
     */
    public const array IMAGE_COLLECTIONS = [
        'story_image',
        'banner_wide_image',
        'banner_double_first_image',
        'banner_double_second_image',
        'banner_triple_first_image',
        'banner_triple_second_image',
        'banner_triple_third_image',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'phone',
        'email',
        'address',
        'working_hours',
        'footer_title',
        'footer_description',
        'instagram_url',
        'youtube_url',
        'vk_url',
        'telegram_url',
        'whatsapp_url',
        'story_title',
        'story_description',
        'story_video_url',
    ];

    public static function cached(): ?self
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addHours(2),
            static fn (): ?self => self::query()
                ->with('media')
                ->first(),
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
        foreach (self::IMAGE_COLLECTIONS as $collection) {
            $this->addMediaCollection($collection)
                ->acceptsMimeTypes(ImageMimeTypes::ALLOWED)
                ->singleFile();
        }
    }
}
