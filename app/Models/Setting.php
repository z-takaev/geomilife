<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Media\ImageMimeTypes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Setting extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * @var list<string>
     */
    public const array IMAGE_COLLECTIONS = [
        'story_image',
        'banner_wide_image',
        'banner_wide_mobile_image',
        'banner_double_first_image',
        'banner_double_first_mobile_image',
        'banner_double_second_image',
        'banner_double_second_mobile_image',
        'banner_triple_first_image',
        'banner_triple_first_mobile_image',
        'banner_triple_second_image',
        'banner_triple_second_mobile_image',
        'banner_triple_third_image',
        'banner_triple_third_mobile_image',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'phone',
        'email',
        'admin_email',
        'address',
        'working_hours',
        'yandex_map_script',
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

    protected static function booted(): void
    {
        self::saved(static function (): void {
            Cache::forget((string) config('cache.items.settings.key'));
        });

        self::deleted(static function (): void {
            Cache::forget((string) config('cache.items.settings.key'));
        });
    }

    protected function phone(): Attribute
    {
        return Attribute::get(static function (?string $value): ?string {
            if ($value === null) {
                return null;
            }

            $digits = preg_replace('/\D/', '', $value);

            if ($digits === null || strlen($digits) !== 11 || ! in_array($digits[0], ['7', '8'], true)) {
                return $value;
            }

            return sprintf(
                '+7 (%s) %s-%s-%s',
                substr($digits, 1, 3),
                substr($digits, 4, 3),
                substr($digits, 7, 2),
                substr($digits, 9, 2),
            );
        });
    }

    public static function cached(): ?self
    {
        return Cache::remember(
            (string) config('cache.items.settings.key'),
            (int) config('cache.items.settings.ttl'),
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

    public function registerMediaCollections(): void
    {
        foreach (self::IMAGE_COLLECTIONS as $collection) {
            $this->addMediaCollection($collection)
                ->acceptsMimeTypes(ImageMimeTypes::ALLOWED)
                ->singleFile();
        }
    }
}
