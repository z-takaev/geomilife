<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Support\Seo\SeoManager;

if (! function_exists('settings')) {
    function settings(): ?Setting
    {
        return once(static fn (): ?Setting => Setting::cached());
    }
}

if (! function_exists('seo')) {
    function seo(): SeoManager
    {
        return app(SeoManager::class);
    }
}
