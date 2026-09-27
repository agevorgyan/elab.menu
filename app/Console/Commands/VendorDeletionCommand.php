<?php

namespace App\Console\Commands;

use App\Models\Vendor;
use App\Models\VendorDeletionJob;
use App\Services\VendorLifecycleService;
use Illuminate\Console\Command;

class VendorDeletionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vendor:delete
                            {vendor : Vendor ID, UUID, or slug}
                            {--force : Force deletion immediately, bypassing retention period}
                            {--retry : Retry a previously failed deletion job}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely and auditably execute the vendor deletion pipeline';

    /**
     * Execute the console command.
     */
    public function handle(VendorLifecycleService $lifecycleService): int
    {
        $vendorIdentifier = $this->argument('vendor');
        $force = (bool) $this->option('force');
        $retry = (bool) $this->option('retry');

        $vendor = Vendor::withTrashed()
            ->where('id', $vendorIdentifier)
            ->orWhere('uuid', $vendorIdentifier)
            ->orWhere('slug', $vendorIdentifier)
            ->first();

        if (! $vendor) {
            $this->error("Vendor [{$vendorIdentifier}] not found.");

            return self::FAILURE;
        }

        $this->info("Initiating deletion workflow for Vendor #{$vendor->id} ({$vendor->name})...");

        // Check if an existing failed or pending job exists
        $deletionJob = VendorDeletionJob::where('vendor_id', $vendor->id)->latest()->first();

        if ($deletionJob && $deletionJob->status === 'failed' && $retry) {
            $this->warn("Retrying failed deletion job #{$deletionJob->id} from step [{$deletionJob->current_step}]...");
        } elseif (! $deletionJob || $deletionJob->status === 'completed') {
            try {
                $deletionJob = $lifecycleService->queueDeletion($vendor, $force);
                $this->line("<fg=green>Deletion job queued successfully:</> {$deletionJob->uuid}");
            } catch (\Throwable $e) {
                $this->error('Failed to queue deletion: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        try {
            $lifecycleService->executeDeletion($deletionJob, $force);
            $this->info("Vendor #{$vendor->id} ({$vendor->name}) successfully and safely deleted.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Deletion pipeline failed at step [{$deletionJob->current_step}]: ".$e->getMessage());

            return self::FAILURE;
        }
    }
}
