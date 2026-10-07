<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSortOrder;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class HomeSlide extends Model implements HasMedia
{
    use HasSortOrder, InteractsWithMedia;

    protected $fillable = [
        'link_url',
        'open_in_new_tab',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        foreach (['desktop_image', 'mobile_image'] as $collection) {
            $this->addMediaCollection($collection)
                ->acceptsMimeTypes(['image/webp'])
                ->singleFile();
        }
    }
}
