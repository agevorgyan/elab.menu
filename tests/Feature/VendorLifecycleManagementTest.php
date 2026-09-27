<?php

namespace Tests\Feature;

use App\Exceptions\RetentionPeriodActiveException;
use App\Models\AiWaiterSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\LocationProductOverride;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDeletionJob;
use App\Models\WaiterCall;
use App\Services\StorageService;
use App\Services\VendorLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

class VendorLifecycleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendorA;

    protected Vendor $vendorB;

    protected User $ownerA;

    protected User $ownerB;

    protected User $superAdmin;

    protected VendorLifecycleService $lifecycleService;

    protected StorageService $storageService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->vendorA = Vendor::create([
            'name' => 'Trattoria Roma',
            'slug' => 'trattoria-roma',
            'email' => 'roma@test.com',
            'password' => bcrypt('password'),
            'custom_domain' => 'roma.menu',
            'lifecycle_status' => 'active',
            'is_active' => true,
        ]);

        $this->ownerA = User::create([
            'name' => 'Mario Rossi',
            'email' => 'mario@roma.com',
            'password' => bcrypt('password'),
            'vendor_id' => $this->vendorA->id,
            'role' => 'vendor_owner',
        ]);

        $this->vendorB = Vendor::create([
            'name' => 'Tokyo Sushi',
            'slug' => 'tokyo-sushi',
            'email' => 'tokyo@test.com',
            'password' => bcrypt('password'),
            'lifecycle_status' => 'active',
            'is_active' => true,
        ]);

        $this->ownerB = User::create([
            'name' => 'Kenji Sato',
            'email' => 'kenji@tokyo.com',
            'password' => bcrypt('password'),
            'vendor_id' => $this->vendorB->id,
            'role' => 'vendor_owner',
        ]);

        $this->superAdmin = User::create([
            'name' => 'Platform Admin',
            'email' => 'super@platform.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
            'vendor_id' => null,
        ]);

        $this->lifecycleService = app(VendorLifecycleService::class);
        $this->storageService = app(StorageService::class);
    }

    public function test_vendor_suspension_disables_access_and_records_audit_log(): void
    {
        $this->lifecycleService->suspend($this->vendorA, 'Billing delinquency', $this->superAdmin);

        $this->vendorA->refresh();
        $this->assertEquals('suspended', $this->vendorA->lifecycle_status);
        $this->assertFalse((bool) $this->vendorA->is_active);
        $this->assertTrue($this->vendorA->isSuspended());

        $this->assertDatabaseHas('vendor_lifecycle_logs', [
            'vendor_id' => $this->vendorA->id,
            'event' => 'suspended',
            'from_state' => 'active',
            'to_state' => 'suspended',
            'reason' => 'Billing delinquency',
            'actor_id' => $this->superAdmin->id,
        ]);
    }

    public function test_termination_request_suspends_access_and_initiates_retention_period(): void
    {
        $this->lifecycleService->requestTermination(
            vendor: $this->vendorA,
            reason: 'Closing branch location',
            actor: $this->ownerA,
            retentionDays: 14
        );

        $this->vendorA->refresh();
        $this->assertEquals('retention', $this->vendorA->lifecycle_status);
        $this->assertFalse((bool) $this->vendorA->is_active);
        $this->assertNotNull($this->vendorA->termination_requested_at);
        $this->assertEquals($this->ownerA->id, $this->vendorA->termination_requested_by);
        $this->assertEquals('Closing branch location', $this->vendorA->termination_reason);

        // Retention deadline must be set to 14 days in future
        $this->assertNotNull($this->vendorA->retention_ends_at);
        $this->assertTrue($this->vendorA->retention_ends_at->isFuture());
        $this->assertTrue($this->vendorA->isInRetention());

        $this->assertDatabaseHas('vendor_lifecycle_logs', [
            'vendor_id' => $this->vendorA->id,
            'event' => 'termination_requested',
            'to_state' => 'retention',
            'reason' => 'Closing branch location',
        ]);
    }

    public function test_premature_deletion_rejected_while_retention_period_is_active(): void
    {
        $this->vendorA->update([
            'lifecycle_status' => 'retention',
            'retention_ends_at' => now()->addDays(20),
            'is_active' => false,
        ]);

        $this->expectException(RetentionPeriodActiveException::class);

        try {
            $this->lifecycleService->queueDeletion($this->vendorA, force: false, actor: $this->ownerA);
        } finally {
            $this->vendorA->refresh();
            $this->assertEquals('retention', $this->vendorA->lifecycle_status);
            $this->assertEquals(0, VendorDeletionJob::where('vendor_id', $this->vendorA->id)->count());
        }
    }

    public function test_deletion_queued_successfully_after_retention_period_expires(): void
    {
        $this->vendorA->update([
            'lifecycle_status' => 'retention',
            'retention_ends_at' => now()->subDay(), // Retention expired
            'is_active' => false,
        ]);

        $job = $this->lifecycleService->queueDeletion($this->vendorA, force: false, actor: $this->ownerA);

        $this->assertInstanceOf(VendorDeletionJob::class, $job);
        $this->assertEquals('pending', $job->status);

        $this->vendorA->refresh();
        $this->assertEquals('deletion_queued', $this->vendorA->lifecycle_status);
        $this->assertTrue($this->vendorA->isDeleting());
    }

    public function test_complete_vendor_deletion_pipeline_deletes_tenant_data_and_storage_while_protecting_billing_and_platform_data(): void
    {
        // 1. Seed complete tenant data for Vendor A
        $location = Location::create([
            'vendor_id' => $this->vendorA->id,
            'name' => 'Downtown Roma',
            'slug' => 'downtown-roma',
        ]);

        $staffUser = User::create([
            'name' => 'Waiter Luigi',
            'email' => 'luigi@roma.com',
            'password' => bcrypt('password'),
            'vendor_id' => $this->vendorA->id,
            'role' => 'staff',
            'location_id' => $location->id,
        ]);

        $category = Category::create([
            'vendor_id' => $this->vendorA->id,
            'name' => 'Pastas',
        ]);

        $product = Product::create([
            'vendor_id' => $this->vendorA->id,
            'category_id' => $category->id,
            'name' => 'Lasagna',
            'price' => 3000,
        ]);

        ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Large',
            'price' => 3500,
        ]);

        $customer = Customer::create([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $location->id,
            'name' => 'Customer Giovanni',
            'phone' => '+37491111111',
        ]);

        $order = Order::create([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $location->id,
            'customer_id' => $customer->id,
            'order_number' => 'ORD-100',
            'total_amount' => 3000,
            'status' => 'completed',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Lasagna',
            'unit_price' => 3000,
            'quantity' => 1,
            'subtotal' => 3000,
        ]);

        WaiterCall::create([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $location->id,
            'table_number' => '12',
            'type' => 'call',
            'status' => 'pending',
        ]);

        AiWaiterSession::create([
            'vendor_id' => $this->vendorA->id,
            'session_token' => 'sess-123456',
            'language' => 'en',
            'status' => 'active',
        ]);

        LocationProductOverride::create([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $location->id,
            'product_id' => $product->id,
            'is_available' => false,
        ]);

        // Upload a file into Vendor A's storage
        $imageFile = UploadedFile::fake()->image('lasagna.jpg', 400, 400);
        $stored = $this->storageService->store($imageFile, 'products', $this->vendorA);
        Storage::disk('public')->assertExists($stored->path);

        // Create billing/legal record (MUST BE PROTECTED)
        $billingRecord = SubscriptionPayment::create([
            'vendor_id' => $this->vendorA->id,
            'invoice_number' => 'INV-2026-999',
            'amount' => 19900,
            'currency' => 'AMD',
            'payment_method' => 'arca',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // 2. Queue and execute deletion with force=true
        $job = $this->lifecycleService->queueDeletion($this->vendorA, force: true, actor: $this->superAdmin);
        $this->lifecycleService->executeDeletion($job, force: true);

        // 3. Assertions on Tenant-Owned Data Removal
        $this->assertEquals(0, Product::withoutGlobalScopes()->where('vendor_id', $this->vendorA->id)->count());
        $this->assertEquals(0, Category::withoutGlobalScopes()->where('vendor_id', $this->vendorA->id)->count());
        $this->assertEquals(0, Location::withoutGlobalScopes()->where('vendor_id', $this->vendorA->id)->count());
        $this->assertEquals(0, Order::withoutGlobalScopes()->where('vendor_id', $this->vendorA->id)->count());
        $this->assertEquals(0, Customer::withoutGlobalScopes()->where('vendor_id', $this->vendorA->id)->count());
        $this->assertEquals(0, WaiterCall::withoutGlobalScopes()->where('vendor_id', $this->vendorA->id)->count());
        $this->assertEquals(0, AiWaiterSession::withoutGlobalScopes()->where('vendor_id', $this->vendorA->id)->count());
        $this->assertEquals(0, LocationProductOverride::withoutGlobalScopes()->where('vendor_id', $this->vendorA->id)->count());

        // Assert tenant staff user was deleted
        $this->assertDatabaseMissing('users', ['id' => $staffUser->id]);

        // 4. Assert Platform Superadmin is NOT deleted
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);

        // 5. Assert Legal / Billing records are PROTECTED and preserved
        $this->assertDatabaseHas('subscription_payments', ['id' => $billingRecord->id]);

        // 6. Assert Storage was deleted
        Storage::disk('public')->assertMissing($stored->path);
        $this->assertFalse(Storage::disk('public')->exists("vendors/{$this->vendorA->uuid}"));

        // 7. Assert Vendor is soft-deleted and status updated
        $this->vendorA->refresh();
        $this->assertEquals('deleted', $this->vendorA->lifecycle_status);
        $this->assertTrue($this->vendorA->trashed());

        // 8. Assert Deletion Job completed
        $job->refresh();
        $this->assertEquals('completed', $job->status);
        $this->assertNotNull($job->completed_at);
        $this->assertTrue($job->isStepCompleted('database_cleanup'));
        $this->assertTrue($job->isStepCompleted('storage_cleanup'));
        $this->assertTrue($job->isStepCompleted('final_audit'));
    }

    public function test_cross_vendor_termination_and_deletion_is_strictly_blocked(): void
    {
        // Owner A attempts to request termination of Vendor B
        $this->expectException(AccessDeniedHttpException::class);

        try {
            $this->lifecycleService->requestTermination(
                vendor: $this->vendorB,
                reason: 'Malicious attack',
                actor: $this->ownerA
            );
        } finally {
            $this->vendorB->refresh();
            $this->assertEquals('active', $this->vendorB->lifecycle_status);
            $this->assertTrue((bool) $this->vendorB->is_active);
        }
    }

    public function test_deletion_retry_after_failure_resumes_from_failed_step_without_duplication(): void
    {
        $job = $this->lifecycleService->queueDeletion($this->vendorA, force: true, actor: $this->superAdmin);

        // Artificially simulate step 1 and step 2 already completed
        $job->markStepCompleted('suspend_access', ['simulated' => true]);
        $job->markStepCompleted('database_cleanup', ['simulated' => true]);
        $job->update([
            'status' => 'failed',
            'current_step' => 'storage_cleanup',
            'error_message' => 'Simulated disk timeout error',
        ]);

        $this->assertEquals('failed', $job->status);

        // Resume / retry deletion
        $this->lifecycleService->executeDeletion($job, force: true);

        $job->refresh();
        $this->assertEquals('completed', $job->status);
        $this->assertNull($job->error_message);
        $this->assertTrue($job->isStepCompleted('storage_cleanup'));
        $this->assertTrue($job->isStepCompleted('final_audit'));

        $this->vendorA->refresh();
        $this->assertTrue($this->vendorA->trashed());
    }

    public function test_storage_deletion_removes_only_target_vendor_files(): void
    {
        $fileA = UploadedFile::fake()->image('a.jpg', 300, 300);
        $recordA = $this->storageService->store($fileA, 'products', $this->vendorA);

        $fileB = UploadedFile::fake()->image('b.jpg', 300, 300);
        $recordB = $this->storageService->store($fileB, 'products', $this->vendorB);

        Storage::disk('public')->assertExists($recordA->path);
        Storage::disk('public')->assertExists($recordB->path);

        $job = $this->lifecycleService->queueDeletion($this->vendorA, force: true);
        $this->lifecycleService->executeDeletion($job, force: true);

        // Vendor A files deleted
        Storage::disk('public')->assertMissing($recordA->path);

        // Vendor B files MUST still exist
        Storage::disk('public')->assertExists($recordB->path);
        $this->assertDatabaseHas('vendor_storage_files', [
            'id' => $recordB->id,
            'deleted_at' => null,
        ]);
    }

    public function test_cache_and_custom_domain_cleanup(): void
    {
        Cache::put("vendor_{$this->vendorA->id}_config", 'CACHED_CONFIG', 600);
        Cache::put('domain_'.$this->vendorA->custom_domain, 'CACHED_DOMAIN', 600);

        $this->assertEquals('CACHED_CONFIG', Cache::get("vendor_{$this->vendorA->id}_config"));

        $job = $this->lifecycleService->queueDeletion($this->vendorA, force: true);
        $this->lifecycleService->executeDeletion($job, force: true);

        // Cache must be purged
        $this->assertNull(Cache::get("vendor_{$this->vendorA->id}_config"));
        $this->assertNull(Cache::get('domain_roma.menu'));

        // Custom domain unset
        $this->vendorA->refresh();
        $this->assertNull($this->vendorA->custom_domain);
    }

    public function test_vendor_delete_artisan_command_end_to_end(): void
    {
        $this->artisan('vendor:delete', [
            'vendor' => $this->vendorA->slug,
            '--force' => true,
        ])->assertSuccessful();

        $this->vendorA->refresh();
        $this->assertEquals('deleted', $this->vendorA->lifecycle_status);
        $this->assertTrue($this->vendorA->trashed());
    }
}
