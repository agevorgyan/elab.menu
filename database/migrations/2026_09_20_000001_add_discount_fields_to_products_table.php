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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('discount_price', 10, 2)->nullable()->after('price');
            $table->json('discount_days')->nullable()->after('discount_price'); // e.g. ["mon", "tue", "wed", "thu", "fri"]
            $table->time('discount_start_time')->nullable()->after('discount_days'); // e.g. "12:00:00"
            $table->time('discount_end_time')->nullable()->after('discount_start_time'); // e.g. "16:00:00"
            $table->boolean('is_discount_active')->default(true)->after('discount_end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'discount_price',
                'discount_days',
                'discount_start_time',
                'discount_end_time',
                'is_discount_active',
            ]);
        });
    }
};
