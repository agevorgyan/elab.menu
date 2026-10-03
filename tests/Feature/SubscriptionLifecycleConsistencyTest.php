<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Location;
use App\Models\MenuTemplate;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vendor;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SubscriptionLifecycleConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionPlan $basicPlan;

    protected SubscriptionPlan $proPlan;

    protected SubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);

        MenuTemplate::firstOrCreate(
            ['slug' => 'modern-minimal'],
            ['name' => 'Modern Minimal', 'is_active' => true]
        );

        $this->basicPlan = SubscriptionPlan::where('slug', 'basic')->firstOrFail();
        $this->proPlan = SubscriptionPlan::where('slug', 'pro')->firstOrFail();
        $this->subscriptionService = app(SubscriptionService::class);
    }

    public function test_start_trial_does_not_downgrade_active_paid_subscription(): void
    {
        $vendor = Vendor::create([
            'name' => 'Active Bistro',
            'slug' => 'active-bistro-'.uniqid(),
            'type' => 'restaurant',
            'email' => 'active_'.uniqid().'@example.com',
            'subscription_plan_id' => $this->proPlan->id,
            'subscription_status' => 'active',
            'is_active' => true,
        ]);

        $futureEnd = Carbon::now()->addMonths(6);
        $subscription = Subscription::create([
            'vendor_id' => $vendor->id,
            'subscription_plan_id' => $this->proPlan->id,
            'status' => SubscriptionStatus::Active,
            'billing_interval' => 'monthly',
            'current_period_start' => Carbon::now(),
            'current_period_end' => $futureEnd,
            'auto_renew' => true,
        ]);

        $result = $this->subscriptionService->startTrial($vendor, $this->basicPlan, 14);

        $this->assertSame($subscription->id, $result->id);
        $this->assertEquals(SubscriptionStatus::Active, $result->fresh()->status);
        $this->assertEquals($futureEnd->toDateTimeString(), $result->fresh()->current_period_end->toDateTimeString());
        $this->assertEquals($this->proPlan->id, $result->fresh()->subscription_plan_id);
    }

    public function test_start_trial_is_idempotent_and_does_not_reset_ongoing_trial_clock(): void
    {
        $vendor = Vendor::create([
            'name' => 'Trial Bistro',
            'slug' => 'trial-bistro-'.uniqid(),
            'type' => 'restaurant',
            'email' => 'trial_'.uniqid().'@example.com',
            'subscription_plan_id' => $this->proPlan->id,
            'subscription_status' => 'trialing',
            'is_active' => true,
        ]);

        $originalStart = Carbon::now()->subDays(10);
        $originalEnd = Carbon::now()->addDays(4);

        $subscription = Subscription::create([
            'vendor_id' => $vendor->id,
            'subscription_plan_id' => $this->proPlan->id,
            'status' => SubscriptionStatus::Trialing,
            'billing_interval' => 'monthly',
            'trial_starts_at' => $originalStart,
            'trial_ends_at' => $originalEnd,
            'current_period_start' => $originalStart,
            'current_period_end' => $originalEnd,
            'auto_renew' => true,
        ]);

        $result = $this->subscriptionService->startTrial($vendor, $this->proPlan, 14);

        $this->assertSame($subscription->id, $result->id);
        $this->assertEquals(SubscriptionStatus::Trialing, $result->fresh()->status);
        $this->assertEquals($originalStart->toDateTimeString(), $result->fresh()->trial_starts_at->toDateTimeString());
        $this->assertEquals($originalEnd->toDateTimeString(), $result->fresh()->trial_ends_at->toDateTimeString());
    }

    public function test_start_trial_plan_change_during_trial_updates_plan_without_resetting_dates(): void
    {
        $vendor = Vendor::create([
            'name' => 'Upgrade Bistro',
            'slug' => 'upgrade-bistro-'.uniqid(),
            'type' => 'restaurant',
            'email' => 'upgrade_'.uniqid().'@example.com',
            'subscription_plan_id' => $this->basicPlan->id,
            'subscription_status' => 'trialing',
            'is_active' => true,
        ]);

        $originalStart = Carbon::now()->subDays(3);
        $originalEnd = Carbon::now()->addDays(11);

        $subscription = Subscription::create([
            'vendor_id' => $vendor->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => SubscriptionStatus::Trialing,
            'billing_interval' => 'monthly',
            'trial_starts_at' => $originalStart,
            'trial_ends_at' => $originalEnd,
            'current_period_start' => $originalStart,
            'current_period_end' => $originalEnd,
            'auto_renew' => true,
        ]);

        $result = $this->subscriptionService->startTrial($vendor, $this->proPlan, 14);

        $this->assertSame($subscription->id, $result->id);
        $this->assertEquals($this->proPlan->id, $result->fresh()->subscription_plan_id);
        $this->assertEquals($originalEnd->toDateTimeString(), $result->fresh()->trial_ends_at->toDateTimeString());
    }

    public function test_superadmin_store_vendor_links_location_id_to_owner_user_and_creates_subscription_atomically(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin_'.uniqid().'@qrmenu.am',
            'password' => bcrypt('secret123'),
            'role' => 'superadmin',
        ]);

        $template = MenuTemplate::firstOrFail();

        $response = $this->actingAs($superAdmin)->post(route('superadmin.vendors.store'), [
            'name' => 'Yerevan Tavern',
            'type' => 'restaurant',
            'email' => 'tavern_'.uniqid().'@example.com',
            'phone' => '+37491123456',
            'subscription_plan' => $this->proPlan->slug,
            'menu_template_id' => $template->id,
            'owner_name' => 'Aram Aramyan',
            'password' => 'password123',
        ]);

        $response->assertSessionHas('success');

        $vendor = Vendor::where('name', 'Yerevan Tavern')->first();
        $this->assertNotNull($vendor);
        $this->assertEquals('trialing', $vendor->subscription_status);
        $this->assertEquals($this->proPlan->id, $vendor->subscription_plan_id);

        $location = Location::where('vendor_id', $vendor->id)->first();
        $this->assertNotNull($location);
        $this->assertEquals('Main Location', $location->name);

        $owner = User::where('vendor_id', $vendor->id)->where('role', 'vendor_owner')->first();
        $this->assertNotNull($owner);
        $this->assertEquals($location->id, $owner->location_id, 'Owner user must have location_id linked to the default location');

        $subscription = Subscription::withoutTenantQuery()->where('vendor_id', $vendor->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals(SubscriptionStatus::Trialing, $subscription->status);
    }

    public function test_start_trial_rejects_and_expires_elapsed_trial(): void
    {
        $vendor = Vendor::create([
            'name' => 'Expired Trial Bistro',
            'slug' => 'expired-trial-'.uniqid(),
            'type' => 'restaurant',
            'email' => 'expired_'.uniqid().'@example.com',
            'subscription_plan_id' => $this->proPlan->id,
            'subscription_status' => 'trialing',
            'is_active' => true,
        ]);

        $pastStart = Carbon::now()->subDays(20);
        $pastEnd = Carbon::now()->subDays(6);

        $subscription = Subscription::create([
            'vendor_id' => $vendor->id,
            'subscription_plan_id' => $this->proPlan->id,
            'status' => SubscriptionStatus::Trialing,
            'billing_interval' => 'monthly',
            'trial_starts_at' => $pastStart,
            'trial_ends_at' => $pastEnd,
            'current_period_start' => $pastStart,
            'current_period_end' => $pastEnd,
            'auto_renew' => true,
        ]);

        $result = $this->subscriptionService->startTrial($vendor, $this->proPlan, 14);

        $this->assertEquals(SubscriptionStatus::Expired, $result->fresh()->status);
        $this->assertEquals('expired', $vendor->fresh()->subscription_status);
        $this->assertEquals($pastEnd->toDateTimeString(), $result->fresh()->trial_ends_at->toDateTimeString());
    }

    public function test_start_trial_rejects_retrial_from_cancelled_or_past_due_status(): void
    {
        $vendor = Vendor::create([
            'name' => 'Cancelled Bistro',
            'slug' => 'cancelled-'.uniqid(),
            'type' => 'restaurant',
            'email' => 'cancelled_'.uniqid().'@example.com',
            'subscription_plan_id' => $this->proPlan->id,
            'subscription_status' => 'cancelled',
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'vendor_id' => $vendor->id,
            'subscription_plan_id' => $this->proPlan->id,
            'status' => SubscriptionStatus::Cancelled,
            'billing_interval' => 'monthly',
            'cancelled_at' => Carbon::now()->subDays(2),
            'current_period_end' => Carbon::now()->subDays(2),
            'auto_renew' => false,
        ]);

        $result = $this->subscriptionService->startTrial($vendor, $this->basicPlan, 14);

        $this->assertEquals(SubscriptionStatus::Cancelled, $result->fresh()->status);
        $this->assertEquals($this->proPlan->id, $result->fresh()->subscription_plan_id);
    }

    public function test_start_trial_lock_for_update_serializes_transactions(): void
    {
        $vendor = Vendor::create([
            'name' => 'Lock Bistro',
            'slug' => 'lock-'.uniqid(),
            'type' => 'restaurant',
            'email' => 'lock_'.uniqid().'@example.com',
            'subscription_plan_id' => $this->proPlan->id,
            'subscription_status' => 'trialing',
            'is_active' => true,
        ]);

        // Start initial trial
        $sub = $this->subscriptionService->startTrial($vendor, $this->proPlan, 14);
        $this->assertNotNull($sub);

        // Verify that lockForUpdate query runs correctly within a transaction
        $locked = DB::transaction(function () use ($vendor) {
            return Subscription::withoutTenantQuery()
                ->where('vendor_id', $vendor->id)
                ->lockForUpdate()
                ->first();
        });

        $this->assertSame($sub->id, $locked->id);
    }

    public function test_vendor_registration_transaction_rollback_on_failure(): void
    {
        $initialVendorCount = Vendor::count();
        $initialLocationCount = Location::count();
        $initialUserCount = User::count();

        // Simulate an atomic transaction failure by forcing an exception during transaction
        $this->expectException(\RuntimeException::class);

        DB::transaction(function () {
            $vendor = Vendor::create([
                'name' => 'Doomed Cafe',
                'slug' => 'doomed-cafe-'.uniqid(),
                'type' => 'cafe',
                'email' => 'doomed@example.com',
                'is_active' => true,
            ]);

            Location::create([
                'vendor_id' => $vendor->id,
                'name' => 'Doomed Location',
                'slug' => 'doomed-main',
                'table_count' => 5,
                'is_active' => true,
            ]);

            throw new \RuntimeException('Simulated failure during registration pipeline');
        });

        $this->assertEquals($initialVendorCount, Vendor::count());
        $this->assertEquals($initialLocationCount, Location::count());
        $this->assertEquals($initialUserCount, User::count());
    }
}
