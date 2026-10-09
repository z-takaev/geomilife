<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Media\ImageMimeTypes;
use App\Support\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Openplain\FilamentTreeView\Concerns\HasTreeStructure;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Category extends Model implements HasMedia
{
    use HasFactory, HasSlug, HasTreeStructure, InteractsWithMedia;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'order',
        'is_active',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function canBeDeleted(): bool
    {
        return ! ($this->children_exists ?? $this->children()->exists())
            && ! ($this->products_exists ?? $this->products()->exists());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->acceptsMimeTypes(ImageMimeTypes::ALLOWED)
            ->singleFile();
    }

    protected function getSlugSourceAttribute(): string
    {
        return 'name';
    }

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
