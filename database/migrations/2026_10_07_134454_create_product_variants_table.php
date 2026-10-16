<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('unit');
            $table->decimal('value', 12, 3);
            $table->unsignedBigInteger('price');
            $table->unsignedTinyInteger('discount_percent')->nullable();
            $table->string('status');
            $table->boolean('is_active')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'is_active', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
