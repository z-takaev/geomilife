<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Setting extends Model
{
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
        'story_image',
        'story_video_url',
        'banner_wide_image',
        'banner_double_first_image',
        'banner_double_second_image',
        'banner_triple_first_image',
        'banner_triple_second_image',
        'banner_triple_third_image',
    ];
}
