<?php

declare(strict_types=1);

namespace App\Support\Traits;

use App\Contracts\Seoable;
use App\Models\Seo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait WithSeo
{
    protected static function bootWithSeo(): void
    {
        static::deleted(static function (Seoable $model): void {
            $model->seo()->delete();
        });
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(Seo::class, 'seoable');
    }
}
