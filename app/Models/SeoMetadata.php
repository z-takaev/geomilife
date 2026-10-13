<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class SeoMetadata extends Model
{
    protected $fillable = [
        'route_name',
        'title',
        'description',
    ];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
