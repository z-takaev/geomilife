<?php

declare(strict_types=1);

namespace App\Support\Seo\Traits;

use App\Models\Seo;
use App\Support\Seo\Contracts\Seoable;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSeo
{
    protected static function bootHasSeo(): void
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
