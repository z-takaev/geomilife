<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->string('author_name');
            $table->text('text');
            $table->decimal('rating', 2, 1);
            $table->string('source')->nullable();
            $table->date('reviewed_at');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'reviewed_at', 'id'], 'reviews_active_reviewed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
