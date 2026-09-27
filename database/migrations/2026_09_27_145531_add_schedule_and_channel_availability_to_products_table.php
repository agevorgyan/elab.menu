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
            $table->time('available_start_time')->nullable()->after('is_available');
            $table->time('available_end_time')->nullable()->after('available_start_time');
            $table->json('available_days')->nullable()->after('available_end_time');
            $table->boolean('available_for_dine_in')->default(true)->after('available_days');
            $table->boolean('available_for_takeaway')->default(true)->after('available_for_dine_in');
            $table->boolean('available_for_delivery')->default(true)->after('available_for_takeaway');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'available_start_time',
                'available_end_time',
                'available_days',
                'available_for_dine_in',
                'available_for_takeaway',
                'available_for_delivery',
            ]);
        });
    }
};
