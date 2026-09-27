# Vendor Lifecycle Management Architecture

## 1. Executive Summary & Core Principles

In accordance with **PHASE 4 — Vendor Lifecycle Management**, the QR Menu SaaS platform ([elab.menu](https://github.com/agevorgyan/elab.menu)) enforces a safe, staged, auditable, and legally compliant lifecycle for all tenants.

### Core Architecture Rules:
1. **No Instant Hard-Deletes**: Immediate invocations of `Vendor::delete()` or `Vendor::destroy()` are strictly prohibited. Deletion is a multi-step orchestrated workflow.
2. **State Machine Integrity**: Vendors transition through formal lifecycle states:
   - `active`
   - `suspended`
   - `termination_requested`
   - `retention`
   - `deletion_queued`
   - `deleting`
   - `deleted`
3. **Defense in Depth**: Cross-vendor termination or deletion attempts are blocked at the service and authorization boundary with `AccessDeniedHttpException`.
4. **Idempotent & Retryable Pipeline**: Every step of the deletion pipeline tracks completion state in `vendor_deletion_jobs.steps_completed`. If a failure occurs (e.g. disk or network timeout), the job can be safely retried and resumes from the failed step without duplicate deletion attempts.
5. **Legal & Financial Data Preservation**: Strict separation between ephemeral operational data and statutory compliance records. Billing ledgers and payment attempts are permanently preserved for tax and audit compliance.
6. **Configurable Retention**: Operational data is held in retention (default 30 days) before permanent removal is queued, giving vendors a window for dispute resolution, recovery, or regulatory hold.

---

## 2. Termination Flow & State Machine

```
[ Active ]
   │
   ├── (Policy violation / Admin action) ──► [ Suspended ]
   │                                             │
   ▼                                             ▼
[ Termination Requested ] ───────────────────────┘
   │ (Immediate access lockout)
   ▼
[ Retention Period (e.g. 30 days) ] ── (Premature deletion blocked)
   │
   ▼ (Retention expires OR Admin --force)
[ Deletion Queued ]
   │
   ▼ (Job dispatched to worker or CLI)
[ Deleting ]
   ├── Step 1: Suspend Access & Lockout
   ├── Step 2: Database Cleanup (Strict dependency order)
   ├── Step 3: Storage Cleanup (Namespace eradication)
   ├── Step 4: Cache Cleanup (Storefront & menu purge)
   ├── Step 5: Queue Cleanup (Pending tasks cancelled)
   ├── Step 6: Domain Cleanup (Custom hostnames unbound)
   └── Step 7: Final Audit Record
   │
   ▼
[ Deleted ] (Vendor soft-deleted, ledger preserved)
```

---

## 3. Data Deletion Dependency Order

When the deletion pipeline executes (`deleteTenantData`), operational entities are removed in strict dependency order within a database transaction:

| Step | Entity | Table | Cleanup Action | Rationale |
| :---: | :--- | :--- | :--- | :--- |
| **1** | AI Waiter Sessions | `ai_waiter_sessions` | Hard Delete | Ephemeral chat interactions referencing orders/locations. |
| **2** | Waiter Calls | `waiter_calls` | Hard Delete | Table service calls referencing locations. |
| **3** | Product Overrides | `location_product_overrides`| Hard Delete | Pivot linking products and locations. |
| **4** | Product Variations & Addons | `product_variations` | Hard Delete | Product children rows. |
| **5** | Products | `products` | Force Delete | Menu catalog items. Associated storage images deleted in Step 3. |
| **6** | Categories | `categories` | Force Delete | Menu grouping taxonomy. |
| **7** | Order Items | `order_items` | Hard Delete | Order line items. |
| **8** | Orders | `orders` | Force Delete | Operational order transactions. |
| **9** | Customers | `customers` | Hard Delete | Tenant customer profiles and marketing records. |
| **10**| Analytics Logs | `analytics_logs` | Hard Delete | Pageview telemetry and visit metrics. |
| **11**| Tenant Users | `users` (`where vendor_id = ?`) | Hard Delete | Staff, managers, and vendor owners. **Platform superadmins are strictly protected.** |
| **12**| Locations | `locations` | Hard Delete | Physical branches and tables. |

---

## 4. Protected Data Policy (What is Retained)

The following entities are **strictly protected from permanent deletion**:

### 1. Billing & Legal Records
- **`subscription_payments`**:
  - Contains invoice numbers, payment gateway transaction IDs, amounts, currencies, tax IDs, and payment timestamps.
  - **Legal Requirement**: Financial and accounting regulations require retaining statutory invoice ledgers for a minimum of 5 to 7 years.
  - **Preservation Policy**: These rows are never hard-deleted. They remain linked to the soft-deleted `vendor_id`.
- **`payment_attempts`**:
  - Payment gateway session attempts, client payloads, and callback verification logs.
  - **Compliance Requirement**: Preserved for dispute resolution, chargeback defense, and audit trails.

### 2. Platform-Owned Data
- **`subscription_plans`**: Global plan configurations.
- **`system_settings`**: Global platform configurations.
- **Platform Superadmins**: Users with `vendor_id = null` and `role = 'superadmin'` are never touched or altered during tenant deletion.

### 3. Lifecycle Audit Trail
- **`vendor_lifecycle_logs`**: Permanent immutable record of every lifecycle event (who requested termination, when, reason, state transitions, step outcomes, and errors).
- **`vendor_deletion_jobs`**: Job records tracking UUID, step progression, attempt counts, and error traces.

---

## 5. Storage, Cache, and Domain Cleanup

### Storage Cleanup (`deleteTenantStorage`)
Delegates to [`StorageService::deleteVendorStorage($vendor)`](file:///Users/apple/Projects/qrmenu/app/Services/StorageService.php):
- Completely purges the vendor's isolated directory: `storage/app/public/vendors/{vendor_uuid}/`.
- Soft-deletes all entries in `vendor_storage_files`.
- Resets vendor usage counters (`storage_used_bytes = 0`, `storage_files_count = 0`).
- **Isolation Guarantee**: Vendor A's storage cleanup can never delete or affect Vendor B's storage namespace.

### Cache Cleanup (`cleanupCacheAndQueues`)
- Flushes vendor-specific cache keys:
  - `vendor_{id}_config`
  - `vendor_{slug}_menu`
  - `domain_{custom_domain}`

### Domain Cleanup (`cleanupDomains`)
- De-registers custom domains (`$vendor->custom_domain = null`).
- Flushes reverse-proxy and routing cache for the custom host.

---

## 6. Service API Specification

[`App\Services\VendorLifecycleService`](file:///Users/apple/Projects/qrmenu/app/Services/VendorLifecycleService.php) exposes the following API:

```php
namespace App\Services;

class VendorLifecycleService
{
    // Suspend vendor access immediately with audit logging
    public function suspend(Vendor $vendor, ?string $reason = null, ?User $actor = null): Vendor;

    // Request termination, immediately suspend access, and start retention period
    public function requestTermination(Vendor $vendor, string $reason, ?User $actor = null, ?int $retentionDays = null): Vendor;

    // Explicitly configure or extend retention period
    public function startRetention(Vendor $vendor, ?int $retentionDays = null, ?User $actor = null): Vendor;

    // Queue vendor for deletion after verifying retention deadline
    public function queueDeletion(Vendor $vendor, bool $force = false, ?User $actor = null): VendorDeletionJob;

    // Execute the idempotent, multi-step deletion pipeline
    public function executeDeletion(VendorDeletionJob $deletionJob, bool $force = false): void;

    // Clean up tenant operational database records
    public function deleteTenantData(Vendor $vendor, ?VendorDeletionJob $deletionJob = null): array;

    // Delete tenant physical storage namespace
    public function deleteTenantStorage(Vendor $vendor, ?VendorDeletionJob $deletionJob = null): bool;

    // Soft-delete vendor and mark job complete
    public function finalizeDeletion(Vendor $vendor, ?VendorDeletionJob $deletionJob = null): void;
}
```

---

## 7. CLI & Queue Automation

### Artisan Deletion Command
```bash
# Queue and execute deletion (fails if retention period is active)
php artisan vendor:delete {vendor_id_or_slug}

# Force deletion immediately (superadmin override, bypasses retention)
php artisan vendor:delete {vendor_id_or_slug} --force

# Retry a previously failed deletion job from the failed step
php artisan vendor:delete {vendor_id_or_slug} --retry
```

### Queueable Job
[`App\Jobs\ProcessVendorDeletionJob`](file:///Users/apple/Projects/qrmenu/app/Jobs/ProcessVendorDeletionJob.php):
- Implements `ShouldQueue` with exponential backoff and 3 retry attempts.
- Executes `VendorLifecycleService::executeDeletion()`.

---

## 8. Automated Test Matrix

The lifecycle management system is verified by automated test suites in [`tests/Feature/VendorLifecycleManagementTest.php`](file:///Users/apple/Projects/qrmenu/tests/Feature/VendorLifecycleManagementTest.php) (10 tests, 62 assertions):

| Test Case | Verification Criteria |
| :--- | :--- |
| `test_vendor_suspension_disables_access_and_records_audit_log` | Immediate access lockout, state='suspended', audit log created. |
| `test_termination_request_suspends_access_and_initiates_retention_period` | Immediate lockout, state='retention', retention deadline 14 days in future, audit log with reason. |
| `test_premature_deletion_rejected_while_retention_period_is_active` | `RetentionPeriodActiveException` thrown, no deletion job created. |
| `test_deletion_queued_successfully_after_retention_period_expires` | `VendorDeletionJob` created with status='pending', vendor state='deletion_queued'. |
| `test_complete_vendor_deletion_pipeline_deletes_tenant_data_and_storage_while_protecting_billing_and_platform_data` | All 10 operational entities deleted, storage namespace purged, vendor soft-deleted, `SubscriptionPayment` preserved, superadmin protected. |
| `test_cross_vendor_termination_and_deletion_is_strictly_blocked` | Vendor A cannot terminate Vendor B (403 `AccessDeniedHttpException`). |
| `test_deletion_retry_after_failure_resumes_from_failed_step_without_duplication` | Failed step 3 retried successfully, completed steps 1 & 2 skipped idempotently. |
| `test_storage_deletion_removes_only_target_vendor_files` | Deleting Vendor A leaves Vendor B storage 100% intact. |
| `test_cache_and_custom_domain_cleanup` | Cache keys flushed, custom domain unbound. |
| `test_vendor_delete_artisan_command_end_to_end` | Artisan command runs end-to-end with `--force`, outputs success. |
