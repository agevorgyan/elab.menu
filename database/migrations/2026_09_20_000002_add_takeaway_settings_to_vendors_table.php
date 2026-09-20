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
            $table->boolean('takeaway_enabled')->default(true)->after('delivery_free_from');
            $table->decimal('takeaway_min_amount', 10, 2)->default(0)->after('takeaway_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'takeaway_enabled',
                'takeaway_min_amount',
            ]);
        });
    }
};
