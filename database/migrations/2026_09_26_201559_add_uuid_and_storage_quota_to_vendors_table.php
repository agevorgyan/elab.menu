<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->unsignedBigInteger('storage_limit_bytes')->default(104857600)->after('subscription_expires_at'); // 100MB default
            $table->unsignedBigInteger('storage_used_bytes')->default(0)->after('storage_limit_bytes');
            $table->unsignedInteger('storage_files_count')->default(0)->after('storage_used_bytes');
        });

        // Backfill UUIDs for any existing vendors
        $existingVendors = DB::table('vendors')->whereNull('uuid')->get();
        foreach ($existingVendors as $vendor) {
            DB::table('vendors')
                ->where('id', $vendor->id)
                ->update(['uuid' => (string) Str::uuid()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'uuid',
                'storage_limit_bytes',
                'storage_used_bytes',
                'storage_files_count',
            ]);
        });
    }
};
