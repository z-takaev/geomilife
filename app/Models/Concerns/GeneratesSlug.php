<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait GeneratesSlug
{
    protected static function bootGeneratesSlug(): void
    {
        static::saving(static function (Model $model): void {
            if (filled($model->getAttribute('slug'))) {
                return;
            }

            $model->setAttribute('slug', $model->generateUniqueSlug());
        });
    }

    private function generateUniqueSlug(): string
    {
        $baseSlug = Str::slug((string) $this->getAttribute('title'));
        $baseSlug = $baseSlug !== '' ? $baseSlug : Str::lower(Str::random(8));

        $existingSlugs = $this->newQuery()
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->where(function ($query) use ($baseSlug): void {
                $query
                    ->where('slug', $baseSlug)
                    ->orWhere('slug', 'like', $baseSlug.'-%');
            })
            ->pluck('slug')
            ->flip();

        if (! $existingSlugs->has($baseSlug)) {
            return $baseSlug;
        }

        for ($suffix = 2; ; $suffix++) {
            $slug = "{$baseSlug}-{$suffix}";

            if (! $existingSlugs->has($slug)) {
                return $slug;
            }
        }
    }
}
