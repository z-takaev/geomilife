<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Traits\HasSortOrder;
use Filament\Forms\Components\RichEditor\FileAttachmentProviders\SpatieMediaLibraryFileAttachmentProvider;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Faq extends Model implements HasMedia, HasRichContent
{
    use HasFactory, HasSortOrder, InteractsWithMedia, InteractsWithRichContent;

    protected $fillable = [
        'question',
        'answer',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('answer')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function setUpRichContent(): void
    {
        $this->registerRichContent('answer')
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentProvider(SpatieMediaLibraryFileAttachmentProvider::make());
    }
}
