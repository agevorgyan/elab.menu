<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('vendor_id')->constrained('locations')->nullOnDelete();
        });

        // Backfill location_id for existing customers
        $customers = DB::table('customers')->get();
        foreach ($customers as $customer) {
            // Find location_id from customer's orders
            $orderLocationId = DB::table('orders')
                ->where('customer_id', $customer->id)
                ->whereNotNull('location_id')
                ->value('location_id');

            if (!$orderLocationId) {
                // Fallback to vendor's first location
                $orderLocationId = DB::table('locations')
                    ->where('vendor_id', $customer->vendor_id)
                    ->value('id');
            }

            if ($orderLocationId) {
                DB::table('customers')
                    ->where('id', $customer->id)
                    ->update(['location_id' => $orderLocationId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });
    }
};
