# QR Menu SaaS — Security Baseline & Architectural Audit

**Document Version:** 1.0.0  
**Phase:** Phase 0 (Baseline Assessment)  
**Date:** 2026-09-26  
**Repository:** [elab.menu](https://github.com/agevorgyan/elab.menu)  
**Framework:** Laravel 13.x | PHP 8.5  
**Test Suite Status:** 244 Tests Passing, 1,324 Assertions (Exit Code 0)  

---

## 1. Executive Summary

This Security Baseline document establishes the definitive architectural, multi-tenant, cryptographic, and operational security profile of the QR Menu SaaS platform. The application is a multi-tenant restaurant and hospitality SaaS offering digital QR menus, dine-in / takeaway / delivery ordering, waiter calling, kitchen management, customer CRM, AI menu parsing and translation, AI Waiter conversational assistance, ESC/POS thermal printing, and Armenian / international payment gateway integrations.

During this Phase 0 audit, the codebase, routing tables, database migrations, authentication flows, service providers, models, controllers, queue jobs, and test suites were exhaustively inspected. Multiple critical vulnerabilities were identified that require immediate remediation in Phase 1 before public deployment, including:

1. **P0:** Unauthenticated, unverified payment callback route allowing arbitrary order payment status manipulation.
2. **P0:** Unverified online SaaS subscription renewal endpoint allowing instant, free plan upgrades.
3. **P0:** Unrestricted demo login and demo email verification endpoints enabled unconditionally in all environments.
4. **P0:** Reflected and stored Cross-Site Scripting (XSS) via `custom_css` query parameter and Blade output.
5. **P0:** Server-Side Request Forgery (SSRF) in the AI Menu website URL extractor targeting internal networks or cloud metadata.
6. **P0:** Direct leak of 2FA TOTP secret keys and user email addresses to an external third-party QR code generation service.
7. **P1:** Clear text database storage of all payment gateway keys, SMS API credentials, Telegram bot tokens, AI API keys, and 2FA secrets.
8. **P1:** Insecure Direct Object Reference (IDOR) on public AI Waiter session endpoints allowing guest session enumeration and hijacking.
9. **P1:** Missing rate limiting on sensitive authentication, 2FA verification, password reset, and AI generation endpoints.
10. **P1:** Cross-tenant location override injection in Menu Builder.

---

## 2. Current Architecture & Component Topology

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           External Attack Surface                               │
│  Storefront Guests | Tenant Admins & Staff | SuperAdmin | Third-Party Webhooks  │
└──────────────────────────────────────┬──────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                             HTTP / Web Routing                                  │
│  routes/web.php | routes/channels.php | routes/console.php                      │
│  Middleware Pipeline: SetAppLocale -> IdentifyTenant -> EnsureRole -> Throttle  │
└──────────────────────────────────────┬──────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                      Tenant Context & Global Scoping                            │
│  App\Services\TenantContext (Singleton)                                         │
│  App\Models\Scopes\TenantScope (where vendor_id = ?)                            │
│  App\Models\Traits\BelongsToVendor                                              │
└──────────────────────────────────────┬──────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                             Application Layer                                   │
│  Controllers (Storefront, Auth, 2FA, Admin, SuperAdmin, AI Waiter, Menu)       │
│  Actions & DTOs (CreateOrderAction, CreateOrderDTO, OrderItemDTO)               │
│  Services (AiGateway, PaymentGateway, MenuExtractor, Telegram, ThermalPrinter)  │
└──────────────────────────────────────┬──────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                               Persistence Layer                                 │
│  Database: MySQL / SQLite (Single-Database Multi-Tenant Shared Schema)          │
│  Filesystem: Storage Disk (public/products, public/branding, system)            │
│  Cache & Queue: Redis / Database Driver                                         │
│  Broadcasting: Laravel Reverb WebSockets (vendor.{id}, order.{number})          │
└─────────────────────────────────────────────────────────────────────────────────┘
```

### 2.1 Core Architectural Characteristics
- **Multi-Tenant Model:** Single-database, multi-tenant with shared tables segregated by `vendor_id`.
- **Tenant Context Discovery:** Resolved in [`IdentifyTenant`](file:///Users/apple/Projects/qrmenu/app/Http/Middleware/IdentifyTenant.php) via custom domain host matching against `vendors.custom_domain`, route parameter `vendor_slug`, or `Auth::user()->vendor_id`.
- **Query Scoping:** Global Eloquent scope [`TenantScope`](file:///Users/apple/Projects/qrmenu/app/Models/Scopes/TenantScope.php) injected via trait [`BelongsToVendor`](file:///Users/apple/Projects/qrmenu/app/Models/Traits/BelongsToVendor.php).
- **Execution & Queue Pipeline:** Asynchronous background jobs for analytics visit recording (`RecordAnalyticsVisitJob`) and menu translation (`TranslateMenuJob`).
- **Real-Time Layer:** Laravel Reverb WebSockets broadcasting `OrderCreated`, `OrderStatusUpdated`, and `WaiterCalled` events.

---

## 3. Security Boundaries & Threat Model

| Boundary | Ingress / Entry Points | Egress / Internal Access | Trust Assumption & Enforcement |
|---|---|---|---|
| **Platform Boundary** | `/superadmin/*` | Global tables, all vendors, plan management, system settings | Requires `auth` and `role:superadmin`. High-privilege boundary. |
| **Tenant Boundary** | `/admin/*` | Scoped to authenticated user's `vendor_id` | Requires `auth`, `role:vendor_owner,manager,staff`, and active subscription. Must strictly forbid cross-tenant reads/writes. |
| **Branch Boundary** | `/admin/orders`, `/admin/floor-plan` | Filtered by `active_location_id` | Intra-tenant boundary separating multi-location restaurant branches. |
| **Storefront Boundary** | `/m/{vendor_slug}`, `/api/m/{vendor_slug}/*` | Public customer menu, order placement, waiter calling, AI waiter chat | Zero-trust public boundary. Must enforce rate limiting, strict validation, anti-tampering, and tenant scoping. |
| **External Integrations** | Payment callbacks, AI APIs, Telegram API, SMS gateways, QR APIs | Internal databases and background workers | Webhooks and callbacks must be cryptographically authenticated. Outbound requests must guard against SSRF. |

---

## 4. Multi-Tenant Architecture & Boundary Evaluation

### 4.1 TenantContext Service ([`app/Services/TenantContext.php`](file:///Users/apple/Projects/qrmenu/app/Services/TenantContext.php))
- **Lifecycle:** Bound as a **singleton** in [`AppServiceProvider`](file:///Users/apple/Projects/qrmenu/app/Providers/AppServiceProvider.php).
- **State Management:** Holds `$tenantId`, `$tenant`, `$customDomainHost`, and `$bypassed` state.
- **Risks Identified:**
  1. In long-running worker environments (Laravel Octane, RoadRunner, FrankenPHP, Horizon), singleton state is retained across requests unless explicitly cleared.
  2. If an unauthenticated or platform-level request is executed on an Octane worker previously processing tenant traffic, it may inherit the previous tenant context if [`TenantContext::clear()`](file:///Users/apple/Projects/qrmenu/app/Services/TenantContext.php#L160-L166) is omitted.
  3. `runInTenantContext(int $vendorId, callable $callback)` restores previous tenant state in a `finally` block, which is properly defensive.

### 4.2 IdentifyTenant Middleware ([`app/Http/Middleware/IdentifyTenant.php`](file:///Users/apple/Projects/qrmenu/app/Http/Middleware/IdentifyTenant.php))
- **Execution Order:** Appended to the `web` middleware group; prioritized after `StartSession` and `ShareErrorsFromSession` and before `Authenticate`.
- **Custom Domain Resolution:** Matches incoming `Host` header against `vendors.custom_domain` or `www.{cleanHost}`.
- **Flaws Identified:**
  1. Does not call `$tenantContext->clear()` at the beginning of `handle()`.
  2. When a custom domain matches, it logs out non-matching authenticated users, but for standard platform requests without a `vendor_slug` route parameter and without active authentication, the context is left uninitialized rather than explicitly cleared.
  3. Host header is accepted directly via `$request->getHost()` without validation against trusted proxies.

### 4.3 TenantScope ([`app/Models/Scopes/TenantScope.php`](file:///Users/apple/Projects/qrmenu/app/Models/Scopes/TenantScope.php))
- **Behavior:** Queries `$tenantContext->getTenantId()`. If non-null, appends `where vendor_id = ?`.
- **Bypass Mechanism:** `$tenantContext->isBypassed()` disables scoping. SuperAdmin users return `null` for tenant ID, effectively viewing global records.

### 4.4 BelongsToVendor Trait ([`app/Models/Traits/BelongsToVendor.php`](file:///Users/apple/Projects/qrmenu/app/Models/Traits/BelongsToVendor.php))
- **Creation Listener:** Auto-assigns `vendor_id` if empty using `$tenantContext->getTenantId()`.
- **Flaws Identified:**
  1. `if (empty($model->vendor_id))` will NOT override a maliciously supplied `vendor_id` if mass-assignment or an un-sanitized array is passed.
  2. There is no `updating` hook. If an Eloquent model is updated with a payload containing `vendor_id`, the tenant association can theoretically be altered.

### 4.5 Model Scoping Matrix

| Model | Trait / Global Scope | Foreign Key | Tenant Scoped | Assessment & Risk |
|---|---|---|---|---|
| [`Vendor`](file:///Users/apple/Projects/qrmenu/app/Models/Vendor.php) | None (Tenant Root) | `id` | Root | Tenant root entity. Missing `$hidden` allows credential exposure. |
| [`Location`](file:///Users/apple/Projects/qrmenu/app/Models/Location.php) | `BelongsToVendor` | `vendor_id` | Yes | Scoped. |
| [`Category`](file:///Users/apple/Projects/qrmenu/app/Models/Category.php) | `BelongsToVendor`, `SoftDeletes` | `vendor_id` | Yes | Scoped. |
| [`Product`](file:///Users/apple/Projects/qrmenu/app/Models/Product.php) | `BelongsToVendor`, `SoftDeletes` | `vendor_id` | Yes | Scoped. |
| [`Order`](file:///Users/apple/Projects/qrmenu/app/Models/Order.php) | `BelongsToVendor`, `SoftDeletes` | `vendor_id` | Yes | Scoped. |
| [`Customer`](file:///Users/apple/Projects/qrmenu/app/Models/Customer.php) | `BelongsToVendor` | `vendor_id` | Yes | Scoped. |
| [`AiWaiterSession`](file:///Users/apple/Projects/qrmenu/app/Models/AiWaiterSession.php) | `BelongsToVendor` | `vendor_id` | Yes | Model is scoped, but public controller endpoints lack session token authorization. |
| [`WaiterCall`](file:///Users/apple/Projects/qrmenu/app/Models/WaiterCall.php) | `BelongsToVendor` | `vendor_id` | Yes | Scoped. |
| [`AnalyticsLog`](file:///Users/apple/Projects/qrmenu/app/Models/AnalyticsLog.php) | `BelongsToVendor` | `vendor_id` | Yes | Scoped. |
| [`PushSubscription`](file:///Users/apple/Projects/qrmenu/app/Models/PushSubscription.php) | `BelongsToVendor` | `vendor_id` | Yes | Scoped. |
| [`User`](file:///Users/apple/Projects/qrmenu/app/Models/User.php) | **None** | `vendor_id` | **NO** | **High Risk.** `User` lacks `BelongsToVendor` or `TenantScope`. Direct queries must manually filter `vendor_id`. |
| [`OrderItem`](file:///Users/apple/Projects/qrmenu/app/Models/OrderItem.php) | **None** | `order_id` | Dependent | Relies on parent `Order`. Direct queries bypass tenant filtering. |
| [`ProductVariation`](file:///Users/apple/Projects/qrmenu/app/Models/ProductVariation.php) | **None** | `product_id` | Dependent | Relies on parent `Product`. No direct `vendor_id`. |
| [`LocationProductOverride`](file:///Users/apple/Projects/qrmenu/app/Models/LocationProductOverride.php) | **None** | `location_id`, `product_id` | **NO** | **High Risk.** No direct `vendor_id`. Cross-tenant location injection possible via `MenuBuilderController::saveOverride`. |
| [`SubscriptionPayment`](file:///Users/apple/Projects/qrmenu/app/Models/SubscriptionPayment.php) | **None** | `vendor_id` | **NO** | Lacks `BelongsToVendor`. Queries must manually constrain `vendor_id`. |
| [`Allergen`](file:///Users/apple/Projects/qrmenu/app/Models/Allergen.php) | None (System Shared) | N/A | Global | Global platform lookup table. |
| [`MenuTemplate`](file:///Users/apple/Projects/qrmenu/app/Models/MenuTemplate.php) | None (System Shared) | N/A | Global | Global platform templates. |
| [`SubscriptionPlan`](file:///Users/apple/Projects/qrmenu/app/Models/SubscriptionPlan.php) | None (System Shared) | N/A | Global | Global SaaS plans. |
| [`SystemSetting`](file:///Users/apple/Projects/qrmenu/app/Models/SystemSetting.php) | None (System Shared) | N/A | Global | Global platform key-value settings. |

---

## 5. Endpoints & Route Access Matrix

### 5.1 Public Unauthenticated Endpoints

| Method | URI | Controller Action | Rate Limit | Risk Analysis |
|---|---|---|---|---|
| `GET` | `/` | `LandingController@index` | None | Public landing page. Low risk. |
| `GET` | `/privacy-policy` | Closure | None | Static legal view. Low risk. |
| `GET` | `/terms-of-service` | Closure | None | Static legal view. Low risk. |
| `GET` | `/lang/{locale}` | Closure | None | Locale switch in session. Protected by `in_array(['hy','en','ru'])`. |
| `GET` | `/manifest.json`, `/sw.js` | `ClientStorefrontController` | None | PWA assets based on tenant context. |
| `GET` | `/m/{vendor_slug}/manifest.json` | `ClientStorefrontController@manifest` | None | Dynamic PWA manifest. |
| `GET` | `/m/{vendor_slug}/sw.js` | `ClientStorefrontController@serviceWorker` | None | Dynamic ServiceWorker JS. |
| `GET` | `/m/{vendor_slug}/{location_slug?}` | `ClientStorefrontController@showMenu` | None | **Critical.** Accepts `?custom_css=` parameter and renders raw/unfiltered CSS in themes. Reflected XSS. |
| `GET` | `/{location_slug}` | Closure -> `showMenu` | None | Custom domain root branch menu router. |
| `POST` | `/api/m/{vendor_slug}/order` | `ClientStorefrontController@submitOrder` | `15,1` | Protected by 15 req/min throttle and server-side price recalculation. |
| `POST` | `/api/m/{vendor_slug}/call-waiter` | `ClientStorefrontController@callWaiter` | `15,1` | Protected by 15 req/min throttle and session table matching. |
| `GET` | `/api/m/{vendor_slug}/order/{order_number}/status` | `ClientStorefrontController@orderStatus` | `60,1` | Status tracker. Protected by 60 req/min throttle. |
| `GET` | `/payment/callback/{vendor_slug}/{order_id}` | `ClientStorefrontController@paymentCallback` | **None** | **CRITICAL (P0).** Unauthenticated public GET endpoint instantly updates order to `payment_status = 'paid'` without HMAC or gateway verification. |
| `GET` | `/api/m/{vendor_slug}/ai-waiter/config` | `AiWaiterController@config` | **None** | Public AI Waiter settings. |
| `GET` | `/api/m/{vendor_slug}/ai-waiter/questions` | `AiWaiterController@questions` | **None** | Public questionnaire library. |
| `POST` | `/api/m/{vendor_slug}/ai-waiter/session` | `AiWaiterController@startSession` | **None** | Creates session, returns UUID and integer ID. |
| `POST` | `/api/m/{vendor_slug}/ai-waiter/session/{id}/language` | `AiWaiterController@setLanguage` | **None** | **IDOR.** Uses raw integer `{id}` without validating `session_token`. |
| `POST` | `/api/m/{vendor_slug}/ai-waiter/session/{id}/answer` | `AiWaiterController@submitAnswer` | **None** | **IDOR.** Updates preferences by integer `{id}`. |
| `POST` | `/api/m/{vendor_slug}/ai-waiter/session/{id}/next-question` | `AiWaiterController@nextQuestion` | **None** | **IDOR.** Retrieves questions for integer `{id}`. |
| `POST` | `/api/m/{vendor_slug}/ai-waiter/session/{id}/recommendations` | `AiWaiterController@recommendations` | **None** | **IDOR + Quota Abuse.** Triggers dish recommendation by integer `{id}`. |
| `POST` | `/api/m/{vendor_slug}/ai-waiter/session/{id}/chat` | `AiWaiterController@chat` | **None** | **IDOR + LLM DOS.** Executes unthrottled LLM completions via integer `{id}`. |
| `POST` | `/api/m/{vendor_slug}/ai-waiter/session/{id}/add-to-cart` | `AiWaiterController@addToCart` | **None** | **IDOR.** Modifies session cart items. |
| `POST` | `/api/m/{vendor_slug}/ai-waiter/session/{id}/complete` | `AiWaiterController@complete` | **None** | **IDOR.** Marks session complete. |
| `POST` | `/api/m/{vendor_slug}/ai-waiter/recommend` | `AiWaiterController@recommend` | **None** | Legacy unthrottled recommendation endpoint. |
| `GET` | `/api/m/{vendor_slug}/ai-waiter/pairings/{product_id}` | `AiWaiterController@pairings` | **None** | Legacy pairings endpoint. |

### 5.2 Public Authentication & Account Recovery Endpoints

| Method | URI | Controller Action | Rate Limit | Risk Analysis |
|---|---|---|---|---|
| `GET` | `/login` | `AuthController@showLogin` | None | Displays login form with Captcha challenge. |
| `POST` | `/login` | `AuthController@login` | **None** | **High Risk.** Validates credentials and captcha, but lacks rate limiting against credential stuffing. |
| `POST` | `/logout` | `AuthController@logout` | None | Invalidates session and regenerates token. Safe. |
| `GET` | `/login/2fa` | `TwoFactorController@showChallenge` | None | Shows 2FA challenge screen. |
| `POST` | `/login/2fa` | `TwoFactorController@verify` | **None** | **CRITICAL (P1).** No rate limit or failed attempt counter on 6-digit OTP verification. |
| `POST` | `/login/2fa/resend` | `TwoFactorController@resendEmailCode` | **None** | **High Risk.** No rate limit on dispatching email verification codes. |
| `GET` | `/forgot-password` | `ForgotPasswordController@showLinkRequestForm` | None | Password reset request form. |
| `POST` | `/forgot-password` | `ForgotPasswordController@sendResetLinkEmail` | **None** | **High Risk.** Captcha required, but missing IP/email throttle against email flooding. |
| `GET` | `/reset-password/{token}` | `ResetPasswordController@showResetForm` | None | Password reset form with token check. |
| `POST` | `/reset-password` | `ResetPasswordController@reset` | **None** | Token hashed in DB with 60-min TTL. Missing IP rate limiting. |
| `GET` | `/captcha/refresh` | `AuthController@refreshCaptcha` | None | Generates new math Captcha challenge in session. |
| `GET` | `/demo/login` | `AuthController@showDemoLogin` | None | **CRITICAL (P0).** Renders demo login screen. Active in all environments. |
| `POST` | `/demo/login` | `AuthController@demoLogin` | None | **CRITICAL (P0).** Logs in as `owner@bistro.am` or first `vendor_owner` without password or 2FA! |
| `GET` | `/register` | `RegisterController@showRegistrationForm` | None | Self-registration form for new vendors. |
| `POST` | `/register` | `RegisterController@register` | None | Creates vendor, location, owner user, initiates trial. Missing throttle. |
| `GET` | `/email/verify` | `RegisterController@showVerificationNotice` | None | Verification notice. |
| `GET` | `/email/verify/{id}/{hash}` | `RegisterController@verifyEmail` | None | Verifies email via hash check. |
| `POST` | `/email/verify/demo` | `RegisterController@directDemoVerify` | None | **CRITICAL (P0).** Instantly marks user and vendor email verified without token. Active in all environments. |

### 5.3 Authenticated User Profile & Security Endpoints

| Method | URI | Controller Action | Middleware | Risk Analysis |
|---|---|---|---|---|
| `POST` | `/security/2fa/send-code` | `ProfileSecurityController@sendTwoFactorCode` | `auth` | Dispatches 6-digit verification code to email. Needs throttling. |
| `GET` | `/security/2fa/secret` | `ProfileSecurityController@getSecretKey` | `auth` | **CRITICAL (P0).** Generates Google Authenticator secret and QR code URL via `api.qrserver.com`. |
| `POST` | `/security/2fa/enable` | `ProfileSecurityController@enableTwoFactor` | `auth` | Confirms code and activates 2FA. In testing/local allows `123456`. |
| `POST` | `/security/2fa/disable` | `ProfileSecurityController@disableTwoFactor` | `auth` | Requires current password. Safe. |
| `POST` | `/security/password` | `ProfileSecurityController@updatePassword` | `auth` | Requires current password + 2FA code (if enabled). Safe. |
| `POST` | `/security/email` | `ProfileSecurityController@updateEmail` | `auth` | Requires current password + 2FA code (if enabled). Safe. |

### 5.4 Authenticated SuperAdmin Panel (`/superadmin/*`)
- **Middleware:** `['auth', 'role:superadmin']`
- **Scope:** Global platform governance.
- **Routes:** Dashboard, vendor management, vendor location/table management, vendor user assignment, subscription plans CRUD, global subscription overview, system settings, Telegram test connection, platform branding.
- **Risk Analysis:** Properly protected by `role:superadmin`. High security perimeter.

### 5.5 Authenticated Vendor Admin Panel (`/admin/*`)
- **Middleware:** `['auth', 'role:vendor_owner,manager,staff', EnsureSubscriptionIsActive::class]`
- **Scope:** Scoped to the authenticated user's vendor.
- **Feature Middlewares:**
  - `EnsurePlanHasFeature:locations` -> `/admin/locations`
  - `EnsurePlanHasFeature:team` -> `/admin/team`
  - `EnsurePlanHasFeature:orders` -> `/admin/orders`, `/admin/orders/feed`, `/admin/floor-plan`
  - `EnsurePlanHasFeature:customers` -> `/admin/customers`, `/admin/customers/export`
- **Key Routes & Vulnerabilities:**
  - `POST /admin/subscription/renew`: **CRITICAL (P0).** Immediately upgrades vendor plan and extends expiration by up to 12 months with no payment validation.
  - `POST /admin/menu/products/{product}/override`: **High Risk (P1).** Accepts arbitrary `location_id` without verifying it belongs to the vendor.
  - `POST /admin/branding`: **High Risk (P0).** Saves `custom_css` without sanitization.
  - `POST /admin/ai/import/process`: **CRITICAL (P0).** Fetches user-provided `website_url` without SSRF protection.
  - `GET /admin/customers/export`: Exports customer PII to CSV. Requires audit logging and role restrictions.

---

## 6. Sensitive Data Inventory & At-Rest Cryptography Audit

| Sensitive Asset | Storage Location | Current Storage Format | Encryption at Rest? | Leakage Vector |
|---|---|---|---|---|
| User Passwords | `users.password` | Bcrypt / Argon2 Hashed | Yes (One-way hash) | None |
| 2FA TOTP Secret Keys | `users.two_factor_secret` | Plain text 16-char Base32 string | **NO** | DB leak, memory dumps, external API calls to `api.qrserver.com` |
| 2FA Email OTP Codes | `users.two_factor_email_code` | Plain text 6-digit string | **NO** | DB leak, shoulder surfing |
| Stripe Secret Keys | `vendors.payment_settings['gateways']['stripe']['secret_key']` | Plain text string | **NO** | DB leak, serialization into JSON |
| Stripe Publishable Keys | `vendors.payment_settings['gateways']['stripe']['publishable_key']` | Plain text string | **NO** | DB leak, serialization into JSON |
| Idram Secret Keys | `vendors.payment_settings['gateways']['idram']['secret_key']` | Plain text string | **NO** | DB leak, serialization into JSON |
| Telcell Secret Keys | `vendors.payment_settings['gateways']['telcell']['key']` | Plain text string | **NO** | DB leak, serialization into JSON |
| FastShift API Keys | `vendors.payment_settings['gateways']['fastshift']['api_key']` | Plain text string | **NO** | DB leak, serialization into JSON |
| ArCa Merchant/Terminal IDs | `vendors.payment_settings['gateways']['arca']` | Plain text strings | **NO** | DB leak, serialization into JSON |
| AI Provider API Keys | `vendors.ai_settings['api_key']` | Plain text string | **NO** | DB leak, serialization into JSON |
| Telegram Bot Tokens | `vendors.telegram_settings['bot_token']` | Plain text string | **NO** | DB leak, serialization into JSON |
| Platform Telegram Bot Token | `system_settings` table (`key='telegram_bot_token'`) | Plain text string | **NO** | DB leak |
| SMS Provider API Keys | `vendors.crm_settings['sms_api_key']` | Plain text string | **NO** | DB leak, serialization into JSON |
| Restaurant Wi-Fi Passwords | `vendors.wifi_password` | Plain text string | **NO** | DB leak, serialization into JSON |
| Customer PII | `customers` table (`name`, `phone`, `email`, `address`, `birthdate`) | Plain text columns | **NO** | DB leak, unauthenticated CSV export |
| Order Customer Details | `orders` table (`customer_name`, `customer_phone`, `customer_email`, `delivery_address`) | Plain text columns | **NO** | DB leak |

---

## 7. Known Vulnerabilities & Risk Classification

### 7.1 P0 (Critical Vulnerabilities — Immediate Action Required)

#### [P0-1] Unauthenticated Payment Callback Forgery / Free Order Completion
- **Location:** [`ClientStorefrontController.php:274-286`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/ClientStorefrontController.php#L274-L286), [`routes/web.php:61-62`](file:///Users/apple/Projects/qrmenu/routes/web.php#L61-L62)
- **Vulnerability:** Route `/payment/callback/{vendor_slug}/{order_id}` is a public GET endpoint. It takes `$order_id`, extracts an unvalidated token or transaction ID from query parameters, and immediately marks `$order->update(['payment_status' => 'paid'])` without any signature verification, secret hash verification, gateway status handshake, or IP whitelist.
- **Impact:** Any customer can place an order of arbitrary amount and immediately mark it as fully paid by navigating to `/payment/callback/{vendor_slug}/{order_id}`, triggering automatic kitchen dispatch and thermal ticket printing without paying.

#### [P0-2] Instant Free SaaS Subscription Upgrade & Activation Bypass
- **Location:** [`VendorAdminController.php:155-189`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/VendorAdminController.php#L155-L189), [`PaymentGatewayService.php:169-220`](file:///Users/apple/Projects/qrmenu/app/Services/PaymentGatewayService.php#L169-L220)
- **Vulnerability:** Endpoint `POST /admin/subscription/renew` directly calls `PaymentGatewayService::processSubscriptionRenewal()`, which immediately creates a `SubscriptionPayment` with `'status' => 'completed'`, updates `subscription_status` to `'active'`, and extends `subscription_expires_at` by up to 12 months with zero payment transaction execution.
- **Impact:** Any authenticated vendor user can obtain a lifetime Enterprise subscription for free.

#### [P0-3] Production Demo Login & Instant Demo Email Verification
- **Location:** [`AuthController.php:43-78`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/AuthController.php#L43-L78), [`RegisterController.php:146-157`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/RegisterController.php#L146-L157), [`routes/web.php:119-120,127`](file:///Users/apple/Projects/qrmenu/routes/web.php#L119-L120)
- **Vulnerability:** Routes `/demo/login` and `/email/verify/demo` are active in all environments with no `app()->environment('local')` condition. Calling `POST /demo/login` immediately logs the caller in as `owner@bistro.am` or the first `vendor_owner` in the database without a password or 2FA challenge. Calling `POST /email/verify/demo` instantly marks unverified accounts as verified.
- **Impact:** Complete authentication bypass and vendor account takeover in production.

#### [P0-4] Reflected & Stored Cross-Site Scripting (XSS) via `custom_css`
- **Location:** [`ClientStorefrontController.php:112-114`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/ClientStorefrontController.php#L112-L114), [`resources/views/storefront/themes/minimalist-light.blade.php:428`](file:///Users/apple/Projects/qrmenu/resources/views/storefront/themes/minimalist-light.blade.php#L428)
- **Vulnerability:** `ClientStorefrontController::showMenu` assigns `$request->get('custom_css')` directly to `$vendor->custom_css` if present in query parameters. In `minimalist-light.blade.php`, line 428 renders `{!! $vendor->custom_css ?? '' !!}` without `<style>` tags or sanitization.
- **Impact:** An attacker can craft a link `/m/{vendor_slug}?custom_css=<script>alert(document.cookie)</script>` leading to instant arbitrary script execution in victim browsers. Furthermore, any stored CSS in Branding settings is rendered unsanitized in this theme.

#### [P0-5] Server-Side Request Forgery (SSRF) in AI Menu Importer
- **Location:** [`MenuExtractorService.php:825-856`](file:///Users/apple/Projects/qrmenu/app/Services/MenuExtractorService.php#L825-L856), [`AiMenuController.php:43-49`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/AiMenuController.php#L43-L49)
- **Vulnerability:** `MenuExtractorService::extractStructuredOrTextFromUrl($url)` performs `Http::timeout(15)->get($currentUrl)` on user-supplied URLs without restricting private IP subnets (`127.0.0.1`, `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`), link-local addresses, or cloud metadata endpoints (`169.254.169.254`, `metadata.google.internal`).
- **Impact:** Attackers can scan internal networks, reach local redis/database services, or exfiltrate cloud instance credentials and IAM tokens.

#### [P0-6] Direct Leak of 2FA TOTP Secret Keys to Third-Party Web Service
- **Location:** [`ProfileSecurityController.php:25,66`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/ProfileSecurityController.php#L25)
- **Vulnerability:** TOTP QR code images are generated by constructing a URL to an external third-party service:  
  `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=otpauth://totp/...secret=XYZ...`
- **Impact:** Every 2FA secret key and associated email address are transmitted over HTTP GET to an unvetted third-party service and recorded in their external access logs.

---

### 7.2 P1 (High Severity Issues)

#### [P1-1] Clear Text Storage of Sensitive Secrets in Database
- **Location:** [`Vendor.php`](file:///Users/apple/Projects/qrmenu/app/Models/Vendor.php), [`User.php`](file:///Users/apple/Projects/qrmenu/app/Models/User.php), [`SystemSetting.php`](file:///Users/apple/Projects/qrmenu/app/Models/SystemSetting.php)
- **Vulnerability:** `two_factor_secret`, Stripe/Idram/Telcell/FastShift gateway credentials, SMS API keys, AI provider keys, Telegram tokens, and Wi-Fi passwords are saved unencrypted in MySQL. The `Vendor` model also lacks a `$hidden` property, causing all keys to leak if the model is serialized to JSON.
- **Impact:** Database compromises, read-only SQL injection, or unintended JSON serialization immediately exposes production financial and integration secrets.

#### [P1-2] Insecure Direct Object Reference (IDOR) on AI Waiter Sessions
- **Location:** [`AiWaiterController.php:99-340`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/AiWaiterController.php#L99-L340)
- **Vulnerability:** Public session management endpoints accept a plain integer `{id}` (e.g. `/api/m/{vendor_slug}/ai-waiter/session/{id}/chat`) and only verify that the session belongs to the vendor. They do NOT require or validate the generated `session_token` UUID.
- **Impact:** Anyone can iterate sequential session IDs, view other guests' preferences and recommendations, inject chat messages, or tamper with active carts.

#### [P1-3] Missing Rate Limiting on Authentication & AI Endpoints
- **Location:** `routes/web.php` lines 65-80, 101-114
- **Vulnerability:** No rate limiting or attempt lockouts exist on `/login`, `/login/2fa`, `/forgot-password`, `/reset-password`, or `/api/m/{vendor_slug}/ai-waiter/*`.
- **Impact:** Susceptible to 6-digit 2FA brute forcing, credential stuffing, password reset mailbox flooding, and denial-of-wallet / quota exhaustion on external AI APIs.

#### [P1-4] Cross-Tenant Location Override Injection in Menu Builder
- **Location:** [`MenuBuilderController.php:113-127`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/MenuBuilderController.php#L113-L127)
- **Vulnerability:** `saveOverride` validates that `location_id` exists in `locations`, but does NOT verify that the location belongs to the product's vendor. It writes directly to `LocationProductOverride::updateOrCreate()`, which lacks tenant scoping.
- **Impact:** Vendor A can inject pricing and availability overrides into branches belonging to Vendor B.

#### [P1-5] SVG File Upload Allowed Without Sanitization
- **Location:** [`StoreProductRequest.php:60`](file:///Users/apple/Projects/qrmenu/app/Http/Requests/StoreProductRequest.php#L60), [`BrandingController.php:27`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/BrandingController.php#L27)
- **Vulnerability:** Product images, store logos, and cover banners accept `.svg` files with no XML/script sanitization.
- **Impact:** Stored Cross-Site Scripting (XSS) via embedded `<script>` or event handlers in SVG files.

#### [P1-6] Unrestricted Public WebSocket Order Channel
- **Location:** [`routes/channels.php:20-22`](file:///Users/apple/Projects/qrmenu/routes/channels.php#L20-L22)
- **Vulnerability:** Channel `order.{orderNumber}` returns `true` unconditionally with no authorization check.
- **Impact:** Anyone who knows or guesses an order number can subscribe to the WebSocket channel and receive real-time order updates.

---

### 7.3 P2 (Medium Severity Issues)

#### [P2-1] Incomplete Model Scoping Across Secondary Entities
- **Location:** `User`, `OrderItem`, `ProductVariation`, `LocationProductOverride`, `SubscriptionPayment`
- **Vulnerability:** These models do not use `BelongsToVendor` or `TenantScope`. Developers must remember to add manual constraints on every query.

#### [P2-2] Missing Hardened HTTP Security Headers
- **Location:** Global HTTP middleware stack
- **Vulnerability:** Missing Content-Security-Policy (CSP), Strict-Transport-Security (HSTS), X-Content-Type-Options, Referrer-Policy, and Permissions-Policy.

#### [P2-3] Session Location Hijacking / Cross-Vendor Session Pollution
- **Location:** [`VendorAdminController.php:28`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/VendorAdminController.php#L28)
- **Vulnerability:** `session(['active_location_id' => $activeLocationId])` accepts unvalidated query input and stores it without verifying location ownership.

#### [P2-4] Absence of Structured Security Audit Logging
- **Location:** System-wide
- **Vulnerability:** No dedicated audit trail exists for privilege escalation, payment settings modifications, password changes, or CSV data exports.

---

## 8. Existing Security Protections & Controls (Verified in Code)

1. **Eloquent Tenant Scoping:** Trait [`BelongsToVendor`](file:///Users/apple/Projects/qrmenu/app/Models/Traits/BelongsToVendor.php) successfully injects [`TenantScope`](file:///Users/apple/Projects/qrmenu/app/Models/Scopes/TenantScope.php) on 9 key entities (`Category`, `Product`, `Order`, `Customer`, `AiWaiterSession`, `Location`, `AnalyticsLog`, `WaiterCall`, `PushSubscription`).
2. **Form Request Authorization:** [`StoreCategoryRequest`](file:///Users/apple/Projects/qrmenu/app/Http/Requests/StoreCategoryRequest.php), [`UpdateCategoryRequest`](file:///Users/apple/Projects/qrmenu/app/Http/Requests/UpdateCategoryRequest.php), [`StoreProductRequest`](file:///Users/apple/Projects/qrmenu/app/Http/Requests/StoreProductRequest.php), and [`UpdateProductRequest`](file:///Users/apple/Projects/qrmenu/app/Http/Requests/UpdateProductRequest.php) enforce `vendor_id` ownership checks before validation rules execute.
3. **Cross-Tenant Product Injection Defense:** [`ClientStorefrontController::submitOrder`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/ClientStorefrontController.php#L205-L216) validates that all submitted item `product_id`s belong to the target vendor.
4. **Server-Side Price Calculation:** [`CreateOrderAction`](file:///Users/apple/Projects/qrmenu/app/Actions/CreateOrderAction.php#L350-L414) completely ignores user-supplied prices in request payloads and computes item subtotals and discounts strictly from database records.
5. **Dine-In Table Hijacking Defense:** [`ClientStorefrontController::callWaiter`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/ClientStorefrontController.php#L414-L429) verifies that the requested table number matches the customer's scanned table session.
6. **Public Storefront Rate Limiting:** Order submission and waiter calling routes are throttled to 15 requests per minute; order status checks are throttled to 60 requests per minute.
7. **Role Gating Middleware:** [`EnsureRole`](file:///Users/apple/Projects/qrmenu/app/Http/Middleware/EnsureRole.php) guards administrative panel routes against unauthorized roles.
8. **Mathematical SVG Captcha:** [`CaptchaService`](file:///Users/apple/Projects/qrmenu/app/Services/CaptchaService.php) protects `/login`, `/register`, and `/forgot-password` against basic automated bots.
9. **Private Reverb Broadcasting Channel:** [`routes/channels.php:13-15`](file:///Users/apple/Projects/qrmenu/routes/channels.php#L13-L15) restricts channel `vendor.{vendorId}` to users matching `(int) $user->vendor_id === (int) $vendorId`.
10. **Database Transactions:** Order creation and customer profile synchronization execute inside `DB::transaction()` to ensure atomicity.

---

## 9. Existing Security Tests Evaluation

The repository currently maintains **244 passing tests with 1,324 assertions**. Key security test files include:

1. [`tests/Feature/IdorProtectionTest.php`](file:///Users/apple/Projects/qrmenu/tests/Feature/IdorProtectionTest.php): Tests cross-tenant category updates, dish updates, category reassignment, order status updates, and team member location assignments.
2. [`tests/Feature/CrossTenantOrderInjectionTest.php`](file:///Users/apple/Projects/qrmenu/tests/Feature/CrossTenantOrderInjectionTest.php): Tests that injecting another vendor's dish or location into an order submission is rejected with HTTP 422.
3. [`tests/Feature/PricingBypassProtectionTest.php`](file:///Users/apple/Projects/qrmenu/tests/Feature/PricingBypassProtectionTest.php): Tests that client-submitted prices are overridden by canonical database prices.
4. [`tests/Feature/PublicEndpointRateLimitingTest.php`](file:///Users/apple/Projects/qrmenu/tests/Feature/PublicEndpointRateLimitingTest.php): Tests that `/api/m/{vendor}/order` and `/api/m/{vendor}/call-waiter` enforce HTTP 429 after 15 requests per minute.
5. [`tests/Feature/SecurityAndAuthFeaturesTest.php`](file:///Users/apple/Projects/qrmenu/tests/Feature/SecurityAndAuthFeaturesTest.php): Tests Captcha refresh, Captcha validation, password reset token expiration, 2FA challenge redirection, email OTP verification, TOTP verification, and 2FA-protected password/email modifications.
6. [`tests/Feature/CustomDomainTest.php`](file:///Users/apple/Projects/qrmenu/tests/Feature/CustomDomainTest.php): Tests custom domain resolution, SuperAdmin panel lockout on tenant custom domains, and foreign tenant authentication eviction.
7. [`tests/Unit/TenantScopeTest.php`](file:///Users/apple/Projects/qrmenu/tests/Unit/TenantScopeTest.php): Unit tests validating SQL query scoping under active, bypassed, and switched tenant contexts.

---

## 10. Missing Tests Inventory

To ensure comprehensive regression coverage during the hardening roadmap, the following test suites must be created:

1. **Payment Callback Security Tests:** Verify that unauthenticated GET requests to `/payment/callback/{slug}/{id}` fail, and verify that invalid HMAC signatures or tampered transaction IDs are rejected.
2. **Subscription Activation Security Tests:** Verify that `/admin/subscription/renew` cannot activate a plan without verified payment gateway webhook callbacks.
3. **Environment Isolation Tests:** Verify that `/demo/login` and `/email/verify/demo` return 404 or 403 when `APP_ENV=production`.
4. **XSS Sanitization Tests:** Test reflected XSS injection via `?custom_css=` query parameter across all storefront themes, and test stored XSS injection via branding `custom_css`.
5. **SSRF Validation Tests:** Test that `MenuExtractorService` rejects requests targeting `127.0.0.1`, `10.0.0.1`, `169.254.169.254`, and cloud metadata hostnames.
6. **2FA Local Generation & At-Rest Encryption Tests:** Test that 2FA QR code URIs do not invoke external services, and verify that `users.two_factor_secret` is encrypted in the database.
7. **AI Waiter IDOR & Token Authorization Tests:** Test that `/api/m/{slug}/ai-waiter/session/{id}/*` returns 403 or 404 when invoked without a valid matching `session_token` header/parameter.
8. **Rate Limiting & Lockout Tests:** Test brute force lockouts on `/login`, `/login/2fa` OTP verification, and password reset requests.
9. **Secrets Encryption Tests:** Verify that `vendors.payment_settings`, `vendors.ai_settings`, `vendors.telegram_settings`, and `vendors.crm_settings` are encrypted at rest.
10. **Cross-Tenant Location Override Tests:** Verify that `MenuBuilderController::saveOverride` rejects a `location_id` belonging to another vendor.
11. **SVG Upload Sanitization Tests:** Test that uploaded SVG files containing `<script>` or event handlers are sanitized or rejected.
12. **HTTP Security Headers Tests:** Test that all HTTP responses include CSP, HSTS, X-Content-Type-Options, and secure cookie attributes.

---

## 11. Recommended Implementation Order

The security hardening phases must be executed in the following priority order:

```
[Phase 1] Critical P0 Patching (Callbacks, Demo Login, XSS, SSRF, QR Leak)
    │
    ▼
[Phase 2] Multi-Tenant Architecture & Data Isolation Hardening
    │
    ▼
[Phase 3] Cryptographic Protection of Secrets & Data at Rest
    │
    ▼
[Phase 4] Authentication, Session Management & 2FA Hardening
    │
    ▼
[Phase 5] Authorization, RBAC & Laravel Policy Enforcement
    │
    ▼
[Phase 6] Public Endpoints & Storefront API Hardening
    │
    ▼
[Phase 7] AI Infrastructure & Prompt Injection Hardening
    │
    ▼
[Phase 8] Storage, Uploads & Content Security Hardening
    │
    ▼
[Phase 9] Network Security, SSRF Defenses & Outbound Traffic Controls
    │
    ▼
[Phase 10] Custom Domain Security & Host Header Hardening
    │
    ▼
[Phase 11] Payment & Subscription Flow Integrity
    │
    ▼
[Phase 12] Audit Logging, Security Observability & Event Telemetry
    │
    ▼
[Phase 13] HTTP Security Headers, Browser Hardening & Cookie Policies
    │
    ▼
[Phase 14] Automated Security Test Automation & CI/CD Pipeline
    │
    ▼
[Phase 15] Production Readiness, Disaster Recovery & Launch Gates
```
