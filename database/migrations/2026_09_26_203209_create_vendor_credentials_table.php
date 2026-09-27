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
        Schema::create('vendor_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('credential_type', 50);
            $table->text('encrypted_value');
            $table->json('metadata')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('rotated_at')->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'provider', 'credential_type'], 'vendor_cred_unique');
            $table->index(['vendor_id', 'provider'], 'vendor_cred_provider_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_credentials');
    }
};
