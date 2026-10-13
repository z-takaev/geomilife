<?php

declare(strict_types=1);

namespace App\Observers;

use App\Contracts\Seoable;
use Illuminate\Database\Eloquent\Model;

final class SeoableObserver
{
    public function deleted(Model $model): void
    {
        if ($model instanceof Seoable) {
            $model->seoMetadata()->delete();
        }
    }
}
