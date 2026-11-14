<?php

declare(strict_types=1);

use App\Models\City;
use App\Models\Setting;
use App\Services\Cities\CurrentCityService;
use App\Support\Phone\PhoneFormat;
use App\Support\Seo\SeoManager;

if (! function_exists('current_city')) {
    function current_city(): ?City
    {
        return app(CurrentCityService::class)->current();
    }
}

if (! function_exists('settings')) {
    function settings(): ?Setting
    {
        return Setting::cached();
    }
}

if (! function_exists('seo')) {
    function seo(): SeoManager
    {
        return app(SeoManager::class);
    }
}

if (! function_exists('phone_href')) {
    function phone_href(?string $phone): string
    {
        $normalizedPhone = PhoneFormat::normalize((string) $phone);

        return $normalizedPhone === '' ? '' : 'tel:'.$normalizedPhone;
    }
}
