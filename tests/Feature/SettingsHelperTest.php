<?php

declare(strict_types=1);

use App\Models\Setting;

it('возвращает актуальные настройки после создания изменения и удаления', function (): void {
    expect(settings())->toBeNull();

    $setting = Setting::query()->create(['snow_enabled' => true]);

    expect(settings()?->snow_enabled)->toBeTrue();

    $setting->update(['snow_enabled' => false]);

    expect(settings()?->snow_enabled)->toBeFalse();

    $setting->delete();

    expect(settings())->toBeNull();
});
