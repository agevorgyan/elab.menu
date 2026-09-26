<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('location_product_overrides', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('id')->constrained('vendors')->cascadeOnDelete();
        });

        // Backfill vendor_id from locations
        DB::statement('UPDATE location_product_overrides SET vendor_id = (SELECT vendor_id FROM locations WHERE locations.id = location_product_overrides.location_id)');

        Schema::table('location_product_overrides', function (Blueprint $table) {
            $table->unique(['vendor_id', 'location_id', 'product_id'], 'loc_prod_override_vendor_loc_prod_unique');
        });
    }

    public function down(): void
    {
        Schema::table('location_product_overrides', function (Blueprint $table) {
            $table->dropUnique('loc_prod_override_vendor_loc_prod_unique');
            $table->dropForeign(['vendor_id']);
            $table->dropColumn('vendor_id');
        });
    }
};
