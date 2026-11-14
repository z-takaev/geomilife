<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_boxes', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();

            $table->string('name');
            $table->unsignedInteger('length');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('weight');
            $table->boolean('is_active')->default(true);
        });

        DB::statement('ALTER TABLE shipping_boxes ADD CONSTRAINT shipping_boxes_dimensions_check CHECK (length > 10 AND width > 10 AND height > 10 AND weight > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_boxes');
    }
};
