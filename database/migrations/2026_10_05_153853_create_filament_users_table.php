<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filament_users', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();

            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default(FilamentUserRoleEnum::MANAGER);

            $table->rememberToken();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filament_users');
    }
};
