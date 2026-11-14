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
            $table->timestamps();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('city_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->unsignedBigInteger('number')->nullable()->unique();

            $table->string('status');
            $table->string('payment_status');
            $table->string('delivery_method');
            $table->string('payment_method');

            $table->json('delivery_details')->nullable();
            $table->json('packages')->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->string('checkout_session_hash', 64)->nullable();
            $table->boolean('is_preorder')->default(false);

            $table->unsignedBigInteger('delivery_price');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('total');

            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email');
            $table->json('delivery_address');
            $table->text('comment')->nullable();

            $table->string('cancellation_state')->nullable()->index();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
