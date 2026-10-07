<?php

declare(strict_types=1);

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

if (! function_exists('settings')) {
    function settings(): ?Setting
    {
        return once(static fn (): ?Setting => Setting::query()->first());
    }
}

if (! function_exists('setting_image_url')) {
    function setting_image_url(?string $path, string $fallback): string
    {
        return filled($path) ? Storage::url($path) : asset($fallback);
    }
}
