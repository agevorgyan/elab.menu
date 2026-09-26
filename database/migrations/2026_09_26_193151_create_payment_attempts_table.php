<?php

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
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscription_payments')->nullOnDelete();
            $table->string('gateway', 50);
            $table->string('merchant_reference', 100)->unique();
            $table->string('provider_transaction_id', 150)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('AMD');
            $table->string('status', 30)->default('created');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->unique(['gateway', 'provider_transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
