<?php

namespace App\Jobs;

use App\Models\VendorDeletionJob;
use App\Services\VendorLifecycleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessVendorDeletionJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public VendorDeletionJob $deletionJob,
        public bool $force = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(VendorLifecycleService $lifecycleService): void
    {
        $lifecycleService->executeDeletion($this->deletionJob, $this->force);
    }
}
