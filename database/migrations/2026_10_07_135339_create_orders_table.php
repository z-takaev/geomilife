<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('number')->unique();
            $table->string('status');
            $table->string('payment_status');
            $table->foreignId('delivery_method_id')->constrained()->restrictOnDelete();
            $table->decimal('delivery_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email');
            $table->json('delivery_address');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
