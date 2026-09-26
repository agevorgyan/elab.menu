# QR Menu SaaS — Security & Production Hardening Roadmap (Phases 1 – 15)

**Document Version:** 1.0.0  
**Phase:** Phase 0 Implementation Output  
**Date:** 2026-09-26  
**Repository:** [elab.menu](https://github.com/agevorgyan/elab.menu)  
**Framework:** Laravel 13.x | PHP 8.5  

---

## Roadmap Overview & Execution Timeline

This document defines the complete 15-phase implementation plan for hardening the QR Menu SaaS platform. The roadmap directly addresses all findings identified in the [Security Baseline](file:///Users/apple/Projects/qrmenu/docs/security/SECURITY_BASELINE.md).

```
┌────────────────────────────────────────────────────────────────────────┐
│  Phase 1: Critical P0 Patching & Access Control Hotfixes               │
├────────────────────────────────────────────────────────────────────────┤
│  Phase 2: Multi-Tenant Architecture & Data Isolation Hardening         │
├────────────────────────────────────────────────────────────────────────┤
│  Phase 3: Cryptographic Protection of Secrets & Data at Rest           │
├────────────────────────────────────────────────────────────────────────┤
│  Phase 4: Authentication, Session Management & 2FA Hardening           │
├────────────────────────────────────────────────────────────────────────┤
│  Phase 5: Authorization, Role-Based Access Control & Policies          │
├────────────────────────────────────────────────────────────────────────┤
│  Phase 6: Public Endpoints & Storefront API Hardening                  │
├────────────────────────────────────────────────────────────────────────┤
│  Phase 7: AI Infrastructure & Prompt Injection Defenses                │
├────────────────────────────────────────────────────────────────────────┤
│  Phase 8: Storage, Uploads & Content Security Hardening                │
├────────────────────────────────────────────────────────────────────────┤
│  Phase 9: Network Security, SSRF Defenses & Outbound Traffic Controls  │
├────────────────────────────────────────────────────────────────────────┤
│  Phase 10: Custom Domain Security & Host Header Hardening              │
├────────────────────────────────────────────────────────────────────────┤
│  Phase 11: Payment & Subscription Flow Integrity                       │
├────────────────────────────────────────────────────────────────────────┤
│  Phase 12: Audit Logging, Security Observability & Event Telemetry     │
├────────────────────────────────────────────────────────────────────────┤
│  Phase 13: HTTP Security Headers, Browser Hardening & Cookie Policies  │
├────────────────────────────────────────────────────────────────────────┤
│  Phase 14: Automated Security Test Automation & CI/CD Pipeline         │
├────────────────────────────────────────────────────────────────────────┤
│  Phase 15: Production Readiness, Disaster Recovery & Launch Gates      │
└────────────────────────────────────────────────────────────────────────┘
```

---

## Phase 1: Critical P0 Patching & Access Control Hotfixes

### Objective
Immediately remediate all identified P0 critical vulnerabilities to close catastrophic exposure vectors prior to further architectural changes.

### Key Tasks
1. **Disable Demo Authentication in Non-Local Environments:**
   - Gate `/demo/login` and `/email/verify/demo` behind strict `app()->environment('local')` checks.
   - Return 404 or 403 when invoked in production, staging, or testing.
2. **Secure Payment Callback Route:**
   - Convert `/payment/callback/{vendor_slug}/{order_id}` from an unverified public GET route into a cryptographically verified handler.
   - For customer redirects, display payment processing status without marking `payment_status = 'paid'`.
   - Require server-to-server webhook callbacks with HMAC/gateway signature verification before marking orders as paid.
3. **Prevent Unverified Subscription Plan Upgrades:**
   - Modify `VendorAdminController::renewSubscription` to prevent immediate activation.
   - Mark initial renewals as `status = 'pending'`, requiring verified webhook confirmation or administrator manual invoice approval.
4. **Remediate Reflected & Stored XSS in Storefront Themes:**
   - Remove query parameter override `$request->get('custom_css')` in `ClientStorefrontController::showMenu`.
   - Update `resources/views/storefront/themes/minimalist-light.blade.php` to sanitize and wrap `custom_css` within `<style>` tags with `strip_tags()`.
   - Sanitize stored `custom_css` in `BrandingController` to strip HTML/script tags and forbid dangerous CSS expressions.
5. **Mitigate Server-Side Request Forgery (SSRF) in AI Importer:**
   - Implement IP validation in `MenuExtractorService::extractStructuredOrTextFromUrl` blocking RFC 1918 private subnets, loopbacks, and cloud metadata IPs (`169.254.169.254`, `metadata.google.internal`).
6. **Eliminate External 2FA Secret Key Leakage:**
   - Replace the external `api.qrserver.com` HTTP call in `ProfileSecurityController` with a local SVG/PNG QR code generator (e.g. `bacon/bacon-qr-code`).

### Affected Files
- [`app/Http/Controllers/AuthController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/AuthController.php)
- [`app/Http/Controllers/RegisterController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/RegisterController.php)
- [`app/Http/Controllers/ClientStorefrontController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/ClientStorefrontController.php)
- [`app/Http/Controllers/VendorAdminController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/VendorAdminController.php)
- [`app/Http/Controllers/ProfileSecurityController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/ProfileSecurityController.php)
- [`app/Services/MenuExtractorService.php`](file:///Users/apple/Projects/qrmenu/app/Services/MenuExtractorService.php)
- [`resources/views/storefront/themes/minimalist-light.blade.php`](file:///Users/apple/Projects/qrmenu/resources/views/storefront/themes/minimalist-light.blade.php)
- [`routes/web.php`](file:///Users/apple/Projects/qrmenu/routes/web.php)

---

## Phase 2: Multi-Tenant Architecture & Data Isolation Hardening

### Objective
Fortify the multi-tenant isolation layer, eliminate cross-tenant data leakage, and ensure persistent runtime safety.

### Key Tasks
1. **TenantContext Lifecycle Hardening:**
   - Ensure `TenantContext::clear()` is invoked at the start of every request in `IdentifyTenant` and registered on Octane request termination hooks.
   - Prevent singleton context bleeding across concurrent or sequential requests.
2. **Comprehensive Model Scoping:**
   - Attach `BelongsToVendor` and `TenantScope` to `SubscriptionPayment`.
   - Add explicit parent-scoped relationships and queries for `OrderItem` and `ProductVariation`.
   - Add `vendor_id` and `BelongsToVendor` to `LocationProductOverride` to prevent cross-tenant override manipulation.
3. **User Model Vendor Scoping:**
   - Implement tenant-aware query scoping on `User` to prevent cross-tenant user enumerations or modifications.
   - Enforce that user creation and assignment strictly validate that `vendor_id` matches the authenticated tenant.
4. **Harden `saveLocationOverride`:**
   - In `MenuBuilderController::saveOverride`, explicitly verify that `Location::where('vendor_id', $vendor->id)->find($location_id)` exists.
5. **Sandbox Session State:**
   - Validate that `session('active_location_id')` strictly belongs to `Auth::user()->vendor_id` on every retrieval.

### Affected Files
- [`app/Services/TenantContext.php`](file:///Users/apple/Projects/qrmenu/app/Services/TenantContext.php)
- [`app/Http/Middleware/IdentifyTenant.php`](file:///Users/apple/Projects/qrmenu/app/Http/Middleware/IdentifyTenant.php)
- [`app/Models/User.php`](file:///Users/apple/Projects/qrmenu/app/Models/User.php)
- [`app/Models/LocationProductOverride.php`](file:///Users/apple/Projects/qrmenu/app/Models/LocationProductOverride.php)
- [`app/Models/SubscriptionPayment.php`](file:///Users/apple/Projects/qrmenu/app/Models/SubscriptionPayment.php)
- [`app/Http/Controllers/MenuBuilderController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/MenuBuilderController.php)
- [`app/Http/Controllers/VendorAdminController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/VendorAdminController.php)

---

## Phase 3: Cryptographic Protection of Secrets & Data at Rest

### Objective
Ensure all integration secrets, 2FA credentials, and customer PII are encrypted at rest and protected from accidental serialization.

### Key Tasks
1. **Encrypt 2FA Secrets:**
   - Cast `users.two_factor_secret` to `encrypted` in `User::casts()`.
   - Hash `users.two_factor_email_code` using `Hash::make()` or encrypt at rest.
2. **Encrypt Integration Credentials in `Vendor`:**
   - Cast `payment_settings`, `ai_settings`, `crm_settings`, `telegram_settings` to `encrypted:array`.
   - Encrypt `wifi_password` using `encrypted` cast.
3. **Encrypt Platform Secrets:**
   - Update `SystemSetting` to encrypt sensitive keys (`telegram_bot_token`, API keys).
4. **Prevent Credential Serialization:**
   - Define `$hidden` array on `Vendor` containing:
     - `payment_settings`, `ai_settings`, `crm_settings`, `telegram_settings`, `wifi_password`, `tax_id`.
   - Ensure API endpoints never expose integration secret keys to the frontend.

### Affected Files
- [`app/Models/User.php`](file:///Users/apple/Projects/qrmenu/app/Models/User.php)
- [`app/Models/Vendor.php`](file:///Users/apple/Projects/qrmenu/app/Models/Vendor.php)
- [`app/Models/SystemSetting.php`](file:///Users/apple/Projects/qrmenu/app/Models/SystemSetting.php)

---

## Phase 4: Authentication, Session Management & 2FA Hardening

### Objective
Protect the platform from brute force, credential stuffing, session hijacking, and unauthorized privilege persistence.

### Key Tasks
1. **Authentication Rate Limiting & Lockout:**
   - Apply rate limiting (`throttle:5,1` with exponential backoff) on `/login`.
   - Implement lockout after 5 consecutive failed login attempts with security logging.
2. **2FA Attempt Throttling & Invalidation:**
   - Apply strict throttling on `/login/2fa` (maximum 5 attempts per challenge).
   - Invalidate the challenge and force re-login upon 5 consecutive failed OTP entries.
   - Remove hardcoded development OTP fallbacks (`123456`) in all non-local environments.
3. **Password Recovery Throttling:**
   - Apply rate limits on `/forgot-password` (3 requests per hour per email/IP).
   - Apply rate limits on `/reset-password`.
4. **Session Security & Invalidation:**
   - Automatically invalidate all other active user sessions upon password change.
   - Enforce session expiration and re-authentication on role changes.

### Affected Files
- [`routes/web.php`](file:///Users/apple/Projects/qrmenu/routes/web.php)
- [`app/Http/Controllers/AuthController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/AuthController.php)
- [`app/Http/Controllers/TwoFactorController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/TwoFactorController.php)
- [`app/Services/TwoFactorAuthService.php`](file:///Users/apple/Projects/qrmenu/app/Services/TwoFactorAuthService.php)
- [`app/Http/Controllers/ProfileSecurityController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/ProfileSecurityController.php)

---

## Phase 5: Authorization, Role-Based Access Control & Policies

### Objective
Replace ad-hoc controller permission checks with formal, declarative Laravel Policies and Gates.

### Key Tasks
1. **Create Policy Classes:**
   - `VendorPolicy`: Manage vendor profile, settings, branding, and billing.
   - `OrderPolicy`: View, update status, and cancel orders.
   - `ProductPolicy` & `CategoryPolicy`: Create, update, toggle, and delete menu items.
   - `LocationPolicy`: Manage branches, tables, and floor plans.
   - `CustomerPolicy`: View CRM directory, edit profiles, and export CSV.
   - `UserPolicy`: Manage team members, invite staff, assign roles.
2. **Register Policies & Gates:**
   - Register policies in `AppServiceProvider`.
3. **Controller Authorization Enforcement:**
   - Replace manual `$loc->vendor_id !== $vendor->id` checks with `$this->authorize(...)`.
4. **Privilege Escalation Prevention:**
   - Prevent vendor owners or managers from assigning the `superadmin` role.
   - Prevent staff members from modifying billing, team members, or system settings.

### Affected Files
- `app/Policies/VendorPolicy.php` (New)
- `app/Policies/OrderPolicy.php` (New)
- `app/Policies/ProductPolicy.php` (New)
- `app/Policies/CategoryPolicy.php` (New)
- `app/Policies/LocationPolicy.php` (New)
- `app/Policies/CustomerPolicy.php` (New)
- `app/Policies/UserPolicy.php` (New)
- [`app/Providers/AppServiceProvider.php`](file:///Users/apple/Projects/qrmenu/app/Providers/AppServiceProvider.php)
- Administrative controllers in `app/Http/Controllers/`

---

## Phase 6: Public Endpoints & Storefront API Hardening

### Objective
Secure the public attack surface against IDOR, enumeration, and unauthorized eavesdropping.

### Key Tasks
1. **AI Waiter IDOR Remediation:**
   - Update all `/api/m/{vendor_slug}/ai-waiter/session/{id}/*` endpoints to authenticate via `session_token` (UUID) passed via header (`X-Session-Token`) or parameter.
   - Prevent sequential integer enumeration of guest sessions.
2. **AI Waiter Rate Limiting:**
   - Apply `throttle:20,1` on AI Waiter chat and recommendation endpoints to prevent LLM quota exhaustion.
3. **WebSocket Channel Authorization:**
   - Secure channel `order.{orderNumber}` by requiring a short-lived signed tracking token generated upon order creation.
   - Prevent arbitrary third-party eavesdropping on order channels.
4. **Public Storefront Input Validation:**
   - Enforce maximum string lengths on all customer notes, names, addresses, and chat queries.

### Affected Files
- [`routes/web.php`](file:///Users/apple/Projects/qrmenu/routes/web.php)
- [`routes/channels.php`](file:///Users/apple/Projects/qrmenu/routes/channels.php)
- [`app/Http/Controllers/AiWaiterController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/AiWaiterController.php)
- [`app/Http/Controllers/ClientStorefrontController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/ClientStorefrontController.php)

---

## Phase 7: AI Infrastructure & Prompt Injection Defenses

### Objective
Harden external LLM integrations against prompt injection, jailbreaking, and denial-of-wallet attacks.

### Key Tasks
1. **Prompt Isolation & Delimitation:**
   - Wrap user chat queries and customer inputs inside strict structural delimiters (e.g. `"""USER_QUERY"""`) within system prompts.
   - Inject rigid system instructions prohibiting system prompt leaking or off-topic conversational manipulation.
2. **Quota & Circuit Breakers:**
   - Implement daily/hourly token consumption counters per vendor in Redis/cache.
   - Temporarily disable AI chat features for a vendor if anomalous usage exceeds plan thresholds.
3. **Response Validation & Output Sanitization:**
   - Validate and sanitize external LLM responses prior to JSON decoding and HTML rendering.
   - Enforce timeouts and graceful fallbacks when providers encounter 429 or 503 errors.

### Affected Files
- [`app/Services/AiGatewayService.php`](file:///Users/apple/Projects/qrmenu/app/Services/AiGatewayService.php)
- [`app/Services/AiWaiterService.php`](file:///Users/apple/Projects/qrmenu/app/Services/AiWaiterService.php)
- [`app/Services/AiMenuService.php`](file:///Users/apple/Projects/qrmenu/app/Services/AiMenuService.php)

---

## Phase 8: Storage, Uploads & Content Security Hardening

### Objective
Secure media uploads against malicious payload execution, stored XSS, and storage exhaustion.

### Key Tasks
1. **SVG Upload Sanitization:**
   - Integrate an SVG sanitization library (e.g. `enshrined/svg-sanitize`) to strip `<script>`, `<foreignObject>`, and event handlers from uploaded SVGs.
   - Alternatively, convert uploaded SVGs to raster formats or restrict SVGs to trusted administrators.
2. **Strict MIME & Magic Byte Inspection:**
   - Inspect true magic bytes using `finfo` rather than trusting client-supplied file extensions.
3. **Filename Randomization & Storage Path Traversal Defense:**
   - Ensure all stored files use UUID or random hash filenames (`Str::random(40)`).
   - Forbid directory traversal tokens (`../`) in any storage references.
4. **Storage Quotas:**
   - Enforce storage quotas per vendor based on their active subscription plan.

### Affected Files
- [`app/Services/MenuManagementService.php`](file:///Users/apple/Projects/qrmenu/app/Services/MenuManagementService.php)
- [`app/Http/Controllers/BrandingController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/BrandingController.php)
- [`app/Http/Requests/StoreProductRequest.php`](file:///Users/apple/Projects/qrmenu/app/Http/Requests/StoreProductRequest.php)
- [`app/Http/Requests/UpdateProductRequest.php`](file:///Users/apple/Projects/qrmenu/app/Http/Requests/UpdateProductRequest.php)

---

## Phase 9: Network Security, SSRF Defenses & Outbound Traffic Controls

### Objective
Establish comprehensive outbound request controls to eliminate Server-Side Request Forgery and malicious external connectivity.

### Key Tasks
1. **Centralized Safe HTTP Client:**
   - Create a dedicated `SafeHttpClient` service wrapping Laravel's `Http` client.
   - Perform DNS resolution before initiating connections and verify that the target IP does not resolve to:
     - `127.0.0.0/8` (Loopback)
     - `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16` (Private RFC 1918)
     - `169.254.0.0/16` (Link-Local & Cloud Metadata)
     - `::1` (IPv6 Loopback), `fc00::/7` (IPv6 Unique Local)
2. **Restrict Follow Redirects:**
   - Prevent HTTP redirect loops or redirects that pivot from public URLs to internal addresses.
3. **Response Payload Limiting:**
   - Enforce a 10MB maximum response size limit on external website extractions.

### Affected Files
- `app/Services/SafeHttpClient.php` (New)
- [`app/Services/MenuExtractorService.php`](file:///Users/apple/Projects/qrmenu/app/Services/MenuExtractorService.php)
- [`app/Http/Controllers/VendorSettingsController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/VendorSettingsController.php)

---

## Phase 10: Custom Domain Security & Host Header Hardening

### Objective
Secure custom domain routing against host header poisoning, cache poisoning, and domain hijacking.

### Key Tasks
1. **Host Header Validation:**
   - Configure `trusted_proxies` in Laravel middleware to ensure `getHost()` accurately reflects the client connection.
2. **Domain Verification Workflow:**
   - Require vendors to add a TXT verification record or CNAME challenge before a custom domain is marked as verified and routed.
   - Prevent vendors from hijacking platform subdomains or unverified domains.
3. **Session Cookie Multi-Domain Isolation:**
   - Set cookie domain policies appropriately to avoid session leakage between tenant domains and the root platform domain.

### Affected Files
- [`app/Http/Middleware/IdentifyTenant.php`](file:///Users/apple/Projects/qrmenu/app/Http/Middleware/IdentifyTenant.php)
- [`app/Http/Controllers/VendorSettingsController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/VendorSettingsController.php)
- `bootstrap/app.php`

---

## Phase 11: Payment & Subscription Flow Integrity

### Objective
Ensure financial transactions, webhooks, and subscription lifecycles are cryptographically tamper-proof and idempotent.

### Key Tasks
1. **Cryptographic Webhook Handlers:**
   - Implement dedicated server-to-server webhook endpoints for:
     - Stripe (`/webhook/stripe` using Stripe Webhook Signature verification)
     - Idram (`/webhook/idram` using secret key MD5/SHA signature verification)
     - Telcell (`/webhook/telcell` using checksum verification)
     - FastShift & ArCa vPOS callbacks
2. **Concurrency & Race Condition Defenses:**
   - Use database row-level locking (`lockForUpdate()`) during payment finalization to prevent race conditions and duplicate order fulfilment.
3. **Idempotent Transaction Ledger:**
   - Record and check transaction IDs in `payment_transactions` to prevent replay attacks.
4. **Subscription Decoupling:**
   - Restrict subscription upgrades so that status is only updated to `'active'` upon receipt of verified payment webhooks.

### Affected Files
- `routes/web.php`
- `app/Http/Controllers/PaymentWebhookController.php` (New)
- [`app/Services/PaymentGatewayService.php`](file:///Users/apple/Projects/qrmenu/app/Services/PaymentGatewayService.php)
- [`app/Http/Controllers/ClientStorefrontController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/ClientStorefrontController.php)
- [`app/Http/Controllers/VendorAdminController.php`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/VendorAdminController.php)

---

## Phase 12: Audit Logging, Security Observability & Event Telemetry

### Objective
Implement comprehensive, immutable security auditing to support forensics, compliance, and threat detection.

### Key Tasks
1. **Create Audit Log Table & Model:**
   - Create `audit_logs` migration (`user_id`, `vendor_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`).
2. **Record Critical Security Events:**
   - Authentication successes and failures.
   - 2FA activations, challenges, and deactivations.
   - User creation, role modifications, and team deletions.
   - Payment gateway credential updates.
   - Customer CRM database CSV exports.
   - Subscription plan alterations.
3. **Structured Alerting:**
   - Dispatch structured log entries on security exceptions for SIEM ingestion.

### Affected Files
- `database/migrations/xxxx_create_audit_logs_table.php` (New)
- `app/Models/AuditLog.php` (New)
- `app/Services/AuditLogService.php` (New)

---

## Phase 13: HTTP Security Headers, Browser Hardening & Cookie Policies

### Objective
Harden client browser environments against clickjacking, MIME-sniffing, XSS, and data exfiltration.

### Key Tasks
1. **Security Headers Middleware:**
   - Register global middleware applying:
     - `Content-Security-Policy`: Default strict policy with frame-ancestors exceptions for `/admin/branding` live preview iframe.
     - `X-Frame-Options: SAMEORIGIN` (or CSP `frame-ancestors 'self'`).
     - `X-Content-Type-Options: nosniff`.
     - `Strict-Transport-Security: max-age=31536000; includeSubDomains`.
     - `Referrer-Policy: strict-origin-when-cross-origin`.
     - `Permissions-Policy: camera=(), microphone=(), geolocation=()`.
2. **Cookie Security:**
   - Enforce `secure => true`, `http_only => true`, and `same_site => 'lax'` in `config/session.php`.

### Affected Files
- `app/Http/Middleware/SecurityHeadersMiddleware.php` (New)
- `bootstrap/app.php`
- `config/session.php`

---

## Phase 14: Automated Security Test Automation & CI/CD Pipeline

### Objective
Establish comprehensive automated security regression testing and CI/CD security quality gates.

### Key Tasks
1. **Develop Regression Test Suites:**
   - Create tests for every P0, P1, and P2 remediation implemented across Phases 1 through 13.
2. **Multi-Tenant Boundary Fuzzing:**
   - Automate cross-tenant penetration tests verifying that no tenant can read or mutate another tenant's orders, dishes, customers, settings, or staff.
3. **Static Analysis & Dependency Audits:**
   - Integrate `composer audit` into automated workflows.
   - Run PHPStan / Larastan at Level 8.
   - Enforce Laravel Pint code styling with zero warnings.

### Affected Files
- `tests/Feature/Security/*` (New test suite)
- `.github/workflows/ci.yml` or local CI scripts

---

## Phase 15: Production Readiness, Disaster Recovery & Launch Gates

### Objective
Validate production environment readiness, secure operational configurations, and establish disaster recovery runbooks.

### Key Tasks
1. **Production Configuration Review:**
   - Verify `APP_DEBUG=false`, secure `APP_KEY`, and correct production URLs in `.env.production`.
   - Ensure SQLite development databases are replaced with secured MySQL/PostgreSQL instances.
2. **Backup & Recovery Verification:**
   - Automate database backups with encryption at rest.
   - Test disaster recovery restoration procedures.
3. **Compliance & Privacy Verification:**
   - Verify GDPR / Armenian PDP customer data erasure workflows ("Right to be Forgotten" in CRM).
4. **Final Security Sign-Off:**
   - Run complete automated test suite (all phases passing).
   - Document security compliance sign-off.

### Deliverables
- `docs/security/PRODUCTION_CHECKLIST.md`
- `docs/security/DISASTER_RECOVERY.md`
