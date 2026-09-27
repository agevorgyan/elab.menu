# Vendor-Isolated Storage Architecture

## 1. Executive Summary & Objective

In accordance with **PHASE 3 — Vendor-Isolated Storage Architecture**, the QR Menu SaaS platform ([elab.menu](https://github.com/agevorgyan/elab.menu)) enforces complete physical and logical isolation for all tenant assets.

### Core Guarantees:
1. **Isolated Namespaces**: No tenant file is ever stored under shared directories (such as `products/`, `branding/`, or `uploads/`). Every vendor operates strictly within a dedicated, UUID-bound storage directory: `vendors/{vendor_uuid}/...`.
2. **Deterministic Vendor Scoping**: Storage paths are generated deterministically by the system using server-side UUIDs. User input cannot dictate destination paths or manipulate namespace directories.
3. **Defense Against Traversal & Cross-Tenant Attacks**: Path traversal sequences (`..`, `\`) and cross-vendor path operations are blocked and trigger security exceptions. Vendor A cannot access, replace, or delete Vendor B files.
4. **Strict Security Validation**: Real MIME types are verified via file inspection (`finfo`) to prevent executable script spoofing (e.g. PHP scripts disguised as `.png` or `.jpg`).
5. **Atomic Quota Tracking**: The `vendor_storage_files` table acts as the ledger of truth. Vendor storage usage (`storage_used_bytes`, `storage_files_count`) is updated atomically and reconciled via automated tooling.
6. **No Secrets or Credentials**: This storage architecture is strictly designed for media and documents. No secrets, credentials, private keys, or API tokens may ever be stored in this system.

---

## 2. Vendor Storage Namespace Layout

Each vendor is allocated an isolated directory keyed by their immutable `uuid`:

```
storage/app/public/
└── vendors/
    └── {vendor_uuid}/
        ├── products/       # Menu dishes, drinks, variation photos
        ├── branding/       # Vendor logo, header cover image, favicon
        ├── gallery/        # Venue interior, ambiance, and food gallery
        ├── qr/             # Generated SVG and PNG table QR codes
        ├── documents/      # Menu spreadsheets, CSVs, nutritional PDFs
        └── temp/           # Ephemeral processing buffers
```

### Whitelisted Namespaces:
- `products`: Images only (`jpeg`, `png`, `webp`, `gif`, `avif`).
- `branding`: Images only (`jpeg`, `png`, `webp`, `gif`, `svg`, `ico`).
- `gallery`: Images only (`jpeg`, `png`, `webp`, `gif`).
- `qr`: Vector and raster QR assets (`png`, `svg`, `jpeg`).
- `documents`: Catalogs and exports (`pdf`, `csv`, `xlsx`, `xls`, `docx`, `doc`, `txt`, `json`).
- `temp`: Ephemeral files.

Any request targeting a non-whitelisted namespace is rejected immediately with an `InvalidStoragePathException`.

---

## 3. Database Metadata Ledger & Quota Model

### `vendor_storage_files` Table

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Primary Key | Auto-incrementing identifier. |
| `vendor_id` | `BIGINT UNSIGNED` | Foreign Key (`vendors.id`, CASCADE) | Owning tenant identifier. |
| `uuid` | `CHAR(36)` | Unique Index | Public / API identifier for the file. |
| `disk` | `VARCHAR(255)` | Default `'public'` | Storage disk driver. |
| `path` | `VARCHAR(255)` | Index | Full relative path: `vendors/{uuid}/{namespace}/{file_uuid}.{ext}`. |
| `original_name`| `VARCHAR(255)` | Not Null | Sanitized client-supplied filename at upload time. |
| `mime_type` | `VARCHAR(255)` | Not Null | Inspected MIME type (e.g. `image/jpeg`). |
| `size_bytes` | `BIGINT UNSIGNED` | Not Null | File size in bytes. |
| `checksum` | `CHAR(64)` | Not Null | SHA-256 hash of file contents. |
| `entity_type` | `VARCHAR(255)` | Nullable | Morph relation class (e.g. `App\Models\Product`). |
| `entity_id` | `BIGINT UNSIGNED` | Nullable | Morph relation ID. |
| `status` | `VARCHAR(32)` | Default `'active'` | `'active'`, `'missing'`, `'deleted'`. |
| `created_at` | `TIMESTAMP` | Nullable | Creation timestamp. |
| `updated_at` | `TIMESTAMP` | Nullable | Update timestamp. |
| `deleted_at` | `TIMESTAMP` | Nullable | Soft-delete timestamp. |

### Quota Attributes on `vendors` Table

- **`uuid`**: `CHAR(36) UNIQUE` — Persistent tenant storage identifier.
- **`storage_limit_bytes`**: `BIGINT UNSIGNED` — Quota ceiling in bytes (default: 100 MB).
- **`storage_used_bytes`**: `BIGINT UNSIGNED` — Current cumulative byte count.
- **`storage_files_count`**: `INT UNSIGNED` — Total count of active stored files.

### Default Quotas by Subscription Plan

| Plan Slug | Quota Limit (Bytes) | Human-Readable |
| :--- | :--- | :--- |
| **`basic`** | `104,857,600` | 100 MB |
| **`pro`** | `524,288,000` | 500 MB |
| **`business`** | `2,147,483,648` | 2 GB |
| **`custom`** | `10,737,418,240` | 10 GB |

When an upload or replacement exceeds `storage_limit_bytes`:
- The upload is immediately aborted before any file is written to disk.
- A `StorageQuotaExceededException` is thrown with exact limits and usage metrics.
- No partial or corrupted files are retained.

---

## 4. Centralized StorageService API

All file storage operations must be routed through `App\Services\StorageService`. Controllers are strictly prohibited from manipulating physical disk paths directly.

```php
namespace App\Services;

class StorageService
{
    public function store(UploadedFile $file, string $namespace, ?Vendor $vendor = null, ?Model $entity = null, string $disk = 'public'): VendorStorageFile;
    public function replace(?string $oldPathOrUuid, UploadedFile $newFile, string $namespace, ?Vendor $vendor = null, ?Model $entity = null, string $disk = 'public'): VendorStorageFile;
    public function delete(string|VendorStorageFile $fileOrPath, ?Vendor $vendor = null, string $disk = 'public'): bool;
    public function exists(string|VendorStorageFile $fileOrPath, ?Vendor $vendor = null, string $disk = 'public'): bool;
    public function usage(?Vendor $vendor = null): array;
    public function quota(?Vendor $vendor = null): int;
    public function canUpload(?Vendor $vendor, int $bytes): bool;
    public function deleteVendorStorage(Vendor $vendor, string $disk = 'public'): bool;
}
```

### Safe Replacement Lifecycle
To prevent data loss and orphaned files, `replace()` executes in four strictly ordered steps:
1. **Quota Check**: Verifies that `used_bytes + (new_size - old_size) <= limit_bytes`.
2. **Store New File**: New file is written to `vendors/{vendor_uuid}/{namespace}/{new_uuid}.{ext}` and recorded in `vendor_storage_files`.
3. **Remove Old File**: The prior file is physically unlinked from disk and its metadata marked soft-deleted.
4. **Atomic Usage Adjustment**: Vendor `storage_used_bytes` is incremented/decremented by the net byte delta.
5. **Consistency Rollback**: If any subsequent step fails, newly written disk files are immediately purged.

---

## 5. Security Controls & Attack Defenses

| Threat Vector | Attack Scenario | Defense Mechanism |
| :--- | :--- | :--- |
| **Path Traversal** | Request passes `../../secret.json` or `..\config.php` | `cleanPath()` strips prefixes and validates absence of `..` or `\`. Throws `InvalidStoragePathException`. |
| **Cross-Tenant Deletion** | Vendor A calls delete on `vendors/{uuid_B}/products/img.jpg` | `assertVendorOwnsPath()` compares resolved vendor UUID against path namespace. Throws `InvalidStoragePathException`. |
| **MIME Spoofing** | Malicious PHP script renamed to `backdoor.jpg` | PHP Fileinfo inspects raw file headers (`finfo_file`). Script execution signatures reject the upload with `InvalidFileException`. |
| **Executable Extension** | Upload of `.php`, `.phar`, `.sh`, `.exe`, etc. | Extension blacklist rejects dangerous execution types. |
| **User-Controlled Paths** | Attacker crafts custom filename to overwrite system files | User filename is stored as descriptive metadata only (`original_name`). Disk filename is always random `Str::uuid()`. |
| **Storage Denial of Service** | Excessive file uploads exhausting server disk | Enforced plan-based quota limits reject uploads before disk allocation. |

---

## 6. CLI Tooling & Maintenance

### 1. Storage Reconciliation Command
```bash
# Preview reconciliation without touching database or disk
php artisan storage:reconcile --dry-run

# Reconcile specific vendor and purge unreferenced orphaned disk files
php artisan storage:reconcile --vendor=1 --delete-orphans
```
- Recalculates `storage_used_bytes` and `storage_files_count` from database records confirmed to exist on disk.
- Detects database records whose physical files are missing from disk.
- Detects orphaned files present on disk that have no corresponding database record.

### 2. Legacy Shared Storage Migration Command
```bash
# Preview legacy migration
php artisan storage:migrate-legacy --dry-run

# Execute migration of legacy shared paths (products/*, branding/*) to vendors/{uuid}/*
php artisan storage:migrate-legacy
```
- Migrates existing product and branding images into vendor-isolated directories.
- Automatically generates SHA-256 checksums and creates `VendorStorageFile` records.
- Updates model URLs (`Product::image`, `Vendor::logo`, `Vendor::cover_image`).
- Zero data loss guarantee: leaves unresolvable files untouched and logs warnings.

---

## 7. Automated Test Coverage

The storage architecture is verified by automated test suites:
- [`VendorStorageArchitectureTest.php`](file:///Users/apple/Projects/qrmenu/tests/Feature/VendorStorageArchitectureTest.php) (14 tests, 57 assertions):
  - File upload into vendor-isolated directories
  - Safe replacement protocol
  - Deletion and atomic quota decrements
  - Quota exceeded enforcement and partial-file prevention
  - Cross-vendor path attack prevention
  - Path traversal attack prevention
  - MIME type spoofing detection
  - Disallowed executable extension rejection
  - Accurate usage metrics calculation
  - Vendor deletion namespace cleanup
  - Orphaned file detection and storage reconciliation
  - Dry-run verification for reconciliation and migration
  - Legacy migration script verification
  - Concurrent atomic upload tracking
- [`ProductStorageCleanupTest.php`](file:///Users/apple/Projects/qrmenu/tests/Feature/ProductStorageCleanupTest.php) (5 tests, 21 assertions):
  - Updated to assert storage within `vendors/{vendor_uuid}/products/` and `vendors/{vendor_uuid}/branding/`.
- [`ProductImageUploadTest.php`](file:///Users/apple/Projects/qrmenu/tests/Feature/ProductImageUploadTest.php) (3 tests, 13 assertions).
