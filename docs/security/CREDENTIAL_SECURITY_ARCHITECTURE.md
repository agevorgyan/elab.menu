# Credential Security Architecture (Phase 5)

## 1. Executive Summary

In multi-tenant SaaS environments, third-party API keys, payment provider secrets, bot tokens, and network passwords represent high-value targets. Previously, vendor secrets (AI API keys, Telegram tokens, payment gateway keys) were stored in plaintext JSON columns within the `vendors` table (`ai_settings`, `payment_settings`, `telegram_settings`) and returned directly to administrative form inputs in raw HTML.

**Phase 5 establishes a hardened credential security architecture:**
- **Zero Plaintext at Rest:** Dedicated `vendor_credentials` table with AES-256-CBC/GCM Laravel application encryption (`encrypted` cast).
- **Zero Plaintext in Memory & Debug:** Eloquent model overrides `__debugInfo()` to redact `encrypted_value => '[REDACTED]'`.
- **Zero Plaintext in Frontend / API:** Secret fields never output plaintext in Blade views, JSON endpoints, or browser localStorage. Admin forms return `configured: true` along with high-entropy masked values (e.g. `sk_live_••••••••a1b2`).
- **Preservation on Submit:** Submitting an empty or masked field preserves the existing secret, preventing accidental credential overwrites.
- **Leakage Prevention in Logs & Exceptions:** Standard HTTP client exception URLs and query parameters (such as Telegram Bot API tokens embedded in endpoint URLs) are automatically redacted before logging.
- **Lifecycle Integration:** Seamless credential rotation, deletion, and automated tenant data destruction during vendor lifecycle termination.
- **Idempotent Migration Path:** Built-in Artisan migration command `php artisan credentials:migrate` with `--dry-run` to encrypt and scrub legacy JSON columns without vendor downtime.

---

## 2. Database Schema: `vendor_credentials`

The `vendor_credentials` table enforces strict relational integrity and isolation:

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `unsignedBigInteger` | Primary Key, Auto Increment | Unique record identifier |
| `vendor_id` | `unsignedBigInteger` | Foreign Key (`vendors.id`, cascade delete) | Tenant ownership boundary |
| `provider` | `string(64)` | Index | Provider code (`ai`, `telegram`, `stripe`, `idram`, `telcell`, `fastshift`, `arca`, `sms`) |
| `credential_type` | `string(64)` | Index | Secret classification (`api_key`, `bot_token`, `secret_key`, `key`) |
| `encrypted_value` | `text` | Encrypted Cast | Laravel-encrypted ciphertext payload |
| `metadata` | `json` | Nullable | Safe non-secret metadata (e.g., key hint, environment) |
| `last_verified_at`| `timestamp` | Nullable | Timestamp of last successful live connection test |
| `rotated_at` | `timestamp` | Nullable | Timestamp when key was replaced/rotated |
| `created_at` | `timestamp` | Nullable | Creation timestamp |
| `updated_at` | `timestamp` | Nullable | Modification timestamp |

### Indexes & Constraints
- `UNIQUE(vendor_id, provider, credential_type)`: Guarantees exactly one active secret per provider/type per tenant.
- `INDEX(vendor_id, provider)`: Fast lookups for vendor provider configurations.
- Foreign key constraint: `ON DELETE CASCADE` ensures foreign keys are cleanly purged if vendor record is hard-deleted.

---

## 3. Core Architecture Components

### 3.1. `App\Models\VendorCredential`
- Implements `App\Traits\BelongsToVendor`: Automatically applies `TenantScope` to all queries, enforcing multi-tenant isolation.
- `protected $casts = ['encrypted_value' => 'encrypted', 'metadata' => 'array', 'last_verified_at' => 'datetime', 'rotated_at' => 'datetime'];`
- `protected $hidden = ['encrypted_value'];`
- Overridden `__debugInfo()`: Explicitly masks `encrypted_value` to prevent accidental disclosure during `dd()`, `dump()`, or error stack traces.

### 3.2. `App\Services\CredentialService`
Centralized service managing all sensitive operations:
- `set(Vendor $vendor, string $provider, string $credentialType, string $value, array $metadata = []): VendorCredential`: Stores encrypted credential; automatically marks `rotated_at` if the value has changed.
- `get(Vendor $vendor, string $provider, string $credentialType, ?string $default = null): ?string`: Decrypts and retrieves the active credential, with fallback to legacy settings during migration transitions.
- `has(Vendor $vendor, string $provider, string $credentialType): bool`: Checks if credential is configured.
- `mask(?string $secret, int $visiblePrefix = 4, int $visibleSuffix = 4): string`: Generates safe masked representations (`sk_live_••••••••3f9a`).
- `rotate(Vendor $vendor, string $provider, string $credentialType, string $newValue): VendorCredential`: Replaces secret and timestamps `rotated_at`.
- `delete(Vendor $vendor, string $provider, string $credentialType): bool`: Purges secret from `vendor_credentials` and scrubs legacy JSON columns.
- `getStatus(Vendor $vendor, string $provider, string $credentialType): array`: Returns public status for UI consumption:
  ```json
  {
    "configured": true,
    "masked": "sk_l••••••••4f2b",
    "last_verified_at": "2026-09-26T20:30:00Z",
    "rotated_at": "2026-09-26T20:30:00Z"
  }
  ```
- `redactString(?string $text): ?string`: Regex scrubber that purges Telegram bot tokens, Stripe secrets, and URL query secrets from logs and exceptions.

