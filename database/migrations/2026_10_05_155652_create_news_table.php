<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->text('content')->default('');
            $table->timestamp('published_at');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'published_at', 'id'], 'news_active_published_idx');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
