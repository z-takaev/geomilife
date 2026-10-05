<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class SettingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $setting = Setting::query()->firstOrNew();

        $setting->fill([
            'phone' => $setting->phone ?? '+7-9888-731-020',
            'email' => $setting->email ?? 'geomilife@bk.ru',
            'address' => $setting->address ?? 'ул. Тамаева 35, Владикавказ',
        ]);

        $setting->save();
    }
}
