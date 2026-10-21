<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

final class CitySeeder extends Seeder
{
    public function run(): void
    {
        City::query()->updateOrCreate(
            ['cdek_code' => 1082],
            [
                'name' => 'Владикавказ',
                'region' => 'Республика Северная Осетия - Алания',
                'sub_region' => 'городской округ Владикавказ',
                'is_active' => true,
                'is_pickup_available' => true,
                'is_courier_available' => true,
            ],
        );
    }
}
