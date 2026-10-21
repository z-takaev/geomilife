<?php

declare(strict_types=1);

namespace App\Support\Seo\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface Seoable
{
    public function seo(): MorphOne;
}
