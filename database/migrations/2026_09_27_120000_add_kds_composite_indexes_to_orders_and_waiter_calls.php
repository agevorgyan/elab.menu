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
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['vendor_id', 'location_id', 'status', 'created_at'], 'idx_orders_kds_feed');
        });

        Schema::table('waiter_calls', function (Blueprint $table) {
            $table->index(['vendor_id', 'location_id', 'status', 'created_at'], 'idx_waiter_calls_kds_feed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_kds_feed');
        });

        Schema::table('waiter_calls', function (Blueprint $table) {
            $table->dropIndex('idx_waiter_calls_kds_feed');
        });
    }
};
