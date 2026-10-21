<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->integer('cdek_code')->unique();
            $table->string('name');
            $table->string('region')->nullable();
            $table->string('sub_region')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_pickup_available')->default(false);
            $table->boolean('is_courier_available')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
