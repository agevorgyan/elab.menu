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
        Schema::table('vendors', function (Blueprint $table) {
            $table->json('ai_waiter_config')->nullable()->after('ai_waiter_featured_product_ids');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('ai_priority')->default(false)->after('is_featured');
            $table->unsignedTinyInteger('ai_priority_level')->default(0)->after('ai_priority');
            $table->string('ai_group', 50)->nullable()->after('ai_priority_level');
            $table->json('ai_tags')->nullable()->after('ai_group');
            $table->unsignedTinyInteger('ai_spicy_level')->default(0)->after('ai_tags');
            $table->json('ai_pairs_with')->nullable()->after('ai_spicy_level');
            $table->boolean('ai_enabled')->default(true)->after('ai_pairs_with');
        });

        Schema::create('ai_waiter_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('session_token', 64)->unique();
            $table->string('table_number', 50)->nullable();
            $table->string('language', 10)->default('hy');
            $table->string('status', 30)->default('started'); // started, questions_in_progress, recommended, added_to_cart, order_placed, abandoned
            $table->json('preferences')->nullable();
            $table->json('questions_history')->nullable();
            $table->json('answers_history')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('cart_items')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->decimal('total_order_amount', 12, 2)->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['vendor_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_waiter_sessions');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'ai_priority',
                'ai_priority_level',
                'ai_group',
                'ai_tags',
                'ai_spicy_level',
                'ai_pairs_with',
                'ai_enabled',
            ]);
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('ai_waiter_config');
        });
    }
};