### 3.3. Transparent Encryption for Wi-Fi Passwords
Branch and vendor Wi-Fi passwords (`wifi_password`) must be encrypted at rest in the database, but displayed to diners on the storefront customer modal.
- `Vendor` and `Location` models implement custom getters and setters using `Crypt::encryptString()` and `Crypt::decryptString()`.
- Legacy unencrypted database values are transparently decrypted and preserved without errors.

---

## 4. Leakage Prevention Matrix

| Attack / Leak Surface | Old Behavior | Phase 5 Hardened Behavior |
|---|---|---|
| **Database Compromise (SQL Dump)** | Plaintext API keys and secrets stored in JSON columns | Encrypted ciphertext via Laravel application key |
| **Admin Form Source (View Source)** | Raw secrets rendered in `<input value="...">` | Input rendered with `value=""` and masked placeholder |
| **Form Submission with Blank Secret** | Overwrote secret or required re-entering it | Blank input preserves existing encrypted credential |
| **API Responses (JSON Settings)** | Returned full credential objects | Returns scrubbed strings with `configured: true` & `masked` |
| **Tinker / Debugging (`dd($cred)`)** | Showed decrypted attributes | `__debugInfo()` replaces value with `[REDACTED]` |
| **Exception Logging (Guzzle/Telegram)** | Telegram bot token embedded in request URL logged to disk | `CredentialService::redactString` sanitizes log messages |
| **Cross-Tenant Querying** | Potential access if model lacked scope | `BelongsToVendor` + `TenantScope` enforces tenant filtering |
| **Vendor Lifecycle Termination** | Credentials remained in database | `deleteTenantData()` purges all credentials during deletion |

---

## 5. UI Secure Masking Pattern

In `resources/views/admin/settings/index.blade.php` and `resources/views/admin/settings/ai.blade.php`:

```html
<!-- Example: Stripe Secret Key -->
<input
    type="password"
    name="payment_settings[gateways][stripe][secret_key]"
    id="stripe_secret_key"
    value=""
    placeholder="{{ !empty($paymentSettings['gateways']['stripe']['masked']) ? $paymentSettings['gateways']['stripe']['masked'] : 'sk_live_...' }}"
    class="form-input font-mono text-sm"
>
@if(!empty($paymentSettings['gateways']['stripe']['configured']))
    <span class="text-xs text-emerald-600 font-medium">✓ Կարգավորված է</span>
@endif
```

When an admin updates other settings on the page and leaves `secret_key` blank:
1. `VendorSettingsController::update` detects the empty input.
2. It skips overwriting the credential in `CredentialService`.
3. It removes any plaintext key from the incoming array before storing safe metadata into `vendors.payment_settings`.

---

## 6. Migration & Scrubbing Command

The repository includes a dedicated command to audit and migrate existing vendor databases:

```bash
# Dry-run audit (inspect what would be migrated without touching the database):
php artisan credentials:migrate --dry-run

# Run migration for all vendors:
php artisan credentials:migrate

# Target a specific vendor ID:
php artisan credentials:migrate --vendor=42
```

### Migration Steps:
1. Audits `ai_settings` (Gemini, OpenAI, Claude keys).
2. Audits `telegram_settings` (`bot_token`).
3. Audits `payment_settings` (Idram, Telcell, FastShift, ArCa, Stripe keys).
4. Audits `wifi_password` on `vendors` and `locations`.
5. Encrypts and saves records to `vendor_credentials`.
6. Scrubs plaintext secrets from `ai_settings`, `telegram_settings`, and `payment_settings` JSON columns.
7. Encrypts plaintext Wi-Fi passwords at rest.

---

## 7. Verification & Automated Tests

Automated test suite: `tests/Feature/SecureVendorCredentialsTest.php`

| Test Name | Verifications |
|---|---|
| `test_sensitive_vendor_credentials_are_encrypted_at_rest_and_never_stored_in_plaintext` | Direct `DB::table` inspection verifies ciphertext at rest for AI, Stripe, Telegram, and Wi-Fi |
| `test_secret_is_never_returned_in_api_responses_or_admin_views` | Asserts HTML responses for `/admin/settings` and `/admin/settings/ai` never contain secret keys |
| `test_unauthorized_vendor_cannot_access_or_tamper_with_other_vendors_credentials` | Verifies `TenantScope` and `CredentialService` prevent cross-tenant credential access |
| `test_logs_and_debug_output_do_not_contain_secrets` | Validates `__debugInfo()` redaction and string scrubber on Guzzle/Telegram URLs |
| `test_credential_rotation_updates_rotated_at_timestamp_and_updates_active_value` | Validates `rotate()` updates `rotated_at` and updates active decryption |
| `test_credential_deletion_removes_from_database_and_scrubs_legacy_columns` | Validates `delete()` removes DB records and scrubs legacy JSON settings |
| `test_blank_input_submission_preserves_existing_credentials` | Validates admin form submissions with blank inputs do not wipe out active credentials |
| `test_credentials_migrate_command_safely_migrates_legacy_plaintext_settings` | Tests end-to-end migration, scrubbing, and backwards compatibility |
| `test_vendor_lifecycle_deletion_cleans_up_vendor_credentials` | Confirms vendor lifecycle termination removes all `vendor_credentials` |

**Suite Result:**
- **9 tests passed**
- **54 assertions**
- **Full suite passing:** 324 tests, 1683 assertions.
