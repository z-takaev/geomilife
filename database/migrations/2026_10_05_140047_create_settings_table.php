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
            $table->timestamps();

            $table->string('admin_email')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('working_hours')->nullable();
            $table->string('yandex_map_id', 64)->nullable();

            $table->string('instagram_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('vk_url')->nullable();
            $table->string('telegram_url')->nullable();
            $table->string('whatsapp_url')->nullable();

            $table->json('benefits')->nullable();

            $table->string('story_title')->nullable();
            $table->text('story_description')->nullable();
            $table->string('story_video_url')->nullable();

            $table->string('footer_title')->nullable();
            $table->string('footer_description')->nullable();
            $table->string('privacy_policy')->nullable();
            $table->boolean('snow_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
