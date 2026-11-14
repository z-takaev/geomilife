<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_documents', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type');
            $table->uuid('uuid')->nullable();
            $table->string('state')->nullable();
            $table->text('error')->nullable();

            $table->unique(['order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_documents');
    }
};
