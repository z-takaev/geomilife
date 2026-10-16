<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\City;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $city = City::query()->where('slug', 'vladikavkaz')->firstOrFail();

        User::query()->updateOrCreate(
            ['email' => 'user@user.ru'],
            [
                'first_name' => 'Пользователь',
                'password' => Hash::make('user@user.ru'),
                'city_id' => $city->getKey(),
            ],
        );
    }
}
