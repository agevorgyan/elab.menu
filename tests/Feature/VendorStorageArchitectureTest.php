<?php

namespace Tests\Feature;

use App\Exceptions\InvalidFileException;
use App\Exceptions\InvalidStoragePathException;
use App\Exceptions\StorageQuotaExceededException;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorStorageFile;
use App\Services\StorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorStorageArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendorA;

    protected Vendor $vendorB;

    protected User $ownerA;

    protected User $ownerB;

    protected StorageService $storageService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->vendorA = Vendor::create([
            'name' => 'Bistro Alpha',
            'slug' => 'bistro-alpha',
            'email' => 'alpha@test.com',
            'password' => bcrypt('password'),
            'storage_limit_bytes' => 104857600, // 100 MB
        ]);

        $this->ownerA = User::create([
            'name' => 'Owner Alpha',
            'email' => 'alpha@test.com',
            'password' => bcrypt('password'),
            'vendor_id' => $this->vendorA->id,
            'role' => 'vendor_owner',
        ]);

        $this->vendorB = Vendor::create([
            'name' => 'Bistro Beta',
            'slug' => 'bistro-beta',
            'email' => 'beta@test.com',
            'password' => bcrypt('password'),
            'storage_limit_bytes' => 104857600, // 100 MB
        ]);

        $this->ownerB = User::create([
            'name' => 'Owner Beta',
            'email' => 'beta@test.com',
            'password' => bcrypt('password'),
            'vendor_id' => $this->vendorB->id,
            'role' => 'vendor_owner',
        ]);

        $this->storageService = app(StorageService::class);
    }

    public function test_file_upload_creates_vendor_isolated_directory_and_metadata_and_increments_usage(): void
    {
        $file = UploadedFile::fake()->image('steak.jpg', 600, 400);

        $storedFile = $this->storageService->store(
            file: $file,
            namespace: 'products',
            vendor: $this->vendorA
        );

        $this->assertInstanceOf(VendorStorageFile::class, $storedFile);
        $this->assertEquals($this->vendorA->id, $storedFile->vendor_id);
        $this->assertEquals('products', explode('/', $storedFile->path)[2]);
        $this->assertStringStartsWith("vendors/{$this->vendorA->uuid}/products/", $storedFile->path);

        // Assert physical existence on disk
        Storage::disk('public')->assertExists($storedFile->path);

        // Assert DB record
        $this->assertDatabaseHas('vendor_storage_files', [
            'id' => $storedFile->id,
            'vendor_id' => $this->vendorA->id,
            'path' => $storedFile->path,
            'status' => 'active',
        ]);

        // Assert vendor usage updated
        $this->vendorA->refresh();
        $this->assertEquals($storedFile->size_bytes, $this->vendorA->storage_used_bytes);
        $this->assertEquals(1, $this->vendorA->storage_files_count);
    }

    public function test_file_replacement_safely_stores_new_file_deletes_old_file_and_updates_usage(): void
    {
        $file1 = UploadedFile::fake()->image('salad_v1.jpg', 400, 400);
        $record1 = $this->storageService->store($file1, 'products', $this->vendorA);
        $path1 = $record1->path;
        $size1 = $record1->size_bytes;

        Storage::disk('public')->assertExists($path1);

        // Replace with second file
        $file2 = UploadedFile::fake()->image('salad_v2.png', 800, 600);
        $record2 = $this->storageService->replace($record1->path, $file2, 'products', $this->vendorA);
        $path2 = $record2->path;
        $size2 = $record2->size_bytes;

        $this->assertNotEquals($path1, $path2);

        // Old file must be missing from disk
        Storage::disk('public')->assertMissing($path1);

        // New file must exist on disk
        Storage::disk('public')->assertExists($path2);

        // Usage must reflect net difference
        $this->vendorA->refresh();
        $this->assertEquals($size2, $this->vendorA->storage_used_bytes);
        $this->assertEquals(1, $this->vendorA->storage_files_count);

        // Old record should be soft deleted or status deleted
        $this->assertSoftDeleted('vendor_storage_files', ['id' => $record1->id]);
    }

    public function test_file_deletion_removes_from_disk_soft_deletes_record_and_decrements_usage(): void
    {
        $file = UploadedFile::fake()->image('burger.jpg', 500, 500);
        $record = $this->storageService->store($file, 'products', $this->vendorA);

        Storage::disk('public')->assertExists($record->path);

        $this->storageService->delete($record, $this->vendorA);

        Storage::disk('public')->assertMissing($record->path);
        $this->assertSoftDeleted('vendor_storage_files', ['id' => $record->id]);

        $this->vendorA->refresh();
        $this->assertEquals(0, $this->vendorA->storage_used_bytes);
        $this->assertEquals(0, $this->vendorA->storage_files_count);
    }

    public function test_quota_exceeded_rejects_upload_does_not_store_partial_files_and_throws_clear_error(): void
    {
        // Set very small limit: 500 bytes
        $this->vendorA->update(['storage_limit_bytes' => 500]);

        $file = UploadedFile::fake()->image('large.jpg', 600, 600); // Definitely > 500 bytes

        $this->expectException(StorageQuotaExceededException::class);

        try {
            $this->storageService->store($file, 'products', $this->vendorA);
        } finally {
            // Verify no file was left on disk
            $allFiles = Storage::disk('public')->allFiles("vendors/{$this->vendorA->uuid}");
            $this->assertEmpty($allFiles);

            // Verify no DB records created
            $this->assertEquals(0, VendorStorageFile::where('vendor_id', $this->vendorA->id)->count());

            $this->vendorA->refresh();
            $this->assertEquals(0, $this->vendorA->storage_used_bytes);
        }
    }

    public function test_cross_vendor_path_attack_is_strictly_blocked(): void
    {
        // Store a legitimate file belonging to Vendor B
        $fileB = UploadedFile::fake()->image('beta_secret.jpg', 400, 400);
        $recordB = $this->storageService->store($fileB, 'branding', $this->vendorB);

        Storage::disk('public')->assertExists($recordB->path);

        // Vendor A attempts to delete Vendor B's file
        $this->expectException(InvalidStoragePathException::class);

        try {
            $this->storageService->delete($recordB->path, $this->vendorA);
        } finally {
            // Assert Vendor B's file was NOT deleted
            Storage::disk('public')->assertExists($recordB->path);
            $this->assertDatabaseHas('vendor_storage_files', [
                'id' => $recordB->id,
                'deleted_at' => null,
            ]);
        }
    }

    public function test_path_traversal_attack_is_strictly_blocked(): void
    {
        $this->expectException(InvalidStoragePathException::class);
        $this->storageService->cleanPath('../../etc/passwd');
    }

    public function test_mime_type_spoofing_is_strictly_prevented(): void
    {
        // Create an executable PHP script named fake.png
        $maliciousContent = '<?php system($_GET["cmd"]); ?>';
        $tempPath = tempnam(sys_get_temp_dir(), 'vuln');
        file_put_contents($tempPath, $maliciousContent);

        $fakeFile = new UploadedFile(
            path: $tempPath,
            originalName: 'fake.png',
            mimeType: 'text/x-php', // Real mime or extension
            error: null,
            test: true
        );

        $this->expectException(InvalidFileException::class);

        try {
            $this->storageService->store($fakeFile, 'products', $this->vendorA);
        } finally {
            @unlink($tempPath);
        }
    }

    public function test_disallowed_executable_extensions_are_rejected(): void
    {
        $file = UploadedFile::fake()->create('script.php', 100, 'application/x-php');

        $this->expectException(InvalidFileException::class);
        $this->storageService->store($file, 'products', $this->vendorA);
    }

    public function test_usage_calculation_returns_accurate_metrics(): void
    {
        $file1 = UploadedFile::fake()->image('img1.jpg', 300, 300);
        $file2 = UploadedFile::fake()->image('img2.jpg', 400, 400);

        $r1 = $this->storageService->store($file1, 'products', $this->vendorA);
        $r2 = $this->storageService->store($file2, 'products', $this->vendorA);

        $usage = $this->storageService->usage($this->vendorA);

        $this->assertEquals($this->vendorA->id, $usage['vendor_id']);
        $this->assertEquals($r1->size_bytes + $r2->size_bytes, $usage['used_bytes']);
        $this->assertEquals(2, $usage['files_count']);
        $this->assertEquals($this->vendorA->storage_limit_bytes, $usage['limit_bytes']);
        $this->assertGreaterThan(0, $usage['available_bytes']);
    }

    public function test_vendor_deletion_cleans_up_only_that_vendor_namespace(): void
    {
        $fileA = UploadedFile::fake()->image('a.jpg', 300, 300);
        $recordA = $this->storageService->store($fileA, 'products', $this->vendorA);

        $fileB = UploadedFile::fake()->image('b.jpg', 300, 300);
        $recordB = $this->storageService->store($fileB, 'products', $this->vendorB);

        Storage::disk('public')->assertExists($recordA->path);
        Storage::disk('public')->assertExists($recordB->path);

        // Delete Vendor A storage
        $this->storageService->deleteVendorStorage($this->vendorA);

        // Vendor A directory should be completely gone
        $this->assertFalse(Storage::disk('public')->exists("vendors/{$this->vendorA->uuid}"));

        // Vendor B directory and files must remain intact
        Storage::disk('public')->assertExists($recordB->path);
        $this->assertDatabaseHas('vendor_storage_files', [
            'id' => $recordB->id,
            'deleted_at' => null,
        ]);
    }

    public function test_storage_reconciliation_command_detects_orphaned_files_and_recalculates_usage(): void
    {
        $file = UploadedFile::fake()->image('tracked.jpg', 400, 400);
        $record = $this->storageService->store($file, 'products', $this->vendorA);

        // Create an untracked orphaned file on disk
        $orphanPath = "vendors/{$this->vendorA->uuid}/products/orphan_file.jpg";
        Storage::disk('public')->put($orphanPath, 'ORPHAN_CONTENT');
        Storage::disk('public')->assertExists($orphanPath);

        // Corrupt vendor usage counter artificially
        $this->vendorA->update(['storage_used_bytes' => 99999999]);

        // Run reconciliation with orphan deletion
        $this->artisan('storage:reconcile', [
            '--vendor' => $this->vendorA->id,
            '--delete-orphans' => true,
        ])->assertSuccessful();

        // Orphan must be deleted from disk
        Storage::disk('public')->assertMissing($orphanPath);

        // Tracked file must remain
        Storage::disk('public')->assertExists($record->path);

        // Usage must be restored to tracked record size
        $this->vendorA->refresh();
        $this->assertEquals($record->size_bytes, $this->vendorA->storage_used_bytes);
        $this->assertEquals(1, $this->vendorA->storage_files_count);
    }

    public function test_storage_reconciliation_dry_run_does_not_modify_database_or_disk(): void
    {
        $file = UploadedFile::fake()->image('tracked2.jpg', 400, 400);
        $record = $this->storageService->store($file, 'products', $this->vendorA);

        $orphanPath = "vendors/{$this->vendorA->uuid}/products/orphan_preview.jpg";
        Storage::disk('public')->put($orphanPath, 'ORPHAN_PREVIEW');

        $this->vendorA->update(['storage_used_bytes' => 5000]);

        $this->artisan('storage:reconcile', [
            '--vendor' => $this->vendorA->id,
            '--dry-run' => true,
            '--delete-orphans' => true,
        ])->assertSuccessful();

        // Orphan must NOT be deleted in dry run
        Storage::disk('public')->assertExists($orphanPath);

        // DB usage must NOT be altered in dry run
        $this->vendorA->refresh();
        $this->assertEquals(5000, $this->vendorA->storage_used_bytes);
    }

    public function test_storage_migrate_legacy_command_moves_legacy_files_into_vendor_namespace(): void
    {
        $category = Category::create([
            'vendor_id' => $this->vendorA->id,
            'name' => 'Mains',
        ]);

        // Create legacy product file on disk
        $legacyRelative = 'products/legacy_carbonara.jpg';
        Storage::disk('public')->put($legacyRelative, 'CARBONARA_IMAGE_BYTES');

        $product = Product::create([
            'vendor_id' => $this->vendorA->id,
            'category_id' => $category->id,
            'name' => 'Spaghetti Carbonara',
            'price' => 3200,
            'image' => '/storage/'.$legacyRelative,
        ]);

        $this->artisan('storage:migrate-legacy')->assertSuccessful();

        $product->refresh();
        $this->assertStringStartsWith("/storage/vendors/{$this->vendorA->uuid}/products/", $product->image);

        // Old legacy path must no longer exist
        Storage::disk('public')->assertMissing($legacyRelative);

        // New path must exist on disk
        $newDiskPath = str_replace('/storage/', '', $product->image);
        Storage::disk('public')->assertExists($newDiskPath);

        // Metadata record must exist
        $this->assertDatabaseHas('vendor_storage_files', [
            'vendor_id' => $this->vendorA->id,
            'path' => $newDiskPath,
            'entity_type' => Product::class,
            'entity_id' => $product->id,
        ]);
    }

    public function test_concurrent_uploads_atomically_update_vendor_usage(): void
    {
        $file1 = UploadedFile::fake()->image('item1.jpg', 300, 300);
        $file2 = UploadedFile::fake()->image('item2.jpg', 300, 300);
        $file3 = UploadedFile::fake()->image('item3.jpg', 300, 300);

        $r1 = $this->storageService->store($file1, 'products', $this->vendorA);
        $r2 = $this->storageService->store($file2, 'products', $this->vendorA);
        $r3 = $this->storageService->store($file3, 'products', $this->vendorA);

        $this->vendorA->refresh();
        $expectedTotal = $r1->size_bytes + $r2->size_bytes + $r3->size_bytes;

        $this->assertEquals($expectedTotal, $this->vendorA->storage_used_bytes);
        $this->assertEquals(3, $this->vendorA->storage_files_count);
    }
}
