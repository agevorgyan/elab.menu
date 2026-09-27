<?php

namespace App\Console\Commands;

use App\Models\Vendor;
use App\Models\VendorStorageFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class StorageReconcileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:reconcile
                            {--vendor= : Specific vendor ID or UUID to reconcile}
                            {--dry-run : Preview calculation without updating database}
                            {--delete-orphans : Delete unreferenced files from disk}
                            {--disk=public : Disk name to reconcile}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile vendor storage usage from metadata records and detect orphaned files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $vendorOption = $this->option('vendor');
        $isDryRun = (bool) $this->option('dry-run');
        $deleteOrphans = (bool) $this->option('delete-orphans');
        $diskName = (string) ($this->option('disk') ?: 'public');

        if ($isDryRun) {
            $this->warn('Running in DRY-RUN mode. No database or disk changes will be made.');
        }

        $query = Vendor::query();
        if ($vendorOption) {
            $query->where(function ($q) use ($vendorOption) {
                $q->where('id', $vendorOption)
                    ->orWhere('uuid', $vendorOption)
                    ->orWhere('slug', $vendorOption);
            });
        }

        $vendors = $query->get();
        if ($vendors->isEmpty()) {
            $this->error('No matching vendors found.');

            return self::FAILURE;
        }

        $summary = [];

        foreach ($vendors as $vendor) {
            if (empty($vendor->uuid)) {
                continue;
            }

            // 1. Fetch active DB records for this vendor
            $dbFiles = VendorStorageFile::where('vendor_id', $vendor->id)
                ->where('status', 'active')
                ->where('disk', $diskName)
                ->get();

            $recalculatedBytes = 0;
            $recalculatedCount = 0;
            $missingOnDiskCount = 0;

            $trackedPaths = [];

            foreach ($dbFiles as $file) {
                $trackedPaths[$file->path] = $file;

                if (Storage::disk($diskName)->exists($file->path)) {
                    $recalculatedBytes += (int) $file->size_bytes;
                    $recalculatedCount++;
                } else {
                    $missingOnDiskCount++;
                    $this->warn("Vendor {$vendor->id}: DB file missing on disk: {$file->path}");
                    if (! $isDryRun) {
                        $file->update(['status' => 'missing']);
                    }
                }
            }

            // 2. Scan physical disk files under vendor directory
            $vendorDiskDir = "vendors/{$vendor->uuid}";
            $orphanedFiles = [];

            if (Storage::disk($diskName)->exists($vendorDiskDir)) {
                $allDiskFiles = Storage::disk($diskName)->allFiles($vendorDiskDir);
                foreach ($allDiskFiles as $diskPath) {
                    if (! isset($trackedPaths[$diskPath])) {
                        $orphanedFiles[] = $diskPath;
                        $this->line("<fg=yellow>Orphaned disk file:</> {$diskPath}");

                        if ($deleteOrphans && ! $isDryRun) {
                            Storage::disk($diskName)->delete($diskPath);
                            $this->line("<fg=red>Deleted orphaned file:</> {$diskPath}");
                        }
                    }
                }
            }

            $diffBytes = $recalculatedBytes - (int) $vendor->storage_used_bytes;
            $diffCount = $recalculatedCount - (int) $vendor->storage_files_count;

            if (! $isDryRun && ($diffBytes !== 0 || $diffCount !== 0)) {
                Vendor::where('id', $vendor->id)->update([
                    'storage_used_bytes' => $recalculatedBytes,
                    'storage_files_count' => $recalculatedCount,
                ]);
            }

            $summary[] = [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'old_used_mb' => round($vendor->storage_used_bytes / (1024 * 1024), 2),
                'new_used_mb' => round($recalculatedBytes / (1024 * 1024), 2),
                'files_count' => $recalculatedCount,
                'missing_disk' => $missingOnDiskCount,
                'orphans' => count($orphanedFiles),
            ];
        }

        $this->table(
            ['ID', 'Vendor Name', 'Old Used (MB)', 'New Used (MB)', 'Active Files', 'Missing on Disk', 'Orphaned Files'],
            $summary
        );

        $this->info('Storage reconciliation complete.');

        return self::SUCCESS;
    }
}
