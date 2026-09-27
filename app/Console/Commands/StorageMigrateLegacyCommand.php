<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorStorageFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageMigrateLegacyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:migrate-legacy
                            {--dry-run : Preview file migrations without making changes}
                            {--disk=public : Storage disk name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy shared storage files into vendor-isolated namespaces';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $diskName = (string) ($this->option('disk') ?: 'public');

        if ($isDryRun) {
            $this->warn('Running in DRY-RUN mode. No files will be moved or database records updated.');
        }

        $migratedProductsCount = 0;
        $migratedBrandingCount = 0;
        $totalBytesMigrated = 0;

        // 1. Migrate Products Images
        $this->info('Scanning products for legacy images...');
        $products = Product::withoutGlobalScopes()->whereNotNull('image')->get();

        foreach ($products as $product) {
            $raw = $product->getRawOriginal('image');
            if (empty($raw) || str_contains($raw, 'default-dish') || str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
                continue;
            }

            $cleanPath = ltrim(str_replace('/storage/', '', $raw), '/');
            if (str_starts_with($cleanPath, 'vendors/')) {
                // Already in vendor isolated namespace
                continue;
            }

            $vendor = $product->vendor ?: Vendor::find($product->vendor_id);
            if (! $vendor) {
                $this->warn("Product #{$product->id} has no valid vendor; skipping.");

                continue;
            }

            if (empty($vendor->uuid)) {
                $vendor->uuid = (string) Str::uuid();
                if (! $isDryRun) {
                    $vendor->saveQuietly();
                }
            }

            if (! Storage::disk($diskName)->exists($cleanPath)) {
                $this->warn("Product #{$product->id} image file not found on disk: {$cleanPath}");

                continue;
            }

            $fileSize = Storage::disk($diskName)->size($cleanPath);
            $mimeType = Storage::disk($diskName)->mimeType($cleanPath) ?: 'image/jpeg';
            $fileUuid = (string) Str::uuid();
            $ext = pathinfo($cleanPath, PATHINFO_EXTENSION) ?: 'jpg';
            $newPath = "vendors/{$vendor->uuid}/products/{$fileUuid}.{$ext}";

            if ($isDryRun) {
                $this->line("<fg=cyan>[DRY-RUN]</> Move Product #{$product->id} image: {$cleanPath} -> {$newPath} ({$fileSize} bytes)");
            } else {
                $fullOld = Storage::disk($diskName)->path($cleanPath);
                $checksum = file_exists($fullOld) ? hash_file('sha256', $fullOld) : '';

                Storage::disk($diskName)->move($cleanPath, $newPath);

                VendorStorageFile::create([
                    'vendor_id' => $vendor->id,
                    'uuid' => $fileUuid,
                    'disk' => $diskName,
                    'path' => $newPath,
                    'original_name' => basename($cleanPath),
                    'mime_type' => $mimeType,
                    'size_bytes' => $fileSize,
                    'checksum' => $checksum,
                    'entity_type' => Product::class,
                    'entity_id' => $product->id,
                    'status' => 'active',
                ]);

                $product->image = '/storage/'.$newPath;
                $product->saveQuietly();

                Vendor::where('id', $vendor->id)->update([
                    'storage_used_bytes' => DB::raw("storage_used_bytes + {$fileSize}"),
                    'storage_files_count' => DB::raw('storage_files_count + 1'),
                ]);
            }

            $migratedProductsCount++;
            $totalBytesMigrated += $fileSize;
        }

        // 2. Migrate Vendor Branding (logo and cover_image)
        $this->info('Scanning vendor brandings for legacy assets...');
        $vendors = Vendor::all();

        foreach ($vendors as $vendor) {
            if (empty($vendor->uuid)) {
                $vendor->uuid = (string) Str::uuid();
                if (! $isDryRun) {
                    $vendor->saveQuietly();
                }
            }

            // Check logo
            if (! empty($vendor->logo) && ! str_starts_with($vendor->logo, 'http://') && ! str_starts_with($vendor->logo, 'https://')) {
                $cleanLogo = ltrim(str_replace('/storage/', '', $vendor->logo), '/');
                if (! str_starts_with($cleanLogo, 'vendors/') && Storage::disk($diskName)->exists($cleanLogo)) {
                    $size = Storage::disk($diskName)->size($cleanLogo);
                    $fileUuid = (string) Str::uuid();
                    $ext = pathinfo($cleanLogo, PATHINFO_EXTENSION) ?: 'png';
                    $newLogoPath = "vendors/{$vendor->uuid}/branding/{$fileUuid}.{$ext}";

                    if ($isDryRun) {
                        $this->line("<fg=cyan>[DRY-RUN]</> Move Vendor #{$vendor->id} logo: {$cleanLogo} -> {$newLogoPath}");
                    } else {
                        $fullOld = Storage::disk($diskName)->path($cleanLogo);
                        $checksum = file_exists($fullOld) ? hash_file('sha256', $fullOld) : '';

                        Storage::disk($diskName)->move($cleanLogo, $newLogoPath);

                        VendorStorageFile::create([
                            'vendor_id' => $vendor->id,
                            'uuid' => $fileUuid,
                            'disk' => $diskName,
                            'path' => $newLogoPath,
                            'original_name' => basename($cleanLogo),
                            'mime_type' => Storage::disk($diskName)->mimeType($newLogoPath) ?: 'image/png',
                            'size_bytes' => $size,
                            'checksum' => $checksum,
                            'entity_type' => Vendor::class,
                            'entity_id' => $vendor->id,
                            'status' => 'active',
                        ]);

                        $vendor->logo = '/storage/'.$newLogoPath;
                        $vendor->saveQuietly();

                        Vendor::where('id', $vendor->id)->update([
                            'storage_used_bytes' => DB::raw("storage_used_bytes + {$size}"),
                            'storage_files_count' => DB::raw('storage_files_count + 1'),
                        ]);
                    }

                    $migratedBrandingCount++;
                    $totalBytesMigrated += $size;
                }
            }

            // Check cover_image
            if (! empty($vendor->cover_image) && ! str_starts_with($vendor->cover_image, 'http://') && ! str_starts_with($vendor->cover_image, 'https://')) {
                $cleanCover = ltrim(str_replace('/storage/', '', $vendor->cover_image), '/');
                if (! str_starts_with($cleanCover, 'vendors/') && Storage::disk($diskName)->exists($cleanCover)) {
                    $size = Storage::disk($diskName)->size($cleanCover);
                    $fileUuid = (string) Str::uuid();
                    $ext = pathinfo($cleanCover, PATHINFO_EXTENSION) ?: 'jpg';
                    $newCoverPath = "vendors/{$vendor->uuid}/branding/{$fileUuid}.{$ext}";

                    if ($isDryRun) {
                        $this->line("<fg=cyan>[DRY-RUN]</> Move Vendor #{$vendor->id} cover: {$cleanCover} -> {$newCoverPath}");
                    } else {
                        $fullOld = Storage::disk($diskName)->path($cleanCover);
                        $checksum = file_exists($fullOld) ? hash_file('sha256', $fullOld) : '';

                        Storage::disk($diskName)->move($cleanCover, $newCoverPath);

                        VendorStorageFile::create([
                            'vendor_id' => $vendor->id,
                            'uuid' => $fileUuid,
                            'disk' => $diskName,
                            'path' => $newCoverPath,
                            'original_name' => basename($cleanCover),
                            'mime_type' => Storage::disk($diskName)->mimeType($newCoverPath) ?: 'image/jpeg',
                            'size_bytes' => $size,
                            'checksum' => $checksum,
                            'entity_type' => Vendor::class,
                            'entity_id' => $vendor->id,
                            'status' => 'active',
                        ]);

                        $vendor->cover_image = '/storage/'.$newCoverPath;
                        $vendor->saveQuietly();

                        Vendor::where('id', $vendor->id)->update([
                            'storage_used_bytes' => DB::raw("storage_used_bytes + {$size}"),
                            'storage_files_count' => DB::raw('storage_files_count + 1'),
                        ]);
                    }

                    $migratedBrandingCount++;
                    $totalBytesMigrated += $size;
                }
            }
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Products Migrated', $migratedProductsCount],
                ['Brandings Migrated', $migratedBrandingCount],
                ['Total Data Migrated (KB)', round($totalBytesMigrated / 1024, 2)],
                ['Status', $isDryRun ? 'DRY-RUN COMPLETE (No changes saved)' : 'MIGRATION COMPLETE'],
            ]
        );

        return self::SUCCESS;
    }
}
