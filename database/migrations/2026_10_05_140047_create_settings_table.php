<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            $table->string('footer_title')->nullable();
            $table->text('footer_description')->nullable();
            $table->text('instagram_url')->nullable();
            $table->text('youtube_url')->nullable();
            $table->text('vk_url')->nullable();
            $table->text('telegram_url')->nullable();
            $table->text('whatsapp_url')->nullable();

            $table->string('story_title')->nullable();
            $table->text('story_description')->nullable();
            $table->string('story_image')->nullable();
            $table->text('story_video_url')->nullable();

            $table->string('banner_wide_image')->nullable();
            $table->string('banner_double_first_image')->nullable();
            $table->string('banner_double_second_image')->nullable();
            $table->string('banner_triple_first_image')->nullable();
            $table->string('banner_triple_second_image')->nullable();
            $table->string('banner_triple_third_image')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
