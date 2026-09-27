<?php

namespace App\Jobs;

use App\Jobs\Concerns\TenantAwareJob;
use App\Jobs\Contracts\TenantJobInterface;
use App\Models\VendorDeletionJob;
use App\Services\VendorLifecycleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessVendorDeletionJob implements ShouldQueue, TenantJobInterface
{
    use InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public VendorDeletionJob $deletionJob,
        public bool $force = false,
        ?int $vendorId = null,
        ?string $idempotencyKey = null
    ) {
        $this->vendorId = $vendorId ?? (int) $deletionJob->vendor_id;
        $this->idempotencyKey = $idempotencyKey ?? 'vendor_deletion_'.$deletionJob->uuid;
    }

    /**
     * Execute the job.
     */
    public function handle(VendorLifecycleService $lifecycleService): void
    {
        // Idempotency: skip if already marked as completed
        if ($this->deletionJob->status === 'completed') {
            return;
        }

        $lifecycleService->executeDeletion($this->deletionJob, $this->force);
    }
}
