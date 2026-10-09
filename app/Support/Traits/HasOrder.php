<?php

declare(strict_types=1);

namespace App\Support\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasOrder
{
    protected static function bootHasOrder(): void
    {
        static::creating(static function (Model $model): void {
            if ($model->getAttribute('order') !== null) {
                return;
            }

            $model->setAttribute(
                'order',
                ((int) $model->newQuery()->max('order')) + 1,
            );
        });
    }
}
