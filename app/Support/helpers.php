<?php

declare(strict_types=1);

use App\Models\Setting;

if (! function_exists('settings')) {
    function settings(): ?Setting
    {
        return once(static fn (): ?Setting => Setting::query()->first());
    }
}
