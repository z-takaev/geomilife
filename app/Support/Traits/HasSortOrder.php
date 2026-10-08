<?php

declare(strict_types=1);

namespace App\Support\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasSortOrder
{
    protected static function bootHasSortOrder(): void
    {
        static::creating(static function (Model $model): void {
            if ($model->getAttribute('sort_order') !== null) {
                return;
            }

            $model->setAttribute(
                'sort_order',
                ((int) $model->newQuery()->max('sort_order')) + 1,
            );
        });
    }
}
