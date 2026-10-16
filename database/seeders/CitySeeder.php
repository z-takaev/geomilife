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
            ['slug' => 'vladikavkaz'],
            ['name' => 'Владикавказ', 'is_active' => true],
        );
    }
}
