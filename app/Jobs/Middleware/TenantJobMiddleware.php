<?php

namespace App\Jobs\Middleware;

use App\Exceptions\TenantContextException;
use App\Jobs\Contracts\TenantJobInterface;
use App\Models\Vendor;
use App\Services\TenantCache;
use App\Services\TenantContext;
use Closure;
use Illuminate\Support\Facades\Log;

class TenantJobMiddleware
{
    /**
     * Process the job through multi-tenant context and idempotency controls.
     */
    public function handle(mixed $job, Closure $next): mixed
    {
        $vendorId = null;

        if ($job instanceof TenantJobInterface) {
            $vendorId = $job->getVendorId();
        } elseif (isset($job->vendorId)) {
            $vendorId = (int) $job->vendorId;
        } elseif (isset($job->vendor_id)) {
            $vendorId = (int) $job->vendor_id;
        }

        if (empty($vendorId)) {
            throw new TenantContextException(sprintf(
                'Tenant job [%s] rejected: missing mandatory vendor_id.',
                get_class($job)
            ));
        }

        // Verify tenant exists
        $vendor = Vendor::withoutGlobalScopes()->find($vendorId);
        if (! $vendor) {
            throw new TenantContextException(sprintf(
                'Tenant job [%s] rejected: vendor #%d does not exist.',
                get_class($job),
                $vendorId
            ));
        }

        $idempotencyKey = null;
        if ($job instanceof TenantJobInterface) {
            $idempotencyKey = $job->getIdempotencyKey();
        } elseif (isset($job->idempotencyKey)) {
            $idempotencyKey = $job->idempotencyKey;
        }

        if (! empty($idempotencyKey)) {
            $cacheStatus = TenantCache::get($vendor, "job_idempotency:{$idempotencyKey}");

            // Check if already completed (duplicate job detection)
            if ($cacheStatus === 'completed') {
                Log::info(sprintf(
                    'Tenant job [%s] for vendor #%d skipped: already executed (idempotency key: %s).',
                    get_class($job),
                    $vendorId,
                    $idempotencyKey
                ));

                return null;
            }

            // Mark as in-flight / processing
            TenantCache::put($vendor, "job_idempotency:{$idempotencyKey}", 'processing', now()->addMinutes(10));
        }

        $tenantContext = app(TenantContext::class);

        try {
            $result = $tenantContext->runInTenantContext($vendorId, function () use ($next, $job) {
                return $next($job);
            });

            // Mark idempotency key as completed on successful run
            if (! empty($idempotencyKey)) {
                TenantCache::put($vendor, "job_idempotency:{$idempotencyKey}", 'completed', now()->addDay());
            }

            return $result;
        } catch (\Throwable $e) {
            // Clear processing state on exception so retry attempts can proceed
            if (! empty($idempotencyKey)) {
                TenantCache::forget($vendor, "job_idempotency:{$idempotencyKey}");
            }

            throw $e;
        } finally {
            $tenantContext->clear();
        }
    }
}
