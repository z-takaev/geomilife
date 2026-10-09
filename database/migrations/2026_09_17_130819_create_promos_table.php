<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->text('content')->nullable();
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'start_at', 'id'], 'promos_active_start_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};
