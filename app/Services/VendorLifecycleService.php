<?php

namespace App\Services;

use App\Exceptions\RetentionPeriodActiveException;
use App\Models\AiWaiterSession;
use App\Models\AnalyticsLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\LocationProductOverride;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCredential;
use App\Models\VendorDeletionJob;
use App\Models\VendorLifecycleLog;
use App\Models\WaiterCall;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VendorLifecycleService
{
    public const DEFAULT_RETENTION_DAYS = 30;

    public function __construct(
        protected StorageService $storageService
    ) {}

    /**
     * Suspend vendor access immediately.
     */
    public function suspend(Vendor $vendor, ?string $reason = null, ?User $actor = null): Vendor
    {
        $this->authorizeLifecycleAction($vendor, $actor);

        $fromState = $vendor->lifecycle_status ?? 'active';

        $vendor->update([
            'lifecycle_status' => 'suspended',
            'is_active' => false,
        ]);

        $this->logEvent(
            vendor: $vendor,
            event: 'suspended',
            fromState: $fromState,
            toState: 'suspended',
            reason: $reason,
            actor: $actor
        );

        $this->flushVendorCache($vendor);

        return $vendor;
    }

    /**
     * Request termination for a vendor, immediately suspending access and starting retention.
     */
    public function requestTermination(
        Vendor $vendor,
        string $reason,
        ?User $actor = null,
        ?int $retentionDays = null
    ): Vendor {
        $this->authorizeLifecycleAction($vendor, $actor);

        $fromState = $vendor->lifecycle_status ?? 'active';
        $retentionDays = $retentionDays ?? config('tenant.retention_days', self::DEFAULT_RETENTION_DAYS);
        $retentionEndsAt = now()->addDays($retentionDays);

        $vendor->update([
            'lifecycle_status' => 'retention',
            'is_active' => false,
            'termination_requested_at' => now(),
            'termination_requested_by' => $actor?->id,
            'termination_reason' => $reason,
            'retention_ends_at' => $retentionEndsAt,
        ]);

        $this->logEvent(
            vendor: $vendor,
            event: 'termination_requested',
            fromState: $fromState,
            toState: 'retention',
            reason: $reason,
            metadata: [
                'retention_ends_at' => $retentionEndsAt->toIso8601String(),
                'retention_days' => $retentionDays,
            ],
            actor: $actor
        );

        $this->flushVendorCache($vendor);

        return $vendor;
    }

    /**
     * Explicitly initiate or modify retention period.
     */
    public function startRetention(Vendor $vendor, ?int $retentionDays = null, ?User $actor = null): Vendor
    {
        $this->authorizeLifecycleAction($vendor, $actor);

        $fromState = $vendor->lifecycle_status ?? 'active';
        $retentionDays = $retentionDays ?? config('tenant.retention_days', self::DEFAULT_RETENTION_DAYS);
        $retentionEndsAt = now()->addDays($retentionDays);

        $vendor->update([
            'lifecycle_status' => 'retention',
            'is_active' => false,
            'retention_ends_at' => $retentionEndsAt,
        ]);

        $this->logEvent(
            vendor: $vendor,
            event: 'retention_started',
            fromState: $fromState,
            toState: 'retention',
            metadata: [
                'retention_ends_at' => $retentionEndsAt->toIso8601String(),
                'retention_days' => $retentionDays,
            ],
            actor: $actor
        );

        return $vendor;
    }

    /**
     * Queue vendor for permanent deletion after verifying retention requirements.
     */
    public function queueDeletion(Vendor $vendor, bool $force = false, ?User $actor = null): VendorDeletionJob
    {
        $this->authorizeLifecycleAction($vendor, $actor);

        if (! $force && $vendor->retention_ends_at && $vendor->retention_ends_at->isFuture()) {
            throw new RetentionPeriodActiveException(
                "Vendor retention period active until {$vendor->retention_ends_at->toIso8601String()}. Premature deletion is prohibited.",
                $vendor->retention_ends_at
            );
        }

        $fromState = $vendor->lifecycle_status;

        $vendor->update([
            'lifecycle_status' => 'deletion_queued',
            'deletion_queued_at' => now(),
            'is_active' => false,
        ]);

        $job = VendorDeletionJob::create([
            'vendor_id' => $vendor->id,
            'uuid' => (string) Str::uuid(),
            'status' => 'pending',
            'requested_by' => $actor?->id,
            'reason' => $vendor->termination_reason,
            'retention_ends_at' => $vendor->retention_ends_at,
        ]);

        $this->logEvent(
            vendor: $vendor,
            event: 'deletion_queued',
            fromState: $fromState,
            toState: 'deletion_queued',
            metadata: [
                'job_uuid' => $job->uuid,
                'force' => $force,
            ],
            actor: $actor
        );

        return $job;
    }

    /**
     * Execute the idempotent, observable deletion pipeline for a vendor deletion job.
     */
    public function executeDeletion(VendorDeletionJob $deletionJob, bool $force = false): void
    {
        $vendor = $deletionJob->vendor;
        if (! $vendor) {
            $deletionJob->update(['status' => 'failed', 'error_message' => 'Vendor not found.']);

            return;
        }

        if (! $force && $vendor->retention_ends_at && $vendor->retention_ends_at->isFuture()) {
            throw new RetentionPeriodActiveException(
                "Vendor retention period active until {$vendor->retention_ends_at->toIso8601String()}.",
                $vendor->retention_ends_at
            );
        }

        $deletionJob->update([
            'status' => 'processing',
            'started_at' => $deletionJob->started_at ?? now(),
            'attempt_count' => $deletionJob->attempt_count + 1,
            'error_message' => null,
            'error_trace' => null,
        ]);

        $vendor->update(['lifecycle_status' => 'deleting']);

        $steps = [
            'suspend_access' => fn () => $this->stepSuspendAccess($vendor, $deletionJob),
            'database_cleanup' => fn () => $this->deleteTenantData($vendor, $deletionJob),
            'storage_cleanup' => fn () => $this->deleteTenantStorage($vendor, $deletionJob),
            'cache_cleanup' => fn () => $this->cleanupCacheAndQueues($vendor, $deletionJob),
            'domain_cleanup' => fn () => $this->cleanupDomains($vendor, $deletionJob),
            'final_audit' => fn () => $this->finalizeDeletion($vendor, $deletionJob),
        ];

        foreach ($steps as $stepName => $action) {
            if ($deletionJob->isStepCompleted($stepName)) {
                continue; // Idempotency check: skip already completed step
            }

            try {
                $deletionJob->update(['current_step' => $stepName]);
                $result = $action();
                $details = is_array($result) ? $result : [];
                $deletionJob->markStepCompleted($stepName, $details);

                $this->logEvent(
                    vendor: $vendor,
                    event: 'step_completed',
                    metadata: array_merge(['step' => $stepName], $details)
                );
            } catch (\Throwable $e) {
                $deletionJob->markFailed($stepName, $e);

                $this->logEvent(
                    vendor: $vendor,
                    event: 'deletion_failed',
                    reason: $e->getMessage(),
                    metadata: [
                        'step' => $stepName,
                        'exception' => get_class($e),
                    ]
                );

                throw $e;
            }
        }
    }

    /**
     * Step 1: Ensure vendor is locked out and suspended.
     */
    protected function stepSuspendAccess(Vendor $vendor, VendorDeletionJob $job): array
    {
        $vendor->update(['is_active' => false]);
        $this->flushVendorCache($vendor);

        return ['suspended' => true];
    }

    /**
     * Step 2: Delete operational tenant-owned data in strict dependency order.
     * Legal and billing records (SubscriptionPayment, PaymentAttempt) are protected.
     */
    public function deleteTenantData(Vendor $vendor, ?VendorDeletionJob $deletionJob = null): array
    {
        return DB::transaction(function () use ($vendor) {
            $stats = [];

            // 1. AI Sessions
            $stats['ai_sessions'] = AiWaiterSession::withoutGlobalScopes()->where('vendor_id', $vendor->id)->delete();

            // 2. Waiter Calls
            $stats['waiter_calls'] = WaiterCall::withoutGlobalScopes()->where('vendor_id', $vendor->id)->delete();

            // 3. Location Product Overrides
            $stats['overrides'] = LocationProductOverride::withoutGlobalScopes()->where('vendor_id', $vendor->id)->delete();

            // 4. Products and Variations
            $productIds = Product::withoutGlobalScopes()->where('vendor_id', $vendor->id)->pluck('id');
            ProductVariation::whereIn('product_id', $productIds)->delete();
            $stats['products'] = Product::withoutGlobalScopes()->where('vendor_id', $vendor->id)->forceDelete();

            // 5. Categories
            $stats['categories'] = Category::withoutGlobalScopes()->where('vendor_id', $vendor->id)->forceDelete();

            // 6. Orders and Order Items
            $orderIds = Order::withoutGlobalScopes()->where('vendor_id', $vendor->id)->pluck('id');
            OrderItem::whereIn('order_id', $orderIds)->delete();
            $stats['orders'] = Order::withoutGlobalScopes()->where('vendor_id', $vendor->id)->forceDelete();

            // 7. Customers
            $stats['customers'] = Customer::withoutGlobalScopes()->where('vendor_id', $vendor->id)->delete();

            // 8. Analytics Logs
            $stats['analytics_logs'] = AnalyticsLog::where('vendor_id', $vendor->id)->delete();

            // 9. Tenant Users (ONLY users belonging strictly to this vendor; superadmins are never deleted)
            $stats['users'] = User::withoutGlobalScopes()
                ->where('vendor_id', $vendor->id)
                ->where('role', '!=', 'superadmin')
                ->delete();

            // 10. Locations
            $stats['locations'] = Location::withoutGlobalScopes()->where('vendor_id', $vendor->id)->delete();

            // 11. Vendor Credentials
            $stats['credentials'] = VendorCredential::withoutGlobalScopes()->where('vendor_id', $vendor->id)->delete();

            return $stats;
        });
    }

    /**
     * Step 3: Delete isolated vendor storage directory and file records.
     */
    public function deleteTenantStorage(Vendor $vendor, ?VendorDeletionJob $deletionJob = null): bool
    {
        return $this->storageService->deleteVendorStorage($vendor);
    }

    /**
     * Step 4: Purge vendor cache keys and background queue artifacts.
     */
    public function cleanupCacheAndQueues(Vendor $vendor, ?VendorDeletionJob $deletionJob = null): void
    {
        $this->flushVendorCache($vendor);
    }

    /**
     * Step 5: Clean up custom domains.
     */
    public function cleanupDomains(Vendor $vendor, ?VendorDeletionJob $deletionJob = null): void
    {
        if (! empty($vendor->custom_domain)) {
            Cache::forget('domain_'.$vendor->custom_domain);
            $vendor->update(['custom_domain' => null]);
        }
    }

    /**
     * Step 6: Mark vendor record as soft-deleted and finalize deletion job.
     */
    public function finalizeDeletion(Vendor $vendor, ?VendorDeletionJob $deletionJob = null): void
    {
        $fromState = $vendor->lifecycle_status;

        $vendor->update([
            'lifecycle_status' => 'deleted',
            'is_active' => false,
        ]);
        $vendor->delete(); // Soft delete vendor

        if ($deletionJob) {
            $deletionJob->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        $this->logEvent(
            vendor: $vendor,
            event: 'deletion_completed',
            fromState: $fromState,
            toState: 'deleted',
            metadata: [
                'job_uuid' => $deletionJob?->uuid,
            ]
        );
    }

    /**
     * Log a lifecycle event in the audit trail.
     */
    public function logEvent(
        Vendor $vendor,
        string $event,
        ?string $fromState = null,
        ?string $toState = null,
        ?string $reason = null,
        array $metadata = [],
        ?User $actor = null
    ): VendorLifecycleLog {
        return VendorLifecycleLog::create([
            'vendor_id' => $vendor->id,
            'actor_id' => $actor?->id,
            'actor_type' => $actor ? 'user' : 'system',
            'event' => $event,
            'from_state' => $fromState,
            'to_state' => $toState,
            'reason' => $reason,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    /**
     * Flush all cache keys associated with the vendor.
     */
    public function flushVendorCache(Vendor $vendor): void
    {
        Cache::forget("vendor_{$vendor->id}_config");
        Cache::forget("vendor_{$vendor->slug}_menu");
        if (! empty($vendor->custom_domain)) {
            Cache::forget('domain_'.$vendor->custom_domain);
        }
    }

    /**
     * Authorize that the actor has permissions to modify this vendor's lifecycle.
     */
    protected function authorizeLifecycleAction(Vendor $vendor, ?User $actor = null): void
    {
        if ($actor === null) {
            return; // System / console context
        }

        if ($actor->isSuperAdmin()) {
            return; // Superadmin has global privilege
        }

        if ((int) $actor->vendor_id !== (int) $vendor->id) {
            throw new AccessDeniedHttpException('Cross-vendor lifecycle operations are strictly prohibited.');
        }

        if (! in_array($actor->role, ['vendor_owner', 'admin'], true)) {
            throw new AccessDeniedHttpException('Only a vendor owner or platform administrator can manage vendor lifecycle.');
        }
    }
}
