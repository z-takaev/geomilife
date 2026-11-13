<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\City;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $city = City::query()->where('cdek_code', 1082)->firstOrFail();

        User::query()->updateOrCreate(
            ['email' => 'user@user.ru'],
            [
                'first_name' => 'Иван',
                'last_name' => 'Иванов',
                'phone' => '+7 (988) 873-10-20',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('user@user.ru'),
                'city_id' => $city->getKey(),
            ],
        );
    }
}
