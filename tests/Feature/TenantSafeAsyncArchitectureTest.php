<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Events\WaiterCalled;
use App\Exceptions\TenantContextException;
use App\Jobs\Concerns\TenantAwareJob;
use App\Jobs\Contracts\TenantJobInterface;
use App\Jobs\Middleware\TenantJobMiddleware;
use App\Jobs\ProcessVendorDeletionJob;
use App\Jobs\RecordAnalyticsVisitJob;
use App\Listeners\SendTelegramOrderNotification;
use App\Listeners\SendTelegramWaiterCallNotification;
use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorDeletionJob;
use App\Models\WaiterCall;
use App\Services\MenuManagementService;
use App\Services\TelegramNotificationService;
use App\Services\TenantCache;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantSafeAsyncArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendorA;

    protected Vendor $vendorB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendorA = Vendor::create([
            'name' => 'Vendor Alpha',
            'slug' => 'vendor-alpha',
            'uuid' => (string) Str::uuid(),
            'email' => 'alpha@test.com',
            'password' => bcrypt('password123'),
            'currency' => 'AMD',
            'is_active' => true,
        ]);

        $this->vendorB = Vendor::create([
            'name' => 'Vendor Beta',
            'slug' => 'vendor-beta',
            'uuid' => (string) Str::uuid(),
            'email' => 'beta@test.com',
            'password' => bcrypt('password123'),
            'currency' => 'USD',
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Tenant Cache Isolation
     * Every tenant-sensitive cache key must include vendor UUID and isolate data between tenants.
     */
    public function test_tenant_cache_keys_include_vendor_uuid_and_provide_strict_isolation(): void
    {
        // 1. Key format validation
        $menuKeyA = TenantCache::menuKey($this->vendorA);
        $settingsKeyA = TenantCache::settingsKey($this->vendorA);
        $analyticsKeyA = TenantCache::analyticsKey($this->vendorA);

        $this->assertSame("vendor:{$this->vendorA->uuid}:menu", $menuKeyA);
        $this->assertSame("vendor:{$this->vendorA->uuid}:settings", $settingsKeyA);
        $this->assertSame("vendor:{$this->vendorA->uuid}:analytics", $analyticsKeyA);

        // 2. Vendor model helper
        $this->assertSame("vendor:{$this->vendorA->uuid}:custom_widget", $this->vendorA->cacheKey('custom_widget'));

        // 3. Cache data isolation
        TenantCache::put($this->vendorA, 'menu', ['categories' => ['Pizzas', 'Pastas']]);
        TenantCache::put($this->vendorB, 'menu', ['categories' => ['Sushi', 'Sashimi']]);

        $this->assertSame(['categories' => ['Pizzas', 'Pastas']], TenantCache::get($this->vendorA, 'menu'));
        $this->assertSame(['categories' => ['Sushi', 'Sashimi']], TenantCache::get($this->vendorB, 'menu'));

        // 4. Invalidation isolation: invalidating vendor A does not flush vendor B
        TenantCache::invalidateAll($this->vendorA);

        $this->assertNull(TenantCache::get($this->vendorA, 'menu'));
        $this->assertSame(['categories' => ['Sushi', 'Sashimi']], TenantCache::get($this->vendorB, 'menu'));
    }

    /**
     * Test 2: Queue Isolation
     * Tenant jobs establish the correct TenantContext and isolate queries to that tenant only.
     */
    public function test_queue_isolation_ensures_jobs_execute_within_correct_tenant_context(): void
    {
        $catA = Category::create([
            'vendor_id' => $this->vendorA->id,
            'name' => 'Alpha Drinks',
            'sort_order' => 1,
        ]);
        Product::create([
            'vendor_id' => $this->vendorA->id,
            'category_id' => $catA->id,
            'name' => 'Alpha Beer',
            'price' => 1500,
        ]);

        $catB = Category::create([
            'vendor_id' => $this->vendorB->id,
            'name' => 'Beta Wines',
            'sort_order' => 1,
        ]);
        Product::create([
            'vendor_id' => $this->vendorB->id,
            'category_id' => $catB->id,
            'name' => 'Beta Merlot',
            'price' => 3000,
        ]);

        $observedTenantId = null;
        $observedProductsCount = null;
        $observedCanSeeBetaProduct = null;

        // Custom test job implementing TenantJobInterface
        $testJob = new class($this->vendorA->id) implements ShouldQueue, TenantJobInterface
        {
            use InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

            public ?int $observedTenantId = null;

            public ?int $observedProductsCount = null;

            public function __construct(int $vendorId)
            {
                $this->vendorId = $vendorId;
            }

            public function handle(): void
            {
                $this->observedTenantId = app(TenantContext::class)->getTenantId();
                // Because TenantScope is active, Product::count() only counts this tenant's products
                $this->observedProductsCount = Product::count();
            }
        };

        $middleware = new TenantJobMiddleware;
        $middleware->handle($testJob, function ($job) {
            $job->handle();

            return true;
        });

        $this->assertSame($this->vendorA->id, $testJob->observedTenantId);
        $this->assertSame(1, $testJob->observedProductsCount);
    }

    /**
     * Test 3: Tenant Context Restoration
     * A worker must never leave TenantContext dirty after executing a job (success or failure).
     */
    public function test_tenant_context_restoration_resets_context_after_job_success_and_failure(): void
    {
        $tenantContext = app(TenantContext::class);
        $this->assertNull($tenantContext->getTenantId(), 'Context must be clean initially.');

        $successfulJob = new class($this->vendorA->id) implements ShouldQueue, TenantJobInterface
        {
            use InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

            public function __construct(int $vendorId)
            {
                $this->vendorId = $vendorId;
            }

            public function handle(): void
            {
                // Verify context inside job
                if (app(TenantContext::class)->getTenantId() !== $this->vendorId) {
                    throw new \RuntimeException('Tenant context mismatch during execution.');
                }
            }
        };

        $middleware = new TenantJobMiddleware;
        $middleware->handle($successfulJob, function ($job) {
            $job->handle();

            return true;
        });

        $this->assertNull($tenantContext->getTenantId(), 'Context must be cleared after successful job.');

        // Test context cleanup on job failure/exception
        $failingJob = new class($this->vendorA->id) implements ShouldQueue, TenantJobInterface
        {
            use InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

            public function __construct(int $vendorId)
            {
                $this->vendorId = $vendorId;
            }

            public function handle(): void
            {
                throw new \RuntimeException('Simulated worker exception.');
            }
        };

        try {
            $middleware->handle($failingJob, function ($job) {
                $job->handle();
            });
            $this->fail('Expected exception was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated worker exception.', $e->getMessage());
        }

        $this->assertNull($tenantContext->getTenantId(), 'Context must be cleared after failed job.');
    }

    /**
     * Test 4: Sequential Queue Jobs Across Multiple Tenants
     * Sequential execution of Job A then Job B never contaminates or bleeds context.
     */
    public function test_sequential_jobs_across_tenants_do_not_bleed_context(): void
    {
        $middleware = new TenantJobMiddleware;
        $sequence = [];

        $jobA = new class($this->vendorA->id) implements ShouldQueue, TenantJobInterface
        {
            use InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

            public function __construct(int $vendorId)
            {
                $this->vendorId = $vendorId;
            }

            public function handle(): int
            {
                return app(TenantContext::class)->getTenantId();
            }
        };

        $jobB = new class($this->vendorB->id) implements ShouldQueue, TenantJobInterface
        {
            use InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

            public function __construct(int $vendorId)
            {
                $this->vendorId = $vendorId;
            }

            public function handle(): int
            {
                return app(TenantContext::class)->getTenantId();
            }
        };

        $sequence[] = $middleware->handle($jobA, fn ($j) => $j->handle());
        $sequence[] = app(TenantContext::class)->getTenantId();
        $sequence[] = $middleware->handle($jobB, fn ($j) => $j->handle());
        $sequence[] = app(TenantContext::class)->getTenantId();

        $this->assertSame([$this->vendorA->id, null, $this->vendorB->id, null], $sequence);
    }

    /**
     * Test 5: Duplicate Jobs Idempotency
     * Jobs with identical idempotency keys are executed once; duplicates are skipped.
     */
    public function test_duplicate_jobs_are_skipped_via_idempotency_keys(): void
    {
        $executionCounter = 0;

        $idempotentJob = new class($this->vendorA->id, 'unique_payment_webhook_999') implements ShouldQueue, TenantJobInterface
        {
            use InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

            public function __construct(int $vendorId, string $idempotencyKey)
            {
                $this->vendorId = $vendorId;
                $this->idempotencyKey = $idempotencyKey;
            }
        };

        $middleware = new TenantJobMiddleware;

        // Run 1: First dispatch executes
        $res1 = $middleware->handle($idempotentJob, function () use (&$executionCounter) {
            $executionCounter++;

            return 'executed_successfully';
        });

        $this->assertSame('executed_successfully', $res1);
        $this->assertSame(1, $executionCounter);

        // Verify idempotency status in TenantCache
        $this->assertSame('completed', TenantCache::get($this->vendorA, 'job_idempotency:unique_payment_webhook_999'));

        // Run 2: Duplicate dispatch is skipped
        $res2 = $middleware->handle($idempotentJob, function () use (&$executionCounter) {
            $executionCounter++;

            return 'executed_successfully';
        });

        $this->assertNull($res2);
        $this->assertSame(1, $executionCounter, 'Second identical job dispatch should not execute.');
    }

    /**
     * Test 6: Job Retry Behavior
     * When a job fails, the temporary lock is released so retries are permitted to succeed.
     */
    public function test_job_retry_allows_execution_after_transient_failure(): void
    {
        $attempts = 0;

        $retriableJob = new class($this->vendorA->id, 'sync_order_attempt_1') implements ShouldQueue, TenantJobInterface
        {
            use InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

            public function __construct(int $vendorId, string $idempotencyKey)
            {
                $this->vendorId = $vendorId;
                $this->idempotencyKey = $idempotencyKey;
            }
        };

        $middleware = new TenantJobMiddleware;

        // Attempt 1 fails
        try {
            $middleware->handle($retriableJob, function () use (&$attempts) {
                $attempts++;
                throw new \RuntimeException('Database deadlock on attempt 1');
            });
            $this->fail('Exception was expected on attempt 1.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Database deadlock on attempt 1', $e->getMessage());
        }

        $this->assertSame(1, $attempts);
        // Processing lock was removed so retry can run
        $this->assertNull(TenantCache::get($this->vendorA, 'job_idempotency:sync_order_attempt_1'));

        // Attempt 2 (Retry) succeeds
        $result = $middleware->handle($retriableJob, function () use (&$attempts) {
            $attempts++;

            return 'retry_succeeded';
        });

        $this->assertSame(2, $attempts);
        $this->assertSame('retry_succeeded', $result);
        $this->assertSame('completed', TenantCache::get($this->vendorA, 'job_idempotency:sync_order_attempt_1'));
    }

    /**
     * Test 7: Failed Jobs Redaction
     * Sensitive payloads (passwords, tokens, api keys, cards) are redacted and never logged.
     */
    public function test_failed_jobs_do_not_leak_sensitive_payloads_into_logs(): void
    {
        $sensitiveJob = new class($this->vendorA->id) implements ShouldQueue, TenantJobInterface
        {
            use InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

            public string $api_key = 'mock_super_secret_api_key_12345';

            public string $webhook_secret = 'whsec_abcdef987654321';

            public string $password = 'super_confidential_pass';

            public string $telegram_token = '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11';

            public array $nested_data = [
                'card_number' => '4111222233334444',
                'cvv' => '123',
                'customer_note' => 'Please ring the doorbell',
            ];

            public function __construct(int $vendorId)
            {
                $this->vendorId = $vendorId;
            }
        };

        $sanitized = $sensitiveJob->getSanitizedPayload();

        $this->assertSame('[REDACTED]', $sanitized['api_key']);
        $this->assertSame('[REDACTED]', $sanitized['webhook_secret']);
        $this->assertSame('[REDACTED]', $sanitized['password']);
        $this->assertSame('[REDACTED]', $sanitized['telegram_token']);
        $this->assertSame('[REDACTED]', $sanitized['nested_data']['card_number']);
        $this->assertSame('[REDACTED]', $sanitized['nested_data']['cvv']);
        $this->assertSame('Please ring the doorbell', $sanitized['nested_data']['customer_note']);

        // Assert failure method executes without throwing error and redacts payload
        $sensitiveJob->failed(new \RuntimeException('Connection timed out.'));
    }

    /**
     * Test 8: Rejection of Jobs Without Tenant ID or Non-Existent Vendor
     */
    public function test_job_missing_vendor_or_with_invalid_vendor_is_rejected(): void
    {
        $middleware = new TenantJobMiddleware;

        // Missing vendor ID
        $invalidJob = new class implements ShouldQueue
        {
            use InteractsWithQueue, Queueable;

            public ?int $vendorId = null;
        };

        $this->expectException(TenantContextException::class);
        $this->expectExceptionMessage('missing mandatory vendor_id');

        $middleware->handle($invalidJob, fn () => true);
    }

    /**
     * Test 9: Rejection of Job for Non-Existent Tenant ID
     */
    public function test_job_with_non_existent_vendor_is_rejected(): void
    {
        $middleware = new TenantJobMiddleware;

        $nonExistentJob = new class(999999) implements ShouldQueue, TenantJobInterface
        {
            use InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

            public function __construct(int $vendorId)
            {
                $this->vendorId = $vendorId;
            }
        };

        $this->expectException(TenantContextException::class);
        $this->expectExceptionMessage('vendor #999999 does not exist');

        $middleware->handle($nonExistentJob, fn () => true);
    }

    /**
     * Test 10: Telegram Listeners Isolation
     * Notification listeners run inside the order/waiter call's specific tenant context.
     */
    public function test_telegram_listeners_execute_within_tenant_context(): void
    {
        $location = Location::create([
            'vendor_id' => $this->vendorA->id,
            'name' => 'Main Hall',
            'slug' => 'main-hall',
        ]);

        $order = Order::create([
            'vendor_id' => $this->vendorA->id,
            'location_id' => $location->id,
            'order_number' => 'ORD-99901',
            'tracking_token' => (string) Str::uuid(),
            'type' => 'dine_in',
            'status' => 'pending',
            'total_amount' => 5000,
        ]);

        $mockTelegram = $this->createMock(TelegramNotificationService::class);
        $mockTelegram->expects($this->once())
            ->method('sendOrderNotification')
            ->willReturnCallback(function ($passedOrder) {
                // Verify TenantContext inside notification handler
                $currentTenantId = app(TenantContext::class)->getTenantId();
                $this->assertSame($passedOrder->vendor_id, $currentTenantId);

                return true;
            });

        $listener = new SendTelegramOrderNotification($mockTelegram);
        $listener->handle(new OrderCreated($order));

        $this->assertNull(app(TenantContext::class)->getTenantId(), 'Context must be cleared after notification.');
    }

    /**
     * Test 11: Real Asynchronous Job Implementations Check
     * ProcessVendorDeletionJob and RecordAnalyticsVisitJob adhere to TenantJobInterface.
     */
    public function test_concrete_tenant_jobs_implement_interface_and_idempotency(): void
    {
        $deletionJobRecord = VendorDeletionJob::create([
            'vendor_id' => $this->vendorA->id,
            'uuid' => (string) Str::uuid(),
            'status' => 'retention_pending',
        ]);

        $processJob = new ProcessVendorDeletionJob($deletionJobRecord);
        $this->assertInstanceOf(TenantJobInterface::class, $processJob);
        $this->assertSame($this->vendorA->id, $processJob->getVendorId());
        $this->assertStringContainsString('vendor_deletion_', $processJob->getIdempotencyKey());
        $this->assertSame(3, $processJob->tries);
        $this->assertSame([10, 30, 90], $processJob->backoff());

        $analyticsJob = new RecordAnalyticsVisitJob([
            'vendor_id' => $this->vendorB->id,
            'channel' => 'dine_in',
            'visit_hash' => 'hash_abc123',
        ]);
        $this->assertInstanceOf(TenantJobInterface::class, $analyticsJob);
        $this->assertSame($this->vendorB->id, $analyticsJob->getVendorId());
        $this->assertSame("analytics_{$this->vendorB->id}_hash_abc123", $analyticsJob->getIdempotencyKey());
    }

    /**
     * Test 12: Waiter Call Telegram Listener Isolation
     */
    public function test_waiter_call_telegram_listener_executes_within_tenant_context(): void
    {
        $call = WaiterCall::create([
            'vendor_id' => $this->vendorA->id,
            'table_number' => '12',
            'type' => 'call',
            'status' => 'pending',
        ]);

        $mockTelegram = $this->createMock(TelegramNotificationService::class);
        $mockTelegram->expects($this->once())
            ->method('sendWaiterCallNotification')
            ->willReturnCallback(function ($passedCall) {
                $this->assertSame($passedCall->vendor_id, app(TenantContext::class)->getTenantId());

                return true;
            });

        $listener = new SendTelegramWaiterCallNotification($mockTelegram);
        $listener->handle(new WaiterCalled($call));

        $this->assertNull(app(TenantContext::class)->getTenantId(), 'Context must be cleared after notification.');
    }

    /**
     * Test 13: Queue Worker Lifecycle Events Guarantee Clean Tenant Context
     */
    public function test_queue_worker_lifecycle_hooks_guarantee_tenant_context_clear(): void
    {
        $tenantContext = app(TenantContext::class);

        // Simulate dirty state before a job
        $tenantContext->setTenantId($this->vendorA->id);
        $this->assertSame($this->vendorA->id, $tenantContext->getTenantId());

        // Queue::before hook trigger
        $mockJob = $this->createMock(Job::class);
        event(new JobProcessing('database', $mockJob));
        $this->assertNull($tenantContext->getTenantId(), 'Queue::before must clear TenantContext.');

        // Simulate dirty state after a job
        $tenantContext->setTenantId($this->vendorB->id);
        event(new JobProcessed('database', $mockJob));
        $this->assertNull($tenantContext->getTenantId(), 'Queue::after must clear TenantContext.');

        // Simulate dirty state when job fails
        $tenantContext->setTenantId($this->vendorA->id);
        event(new JobFailed('database', $mockJob, new \RuntimeException('Failure')));
        $this->assertNull($tenantContext->getTenantId(), 'Queue::failing must clear TenantContext.');
    }

    /**
     * Test 14: Menu Mutations Invalidate Tenant Menu Cache
     */
    public function test_menu_management_service_invalidates_tenant_menu_cache(): void
    {
        $menuService = app(MenuManagementService::class);

        // Prime cache
        TenantCache::put($this->vendorA, 'menu', ['cached_data' => true]);
        $this->assertTrue(TenantCache::has($this->vendorA, 'menu'));

        // Create category
        $cat = $menuService->createCategory($this->vendorA, ['name' => 'Desserts']);
        $this->assertFalse(TenantCache::has($this->vendorA, 'menu'), 'Category creation must invalidate tenant menu cache.');

        // Re-prime cache
        TenantCache::put($this->vendorA, 'menu', ['cached_data' => true]);
        $this->assertTrue(TenantCache::has($this->vendorA, 'menu'));

        // Create product
        $prod = $menuService->createProduct($this->vendorA, [
            'name' => 'Tiramisu',
            'category_id' => $cat->id,
            'price' => 2000,
        ]);
        $this->assertFalse(TenantCache::has($this->vendorA, 'menu'), 'Product creation must invalidate tenant menu cache.');
    }
}
