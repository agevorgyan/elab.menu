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
            $table->string('lifecycle_status', 32)->default('active')->after('is_active');
            $table->timestamp('termination_requested_at')->nullable()->after('lifecycle_status');
            $table->foreignId('termination_requested_by')->nullable()->after('termination_requested_at')->constrained('users')->nullOnDelete();
            $table->text('termination_reason')->nullable()->after('termination_requested_by');
            $table->timestamp('retention_ends_at')->nullable()->after('termination_reason');
            $table->timestamp('deletion_queued_at')->nullable()->after('retention_ends_at');
            $table->softDeletes();

            $table->index('lifecycle_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex(['lifecycle_status']);
            $table->dropForeign(['termination_requested_by']);
            $table->dropColumn([
                'lifecycle_status',
                'termination_requested_at',
                'termination_requested_by',
                'termination_reason',
                'retention_ends_at',
                'deletion_queued_at',
                'deleted_at',
            ]);
        });
    }
};
