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
            $table->timestamps();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('sku')->unique();
            $table->string('unit');
            $table->unsignedInteger('value');
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('old_price')->nullable();
            $table->unsignedInteger('weight')->nullable();
            $table->unsignedInteger('length')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->boolean('is_active')->default(false);
            $table->boolean('is_preorder')->default(false);
            $table->integer('order')->default(0);

            $table->index(['product_id', 'is_active', 'order']);
        });

        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_value_check CHECK (value > 0 AND value <= 999999999)');
        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_price_check CHECK (price > 0 AND price % 100 = 0)');
        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_transport_check CHECK ((weight IS NULL OR weight > 0) AND (length IS NULL OR length > 0) AND (width IS NULL OR width > 0) AND (height IS NULL OR height > 0))');
        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_old_price_check CHECK (old_price IS NULL OR (old_price > price AND old_price % 100 = 0))');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
