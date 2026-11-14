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
            $table->timestamps();

            $table->integer('cdek_code')->unique();
            $table->string('name');
            $table->string('region')->nullable();
            $table->string('sub_region')->nullable();

            $table->boolean('is_active')->default(false);
            $table->boolean('is_suggested')->default(false);
            $table->boolean('is_pickup_available')->default(false);
            $table->boolean('is_courier_available')->default(false);
            $table->boolean('is_cdek_excluded')->default(false);

            $table->unsignedBigInteger('door_delivery_price')->default(0);
            $table->unsignedBigInteger('courier_delivery_price')->nullable();
            $table->unsignedBigInteger('floor_lifting_price')->nullable();

            $table->text('pickup_address')->nullable();
            $table->string('pickup_working_hours')->nullable();
            $table->string('pickup_phone')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
