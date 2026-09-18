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
            $table->index(['vendor_id', 'created_at']);
            $table->index(['vendor_id', 'status']);
        });

        Schema::table('analytics_logs', function (Blueprint $table) {
            $table->index(['vendor_id', 'visit_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['vendor_id', 'created_at']);
            $table->dropIndex(['vendor_id', 'status']);
        });

        Schema::table('analytics_logs', function (Blueprint $table) {
            $table->dropIndex(['vendor_id', 'visit_date']);
        });
    }
};
