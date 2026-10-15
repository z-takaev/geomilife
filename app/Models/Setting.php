<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Media\ImageMimeTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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

    public function storyVideoEmbedUrl(): ?string
    {
        if (! $this->story_video_url) {
            return null;
        }

        $matches = [];
        $isDirectVkVideoUrl = preg_match(
            '~^https?://(?:www\.)?(?:vkvideo\.ru|vk\.com)/video(?<owner>-?\d+)_(?<video>\d+)(?:[/?#].*)?$~i',
            $this->story_video_url,
            $matches,
        );

        if ($isDirectVkVideoUrl !== 1) {
            return $this->story_video_url;
        }

        return 'https://vkvideo.ru/video_ext.php?'.http_build_query([
            'oid' => $matches['owner'],
            'id' => $matches['video'],
            'hd' => 3,
            'autoplay' => 0,
            'muted' => 0,
            'js_api' => 1,
        ]);
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

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('banner_wide')
            ->fit(Fit::Crop, 1290, 200)
            ->performOnCollections('banner_wide_image')
            ->nonQueued();

        $this->addMediaConversion('banner_wide_mobile')
            ->fit(Fit::Crop, 750, 116)
            ->performOnCollections('banner_wide_image')
            ->nonQueued();

        $this->addMediaConversion('banner_double')
            ->fit(Fit::Crop, 635, 180)
            ->performOnCollections('banner_double_first_image', 'banner_double_second_image')
            ->nonQueued();

        $this->addMediaConversion('banner_double_mobile')
            ->fit(Fit::Crop, 750, 212)
            ->performOnCollections('banner_double_first_image', 'banner_double_second_image')
            ->nonQueued();

        $this->addMediaConversion('banner_triple')
            ->fit(Fit::Crop, 417, 200)
            ->performOnCollections(
                'banner_triple_first_image',
                'banner_triple_second_image',
                'banner_triple_third_image',
            )
            ->nonQueued();

        $this->addMediaConversion('banner_triple_mobile')
            ->fit(Fit::Crop, 750, 360)
            ->performOnCollections(
                'banner_triple_first_image',
                'banner_triple_second_image',
                'banner_triple_third_image',
            )
            ->nonQueued();
    }
}
