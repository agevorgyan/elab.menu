<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->after('subscription_plan')->constrained('subscription_plans')->nullOnDelete();
            $table->string('subscription_status', 20)->default('trialing')->after('subscription_plan_id'); // trialing, active, expired, cancelled
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->timestamp('subscription_expires_at')->nullable()->after('trial_ends_at');
            $table->text('custom_plan_notes')->nullable()->after('subscription_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropColumn([
                'subscription_plan_id',
                'subscription_status',
                'trial_ends_at',
                'subscription_expires_at',
                'custom_plan_notes',
            ]);
        });
    }
};
