<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FilamentUserSeeder::class,
            CitySeeder::class,
            UserSeeder::class,
            SettingSeeder::class,
            SeoSeeder::class,
            PageSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
