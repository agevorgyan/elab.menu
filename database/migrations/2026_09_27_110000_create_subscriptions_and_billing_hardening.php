<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create authoritative subscriptions table
        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->restrictOnDelete();
                $table->string('status', 30)->default('trialing'); // trialing, active, past_due, grace, cancelled, expired, suspended
                $table->string('billing_interval', 20)->default('monthly'); // monthly, yearly
                $table->timestamp('trial_starts_at')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('current_period_start')->nullable();
                $table->timestamp('current_period_end')->nullable();
                $table->timestamp('grace_ends_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('renewal_state', 30)->default('active'); // active, pending, failed, cancelled, none
                $table->boolean('auto_renew')->default(true);
                $table->string('last_payment_reference')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['vendor_id', 'status']);
            });
        }

        // 2. Add hardening columns to vendors table if missing
        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'grace_ends_at')) {
                $table->timestamp('grace_ends_at')->nullable()->after('subscription_expires_at');
            }
            if (! Schema::hasColumn('vendors', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('grace_ends_at');
            }
            if (! Schema::hasColumn('vendors', 'current_period_start')) {
                $table->timestamp('current_period_start')->nullable()->after('trial_ends_at');
            }
        });

        // 3. Add subscription_id relation to subscription_payments table if missing
        if (Schema::hasTable('subscription_payments') && ! Schema::hasColumn('subscription_payments', 'subscription_id')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->foreignId('subscription_id')->nullable()->after('vendor_id')->constrained('subscriptions')->nullOnDelete();
            });
        }

        // 4. Backfill existing vendors into authoritative subscriptions table
        if (Schema::hasTable('vendors') && Schema::hasTable('subscription_plans')) {
            $defaultPlanId = DB::table('subscription_plans')->where('slug', 'pro')->value('id')
                ?? DB::table('subscription_plans')->value('id');

            if ($defaultPlanId) {
                $vendors = DB::table('vendors')->get();
                foreach ($vendors as $vendor) {
                    $planId = $vendor->subscription_plan_id;
                    if (! $planId && ! empty($vendor->subscription_plan)) {
                        $planId = DB::table('subscription_plans')->where('slug', $vendor->subscription_plan)->value('id');
                    }
                    $planId = $planId ?? $defaultPlanId;

                    $existingSub = DB::table('subscriptions')->where('vendor_id', $vendor->id)->first();
                    if (! $existingSub) {
                        $subId = DB::table('subscriptions')->insertGetId([
                            'vendor_id' => $vendor->id,
                            'subscription_plan_id' => $planId,
                            'status' => $vendor->subscription_status ?? 'trialing',
                            'billing_interval' => 'monthly',
                            'trial_starts_at' => $vendor->created_at,
                            'trial_ends_at' => $vendor->trial_ends_at,
                            'current_period_start' => $vendor->created_at,
                            'current_period_end' => $vendor->subscription_expires_at,
                            'grace_ends_at' => null,
                            'cancelled_at' => null,
                            'renewal_state' => 'active',
                            'auto_renew' => true,
                            'created_at' => $vendor->created_at ?? now(),
                            'updated_at' => $vendor->updated_at ?? now(),
                        ]);

                        if (Schema::hasTable('subscription_payments') && Schema::hasColumn('subscription_payments', 'subscription_id')) {
                            DB::table('subscription_payments')
                                ->where('vendor_id', $vendor->id)
                                ->whereNull('subscription_id')
                                ->update(['subscription_id' => $subId]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('subscription_payments') && Schema::hasColumn('subscription_payments', 'subscription_id')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->dropForeign(['subscription_id']);
                $table->dropColumn('subscription_id');
            });
        }

        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'current_period_start')) {
                $table->dropColumn('current_period_start');
            }
            if (Schema::hasColumn('vendors', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
            if (Schema::hasColumn('vendors', 'grace_ends_at')) {
                $table->dropColumn('grace_ends_at');
            }
        });

        Schema::dropIfExists('subscriptions');
    }
};
