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
            $table->boolean('service_fee_enabled')->default(false)->after('currency');
            $table->enum('service_fee_type', ['percent', 'fixed'])->default('percent')->after('service_fee_enabled');
            $table->decimal('service_fee_value', 10, 2)->default(0)->after('service_fee_type');
            $table->decimal('service_fee_min_order', 10, 2)->nullable()->after('service_fee_value');

            $table->boolean('delivery_enabled')->default(true)->after('service_fee_min_order');
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('delivery_enabled');
            $table->decimal('delivery_min_amount', 10, 2)->default(0)->after('delivery_fee');
            $table->decimal('delivery_free_from', 10, 2)->nullable()->after('delivery_min_amount');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->after('type');
            $table->decimal('service_fee', 10, 2)->default(0)->after('subtotal');
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('service_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'service_fee_enabled',
                'service_fee_type',
                'service_fee_value',
                'service_fee_min_order',
                'delivery_enabled',
                'delivery_fee',
                'delivery_min_amount',
                'delivery_free_from',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal',
                'service_fee',
                'delivery_fee',
            ]);
        });
    }
};
