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
            $table->boolean('ai_waiter_enabled')->default(true);
            $table->string('ai_waiter_name', 100)->default('AI Մատուցող');
            $table->string('ai_waiter_avatar')->nullable();
            $table->text('ai_waiter_priority_ingredients')->nullable();
            $table->text('ai_waiter_welcome_text')->nullable();
            $table->json('ai_waiter_featured_product_ids')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'ai_waiter_enabled',
                'ai_waiter_name',
                'ai_waiter_avatar',
                'ai_waiter_priority_ingredients',
                'ai_waiter_welcome_text',
                'ai_waiter_featured_product_ids',
            ]);
        });
    }
};
