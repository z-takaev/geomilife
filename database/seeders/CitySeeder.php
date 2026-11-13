<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\City;
use App\Support\Values\Money;
use Illuminate\Database\Seeder;

final class CitySeeder extends Seeder
{
    public function run(): void
    {
        City::query()->firstOrCreate(
            ['cdek_code' => 1082],
            [
                'name' => 'Владикавказ',
                'region' => 'Республика Северная Осетия - Алания',
                'sub_region' => 'городской округ Владикавказ',
                'is_active' => true,
                'is_pickup_available' => true,
                'is_courier_available' => true,
                'is_cdek_excluded' => true,
                'courier_delivery_price' => Money::fromRubles(300),
                'door_delivery_price' => Money::fromRubles(100),
                'floor_lifting_price' => Money::fromRubles(50),
                'pickup_address' => 'ул. Тамаева 35, Владикавказ',
                'pickup_working_hours' => 'Пн–Пт 09:00–18:00',
                'pickup_phone' => '+7 (988) 873-10-20',
            ],
        );
    }
}
