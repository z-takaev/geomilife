<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FilamentUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class FilamentUserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        FilamentUser::query()->firstOrCreate([
            'email' => 'admin@admin.ru',
        ], [
            'name' => 'Admin',
            'password' => Hash::make('admin@admin.ru'),
        ]);
    }
}
