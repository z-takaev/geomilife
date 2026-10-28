<?php

declare(strict_types=1);

use App\Models\City;
use App\Models\Setting;
use App\Services\Locations\LocationService;
use App\Support\Seo\SeoManager;

if (! function_exists('current_city')) {
    function current_city(): ?City
    {
        return app(LocationService::class)->current();
    }
}

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
