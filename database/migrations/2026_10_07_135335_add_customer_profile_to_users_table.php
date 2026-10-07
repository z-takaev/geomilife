<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->renameColumn('name', 'first_name');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('last_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone')->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('telegram_chat_id')->nullable();
            $table->string('telegram_username')->nullable();
            $table->boolean('telegram_notifications_enabled')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('city_id');
            $table->dropColumn([
                'last_name',
                'birth_date',
                'phone',
                'phone_verified_at',
                'is_blocked',
                'telegram_chat_id',
                'telegram_username',
                'telegram_notifications_enabled',
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->renameColumn('first_name', 'name');
        });
    }
};
