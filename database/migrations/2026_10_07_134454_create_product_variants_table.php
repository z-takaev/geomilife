<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->unsignedBigInteger('old_price')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_preorder')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'is_active', 'order']);
        });

        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_price_check CHECK (price > 0 AND price % 100 = 0)');
        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_old_price_check CHECK (old_price IS NULL OR (old_price > price AND old_price % 100 = 0))');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
