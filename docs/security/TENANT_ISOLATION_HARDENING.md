# Multi-Tenant Isolation Hardening Architecture & Audit

## 1. Executive Summary

This document specifies the multi-tenant isolation architecture and hardening measures implemented for the QR Menu SaaS platform ([elab.menu](https://github.com/agevorgyan/elab.menu)) in accordance with **PHASE 2 — Complete Multi-Tenant Isolation Hardening**.

The core objective is to mathematically and architecturally guarantee that **Vendor A can never read, modify, delete, assign, export, or otherwise access Vendor B's data**, even under malicious manipulation of client-supplied IDs, URL parameters, foreign keys, or request payloads.

Isolation is enforced through **Defense-in-Depth** across eight distinct, non-overlapping architectural layers:
1. **TenantContext** — Request and background job tenant lifecycle management.
2. **TenantScope** — Automatic Eloquent global query filtering.
3. **BelongsToVendor** — Automatic model association, ownership stamping, and cross-tenant mutation rejection.
4. **Model Lifecycle Invariant Guards** — Runtime validation rejecting cross-tenant foreign key linkages.
5. **Authorization Policies** — Granular role-based, tenant-bound policy gates registered with Laravel's Gate system.
6. **FormRequest Authorization & Tenant-Scoped Rules** — Request-time authorization and validation scoped strictly to current tenant IDs.
7. **Database Constraints** — Composite foreign keys and database-level unique constraints.
8. **Security Test Matrix** — Automated test suite verifying isolation across all actors, resources, and indirect attack vectors.

---

## 2. Complete Model Classification Matrix

Every model in the application is audited, classified, and assigned an explicit tenant boundary:

| Model | Classification | Tenant Boundary Attribute | Isolation Strategy | Notes |
| :--- | :--- | :--- | :--- | :--- |
| **`Vendor`** | Tenant Root | `id` | Route / Session / Domain lookup, `VendorPolicy` | The root tenant entity. |
| **`Location`** | Tenant-Owned | `vendor_id` | `BelongsToVendor`, `TenantScope`, `LocationPolicy` | Physical venue / branch. |
| **`Category`** | Tenant-Owned | `vendor_id` | `BelongsToVendor`, `TenantScope`, `CategoryPolicy` | Menu category. |
| **`Product`** | Tenant-Owned | `vendor_id` | `BelongsToVendor`, `TenantScope`, `ProductPolicy` | Menu item. Validates `category.vendor_id == vendor_id`. |
| **`ProductVariation`** | Tenant-Owned | Indirect via `product_id` | Cascades through `Product` | Size / option variation for a product. |
| **`ProductAddon`** | Tenant-Owned | Indirect via `product_id` | Cascades through `Product` | Extra ingredient or addon for a product. |
| **`Order`** | Tenant-Owned | `vendor_id` | `BelongsToVendor`, `TenantScope`, `OrderPolicy` | Invariant: `location.vendor_id == vendor_id`, `customer.vendor_id == vendor_id`. |
| **`OrderItem`** | Tenant-Owned / Pivot | Indirect via `order_id` | Cascades through `Order` | Line items belonging strictly to an order. |
| **`Customer`** | Tenant-Owned | `vendor_id` | `BelongsToVendor`, `TenantScope`, `CustomerPolicy` | Invariant: `location.vendor_id == vendor_id`. |
| **`WaiterCall`** | Tenant-Owned | `vendor_id` | `BelongsToVendor`, `TenantScope`, `WaiterCallPolicy` | Table service call requests. |
| **`LocationProductOverride`** | Tenant-Owned / Pivot | `vendor_id` | `BelongsToVendor`, `TenantScope`, `LocationProductOverridePolicy` | **Hardened**: Composite `UNIQUE(vendor_id, location_id, product_id)`. |
| **`AiWaiterSession`** | Tenant-Owned | `vendor_id` | `BelongsToVendor`, `TenantScope`, `AiWaiterSessionPolicy` | Invariant: `location.vendor_id == vendor_id`, `order.vendor_id == vendor_id`. |
| **`PaymentAttempt`** | Tenant-Owned | `vendor_id` | `BelongsToVendor`, `TenantScope` | Invariant: `order.vendor_id == vendor_id`, `subscription.vendor_id == vendor_id`. |
| **`SubscriptionPayment`** | Tenant-Owned | `vendor_id` | `BelongsToVendor`, `TenantScope` | SaaS billing ledger entries. |
| **`User`** | Tenant-Owned (Staff/Admin) or Platform | `vendor_id` (nullable) | `BelongsToVendor`, `TenantScope` | `vendor_id` is null only for platform superadmins. Invariant: `location.vendor_id == vendor_id`. |
| **`AnalyticsLog`** | Tenant-Owned | `vendor_id` | Explicit `vendor_id` column | Page and menu view telemetry. |
| **`SubscriptionPlan`** | Platform-Owned | None | Read-only to vendors, managed by platform superadmin | Available SaaS tiers. |

---

## 3. Defense-in-Depth Implementation Details

### Layer 1: TenantContext
The `TenantContext` service singleton manages the currently active tenant ID in memory:
- Initialized per request via URL parameters (`/m/{vendor_slug}`), authenticated session (`Auth::user()->vendor_id`), or resolved custom domain.
- Asynchronous queue jobs (such as `TranslateMenuJob`) execute inside `$tenantContext->runInTenantContext($vendorId, callable)`.
- Reset automatically between requests.

### Layer 2: TenantScope & Layer 3: BelongsToVendor
The `BelongsToVendor` trait applies a global Eloquent scope (`TenantScope`) ensuring all `select`, `update`, and `delete` queries automatically include `WHERE vendor_id = ?`:
- **Auto-Stamping**: When saving a new model while a `TenantContext` is active, `vendor_id` is automatically populated if not explicitly set.
- **Cross-Tenant Mutation Guard**: Throws `InvalidArgumentException` if a model is instantiated or updated with a foreign `vendor_id` different from the active `TenantContext`.
- **Cross-Tenant Transfer Prevention**: Prevents modifying the `vendor_id` of an existing record once persisted.

### Layer 4: Model Lifecycle Invariant Protection
To prevent indirect relation poisoning (e.g. assigning a Vendor B category to a Vendor A product), models enforce strict invariant checks in `booted()` saving hooks:
1. **`Product`**:
   ```php
   if ($product->category_id) {
       $category = Category::withoutGlobalScopes()->find($product->category_id);
       if (! $category || (int) $category->vendor_id !== (int) $product->vendor_id) {
           throw new \InvalidArgumentException('Cross-tenant category assignment is strictly prohibited.');
       }
   }
   ```
2. **`Order`**:
   ```php
   if ($order->location_id) {
       $location = Location::withoutGlobalScopes()->find($order->location_id);
       if (! $location || (int) $location->vendor_id !== (int) $order->vendor_id) {
           throw new \InvalidArgumentException('Cross-tenant location assignment is strictly prohibited.');
       }
   }
   if ($order->customer_id) {
       $customer = Customer::withoutGlobalScopes()->find($order->customer_id);
       if (! $customer || (int) $customer->vendor_id !== (int) $order->vendor_id) {
           throw new \InvalidArgumentException('Cross-tenant customer assignment is strictly prohibited.');
       }
   }
   ```
3. **`Customer` & `User`**:
   Guarantees that assigned `location_id` belongs strictly to the user/customer's `vendor_id`.
4. **`AiWaiterSession`**:
   Guarantees that assigned `location_id` and `order_id` belong strictly to the session's `vendor_id`.
5. **`PaymentAttempt`**:
   Guarantees that associated `order_id` and `subscription_id` belong strictly to the attempt's `vendor_id`.

### Layer 5: LocationProductOverride Hardening
`LocationProductOverride` was previously a pivot table lacking explicit tenant ownership. It has been hardened with:
1. **Migration**: Added `vendor_id` column indexed with foreign key constraint to `vendors.id`.
2. **Unique Composite Constraint**: `UNIQUE(vendor_id, location_id, product_id)` in `location_product_overrides`.
3. **Model Invariants**:
   ```php
   $override->vendor_id = $location->vendor_id;
   if ((int) $location->vendor_id !== (int) $product->vendor_id || (int) $override->vendor_id !== (int) $location->vendor_id) {
       throw new \InvalidArgumentException('Cross-tenant location product override is strictly prohibited.');
   }
   ```
4. **Policy Enforcement**: `LocationProductOverridePolicy` checks that all three entities share the user's vendor.

### Layer 6: Granular Authorization Policies
Dedicated policy classes have been authored and registered in `AppServiceProvider`:
- **`VendorPolicy`**: Enforces access boundaries for vendor profile, branding, operational settings, team management, and SaaS billing (`manageSettings`, `manageBilling`, `manageTeam`).
- **`LocationPolicy`**: `viewAny`, `view`, `create`, `update`, `delete`.
- **`CategoryPolicy`**: `viewAny`, `view`, `create`, `update`, `delete`.
- **`ProductPolicy`**: `viewAny`, `view`, `create`, `update`, `delete`.
- **`OrderPolicy`**: `viewAny`, `view`, `update`, `delete`, `export`.
- **`CustomerPolicy`**: `viewAny`, `view`, `create`, `update`, `delete`, `export`.
- **`WaiterCallPolicy`**: `viewAny`, `view`, `update`, `delete`.
- **`LocationProductOverridePolicy`**: `viewAny`, `view`, `create`, `update`, `delete`.
- **`AiWaiterSessionPolicy`**: `viewAny`, `view`, `create`, `update`, `delete`.
- **`MediaPolicy`**: `upload`, `delete` — Validates vendor directory scoping and rejects path traversal (`..`).

### Layer 7: FormRequest & Controller Validation
Every endpoint and FormRequest enforces tenant scoping:
- `StoreProductRequest` & `UpdateProductRequest`: Scopes `category_id` validation using `Rule::exists('categories', 'id')->where('vendor_id', Auth::user()?->vendor_id)`.
- `VendorSettingsController`: Scopes `featured_product_id` with `Rule::exists('products', 'id')->where('vendor_id', $vendor->id)`.
- `CustomerController`: All actions (`index`, `show`, `store`, `update`, `destroy`, `export`, `sendBirthdayGreeting`) explicitly call `$this->authorize()`.
- `OrderController`: All actions (`index`, `feed`, `updateStatus`, `updateWaiterCallStatus`, `receiptText`) explicitly call `$this->authorize()`.
- `VendorAdminController`: Team invitations verify that assigned locations belong strictly to the authenticated vendor, returning 403 otherwise.
- `TwoFactorController`: Confirms that the user's vendor matches any active custom domain tenant.

---

## 4. Legitimate Bypasses of `TenantScope` (`withoutGlobalScopes`)

A strict zero-trust audit was conducted on every usage of `withoutGlobalScopes()`. Below is the complete catalog and justification for each legitimate occurrence:

| Location | Code | Legitimate Purpose & Security Rationale |
| :--- | :--- | :--- |
| [`Product.php:23`](file:///Users/apple/Projects/qrmenu/app/Models/Product.php#L23) | `Category::withoutGlobalScopes()->find($product->category_id)` | **Invariant Inspection**: When saving a product, the invariant check must inspect global database reality to verify whether the submitted `category_id` actually belongs to the product's vendor. If scoped queries were used, an unauthorized category from Vendor B would return `null`, potentially causing an incorrect or bypassed error state. |
| [`Order.php:52`](file:///Users/apple/Projects/qrmenu/app/Models/Order.php#L52) | `Location::withoutGlobalScopes()->find($order->location_id)` | **Invariant Inspection**: Guarantees that an order cannot be placed or modified with a location belonging to another vendor. |
| [`Order.php:59`](file:///Users/apple/Projects/qrmenu/app/Models/Order.php#L59) | `Customer::withoutGlobalScopes()->find($order->customer_id)` | **Invariant Inspection**: Guarantees that an order cannot attach a customer profile belonging to another vendor. |
| [`Customer.php:39`](file:///Users/apple/Projects/qrmenu/app/Models/Customer.php#L39) | `Location::withoutGlobalScopes()->find($customer->location_id)` | **Invariant Inspection**: Guarantees that a customer profile cannot be attached to a location belonging to another vendor. |
| [`User.php:52`](file:///Users/apple/Projects/qrmenu/app/Models/User.php#L52) | `Location::withoutGlobalScopes()->find($user->location_id)` | **Invariant Inspection**: Guarantees that staff/manager users cannot be assigned to another vendor's location. |
| [`AiWaiterSession.php:47`](file:///Users/apple/Projects/qrmenu/app/Models/AiWaiterSession.php#L47) | `Location::withoutGlobalScopes()->find($session->location_id)` | **Invariant Inspection**: Guarantees that an AI Waiter session cannot be created or bound to another vendor's location. |
| [`AiWaiterSession.php:54`](file:///Users/apple/Projects/qrmenu/app/Models/AiWaiterSession.php#L54) | `Order::withoutGlobalScopes()->find($session->order_id)` | **Invariant Inspection**: Guarantees that an AI Waiter session cannot read or interact with another vendor's order. |
| [`PaymentAttempt.php:59`](file:///Users/apple/Projects/qrmenu/app/Models/PaymentAttempt.php#L59) | `Order::withoutGlobalScopes()->find($attempt->order_id)` | **Invariant Inspection**: Guarantees that a payment attempt cannot be created or verified against another vendor's order. |
| [`PaymentAttempt.php:66`](file:///Users/apple/Projects/qrmenu/app/Models/PaymentAttempt.php#L66) | `SubscriptionPayment::withoutGlobalScopes()->find(...)` | **Invariant Inspection**: Guarantees that a SaaS payment attempt cannot reference another vendor's subscription. |
| [`LocationProductOverride.php:30-31`](file:///Users/apple/Projects/qrmenu/app/Models/LocationProductOverride.php#L30-L31) | `Product::withoutGlobalScopes()->find(...)`, `Location::withoutGlobalScopes()->find(...)` | **Invariant Inspection**: Guarantees that an override record cannot link a product from Vendor A with a location from Vendor B. |

*Note: No controllers, services, or public endpoints bypass `TenantScope`. The only bypasses exist strictly within model-level lifecycle guards to verify global invariant truth before writing to the database.*

---

## 5. Security Test Matrix

A comprehensive security test suite has been established in [`tests/Feature/MultiTenantIsolationSecurityTest.php`](file:///Users/apple/Projects/qrmenu/tests/Feature/MultiTenantIsolationSecurityTest.php), exercising 28 dedicated security test cases with 96 assertions across all actors and resource vectors:

### Actors Tested
- `public` (Unauthenticated storefront / API visitor)
- `vendor owner A` (Primary administrator of Vendor A)
- `vendor manager A` (Operational manager of Vendor A)
- `vendor staff A` (Restricted staff member of Vendor A)
- `vendor owner B` (Malicious cross-tenant actor from Vendor B)
- `superadmin` (Platform-level administrator)

### Direct Resource & Action Matrix
| Resource | List / Index | View / Show | Create / Store | Update | Delete | Export |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **Products** | 404 / Scoped | 404 / 403 | 403 (Cross-tenant) | 404 / 403 | 404 / 403 | N/A |
| **Categories** | 404 / Scoped | 404 / 403 | 403 (Cross-tenant) | 404 / 403 | 404 / 403 | N/A |
| **Locations** | 404 / Scoped | 404 / 403 | 403 (Cross-tenant) | 404 / 403 | 404 / 403 | N/A |
| **Orders** | 404 / Scoped | 404 / 403 | Isolated | 404 / 403 | 404 / 403 | 404 / 403 |
| **Customers** | 404 / Scoped | 404 / 403 | Isolated | 404 / 403 | 404 / 403 | 403 (Forbidden) |
| **Waiter Calls** | 404 / Scoped | 404 / 403 | Isolated | 404 / 403 | 404 / 403 | N/A |
| **AI Sessions** | 404 / Scoped | 404 / 403 | Isolated | 404 / 403 | 404 / 403 | N/A |
| **Overrides** | 404 / Scoped | 404 / 403 | 403 / Invariant | 404 / 403 | 404 / 403 | N/A |
| **Settings / Billing**| 403 (Staff) | 403 (Staff) | 403 (Staff) | 403 (Cross-tenant) | N/A | N/A |

### Indirect Access Attack Vectors Tested
1. **Cross-Vendor Category Assignment**:
   Owner A submits a request to create a Product in Vendor A referencing Category B.
   *Result: HTTP 422 / 403 rejected.*
2. **Cross-Vendor Location on Order**:
   Attempt to create/save an Order for Vendor A with Location B.
   *Result: Invariant `InvalidArgumentException` blocks persistence.*
3. **Cross-Vendor Customer on Order**:
   Attempt to link Customer B to an Order belonging to Vendor A.
   *Result: Invariant `InvalidArgumentException` blocks persistence.*
4. **Cross-Vendor Location Product Override**:
   Attempt to create an override linking Product A with Location B.
   *Result: Database constraint + model invariant block persistence.*
5. **Cross-Vendor Order Status Manipulation**:
   Owner B attempts to update the status of Order A.
   *Result: HTTP 404 / 403 rejected.*
6. **Cross-Vendor Staff Assignment**:
   Owner A attempts to assign a new team member to Location B.
   *Result: HTTP 403 rejected.*
7. **Cross-Vendor Media Deletion & Path Traversal**:
   Attempt to delete media files outside the vendor's storage directory (e.g. `../` path traversal or targeting Vendor B).
   *Result: Rejected with `InvalidArgumentException` or 403.*
8. **Customer Data Export Isolation**:
   Owner B attempts to export Customer A records.
   *Result: Export strictly returns only Vendor B customers.*
9. **Staff Privilege Escalation**:
   Staff A attempts to access billing, subscriptions, or vendor business settings.
   *Result: HTTP 403 rejected.*

---

## 6. Verification Results

- **Security Test Suite**: `MultiTenantIsolationSecurityTest` (28 tests, 96 assertions) — **100% Passed**.
- **Complete Application Test Suite**: `php artisan test --compact` (291 tests, 1508 assertions) — **100% Passed**.
- **Pint Code Style Formatter**: `vendor/bin/pint --format agent` — **100% Clean**.
