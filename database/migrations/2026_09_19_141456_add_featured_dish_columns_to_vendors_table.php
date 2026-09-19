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
            $table->foreignId('featured_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->boolean('featured_dish_enabled')->default(false);
            $table->string('featured_dish_badge', 100)->nullable();
            $table->string('featured_dish_subtitle', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropForeign(['featured_product_id']);
            $table->dropColumn([
                'featured_product_id',
                'featured_dish_enabled',
                'featured_dish_badge',
                'featured_dish_subtitle',
            ]);
        });
    }
};
