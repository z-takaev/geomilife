<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Media\ImageMimeTypes;
use Illuminate\Database\Eloquent\Model;
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

    public function registerMediaCollections(): void
    {
        foreach (self::IMAGE_COLLECTIONS as $collection) {
            $this->addMediaCollection($collection)
                ->acceptsMimeTypes(ImageMimeTypes::ALLOWED)
                ->singleFile();
        }
    }
}
