<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->text('banner_wide_url')->nullable();
            $table->text('banner_double_first_url')->nullable();
            $table->text('banner_double_second_url')->nullable();
            $table->text('banner_triple_first_url')->nullable();
            $table->text('banner_triple_second_url')->nullable();
            $table->text('banner_triple_third_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn([
                'banner_wide_url',
                'banner_double_first_url',
                'banner_double_second_url',
                'banner_triple_first_url',
                'banner_triple_second_url',
                'banner_triple_third_url',
            ]);
        });
    }
};
