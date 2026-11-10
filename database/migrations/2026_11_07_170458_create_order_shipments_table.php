<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_shipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('cdek_number')->nullable()->index();
            $table->string('status')->nullable();
            $table->string('request_state')->nullable();
            $table->timestamp('status_at')->nullable();
            $table->string('operation_state')->nullable()->index();
            $table->text('error')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shipments');
    }
};
