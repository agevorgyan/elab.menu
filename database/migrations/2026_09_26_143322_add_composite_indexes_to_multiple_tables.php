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
        $tables = ['orders', 'waiter_calls', 'customers', 'users', 'analytics_logs'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    try {
                        $table->index(['vendor_id', 'location_id']);
                    } catch (\Exception $e) {
                        // Index might already exist
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['orders', 'waiter_calls', 'customers', 'users', 'analytics_logs'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    try {
                        $table->dropIndex(['vendor_id', 'location_id']);
                    } catch (\Exception $e) {
                        // Index might not exist
                    }
                });
            }
        }
    }
};
