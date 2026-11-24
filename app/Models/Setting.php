<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\NormalizedPhoneCast;
use App\Support\Email\NormalizedEmailCast;
use App\Support\Media\ImageMimeTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Setting extends Model implements HasMedia
{
    use InteractsWithMedia;

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

    protected $fillable = [
        'phone', 'email', 'address', 'working_hours',
        'admin_email', 'yandex_map_id',
        'footer_title', 'footer_description',
        'instagram_url', 'youtube_url', 'vk_url', 'telegram_url', 'whatsapp_url',
        'story_title', 'story_description', 'story_video_url',
        'benefits', 'privacy_policy', 'snow_enabled',
    ];

    protected function casts(): array
    {
        return [
            'snow_enabled' => 'boolean',
            'benefits' => 'array',
            'phone' => NormalizedPhoneCast::class,
            'email' => NormalizedEmailCast::class,
            'admin_email' => NormalizedEmailCast::class,
        ];
    }

    protected static function booted(): void
    {
        self::saved(static function (): void {
            Cache::memo()->forget((string) config('cache.items.settings.key'));
        });

        self::deleted(static function (): void {
            Cache::memo()->forget((string) config('cache.items.settings.key'));
        });
    }

    public static function cached(): ?self
    {
        return Cache::memo()->remember(
            (string) config('cache.items.settings.key'),
            (int) config('cache.items.settings.ttl'),
            static fn (): ?self => self::query()
                ->with('media')
                ->first(),
        );
    }

    public function privacyPolicyUrl(): ?string
    {
        return $this->privacy_policy ?
            Storage::disk('public')->url($this->privacy_policy) : null;
    }

    public function hasSocialLinks(): bool
    {
        return filled($this->instagram_url)
            || filled($this->youtube_url)
            || filled($this->vk_url)
            || filled($this->telegram_url)
            || filled($this->whatsapp_url);
    }

    public function yandexMapUrl(): ?string
    {
        if (! is_string($this->yandex_map_id) || preg_match('/\A[a-f0-9]{64}\z/', $this->yandex_map_id) !== 1) {
            return null;
        }

        return 'https://api-maps.yandex.ru/services/constructor/1.0/js/?'.http_build_query([
            'um' => 'constructor:'.$this->yandex_map_id,
            'width' => '100%',
            'height' => 400,
            'lang' => 'ru_RU',
            'scroll' => 'true',
        ], encoding_type: PHP_QUERY_RFC3986);
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
