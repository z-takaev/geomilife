<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CitySeeder::class,
            SettingSeeder::class,
            FilamentUserSeeder::class,
            UserSeeder::class,
            PageSeeder::class,
            CategorySeeder::class,
            SeoSeeder::class,
            ShippingBoxSeeder::class,
        ]);
    }
}
