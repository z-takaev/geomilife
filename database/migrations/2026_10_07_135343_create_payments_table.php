<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('transaction_id')->nullable();
            $table->string('bank_order_id')->nullable()->unique();
            $table->text('payment_url')->nullable();
            $table->string('provider_status')->nullable();
            $table->string('operation_state')->nullable()->index();
            $table->text('error')->nullable();
            $table->timestamp('refund_requested_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('status');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'transaction_id']);
            $table->index(['order_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
