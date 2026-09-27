# Fine-Grained RBAC & Authorization Architecture (Phase 6)

## 1. Executive Summary

Prior to Phase 6, authorization within the QRMenu platform relied predominantly on coarse route-level role checks (e.g. `role:vendor_owner,manager,staff`) and manual, ad-hoc tenant conditionals inside controller actions. SuperAdmin enjoyed an unconstrained bypass across all policies via blanket `before()` hooks. Furthermore, staff members had unbounded access across different locations within a vendor, and roles like kitchen chefs and front-desk cashiers lacked tailor-made privilege boundaries.

**Phase 6 implements a comprehensive, permission-based Role-Based Access Control (RBAC) and policy architecture:**
- **6 Core System Roles:** `superadmin`, `vendor_owner`, `manager`, `staff`, `chef`, and `cashier`.
- **28 Granular Permissions:** Standardized permissions covering menu management, order lifecycle, CRM customer records, branch locations, team administration, billing/subscriptions, AI configuration, system settings, media storage, and reporting.
- **Explicit SuperAdmin Governance:** Replaced blanket `before()` bypasses with explicit platform permissions (`platform.access`, `platform.vendors`, `platform.settings`, etc.).
- **Strict Staff & Operational Guardrails:** Staff and kitchen chefs are strictly prevented from modifying billing, modifying AI API credentials, modifying security settings, inviting/managing users, or canceling/refunding orders without proper privileges.
- **Location-Level Scoping & Boundaries:** Staff, chefs, and cashiers assigned to Branch A are cryptographically and logically isolated from viewing or mutating orders, customers, or floor plans belonging to Branch B. Query parameter tampering (e.g., `?location_id=...`) is strictly blocked.
- **Custom Permission Overrides:** Dynamic per-user grants and denials via `custom_permissions` JSON column, preserving full backward compatibility with existing role defaults.
- **Privilege Escalation Prevention:** Tenant owners and managers cannot invite or assign `superadmin` or `vendor_owner` roles via tenant invitation endpoints.

---

## 2. Role & Permission Matrix

| Permission | Description | `superadmin` | `vendor_owner` | `manager` | `staff` | `chef` | `cashier` |
|---|---|:---:|:---:|:---:|:---:|:---:|:---:|
| `menu.view` | View categories, products, allergens | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `menu.create` | Create menu dishes & categories | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `menu.update` | Edit dishes, prices, availability | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `menu.delete` | Delete dishes & categories | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `orders.view` | View live orders & receipts | ❌ | ✅ | ✅ | ✅ *(Loc)* | ✅ *(Loc)* | ✅ *(Loc)* |
| `orders.update` | Update prep status (`preparing`, `ready`) | ❌ | ✅ | ✅ | ✅ *(Loc)* | ✅ *(Loc)* | ✅ *(Loc)* |
| `orders.cancel` | Cancel customer orders | ❌ | ✅ | ✅ | ❌ | ❌ | ✅ *(Loc)* |
| `orders.refund` | Mark orders refunded | ❌ | ✅ | ✅ | ❌ | ❌ | ✅ *(Loc)* |
| `customers.view`| View CRM directory & profiles | ❌ | ✅ | ✅ | ✅ *(Loc)* | ❌ | ✅ *(Loc)* |
| `customers.export`| Export customer CRM list to CSV | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `locations.view` | View branch locations & floor plans | ❌ | ✅ | ✅ | ✅ *(Loc)* | ✅ *(Loc)* | ✅ *(Loc)* |
| `locations.manage`| Create/edit branches & save floor plans | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `team.view` | View team members directory | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `team.manage` | Invite, edit, remove team members | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `billing.view` | View subscription plan & invoice history | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `billing.manage` | Upgrade, renew, modify payment keys | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `ai.view` | View AI waiter prompts & import tool | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `ai.manage` | Update AI keys, test AI connections | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `settings.view` | View restaurant & branding settings | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `settings.manage`| Update service fees, domains, Telegram | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `storage.view` | View uploaded tenant assets | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| `storage.manage`| Upload, replace, delete media assets | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `reports.view` | View analytics dashboard & visit counts | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ *(Loc)* |
| `reports.export`| Export analytics & financial reports | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `platform.access` | Access SuperAdmin panel routes | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `platform.vendors`| Manage platform vendor records | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `platform.subscriptions`| Manage platform billing plans | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `platform.settings`| Modify platform settings & landing | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

*(Loc)*: Restricted strictly to the user's assigned branch (`location_id`).

---

## 3. Location-Level Scoping & Boundaries

Users assigned to a specific branch (`location_id !== null`) are strictly prohibited from viewing or manipulating data belonging to other branches:

1. **Model Access Verification:**
   ```php
   // In User model:
   public function canAccessLocationId(?int $locationId): bool
   {
       if ($locationId === null) {
           return true;
       }

       if ($this->location_id !== null) {
           return (int) $this->location_id === (int) $locationId;
       }

       return true;
   }
   ```

2. **Policy Enforcement:**
   In [`OrderPolicy`](file:///Users/apple/Projects/qrmenu/app/Policies/OrderPolicy.php), [`CustomerPolicy`](file:///Users/apple/Projects/qrmenu/app/Policies/CustomerPolicy.php), and [`WaiterCallPolicy`](file:///Users/apple/Projects/qrmenu/app/Policies/WaiterCallPolicy.php):
   ```php
   public function view(User $user, Order $order): bool
   {
       return (int) $user->vendor_id === (int) $order->vendor_id
           && $user->canAccessLocationId($order->location_id)
           && $user->hasPermission(Permission::ORDERS_VIEW);
   }
   ```

3. **Controller & Query Tampering Protection:**
   In [`OrderController`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/OrderController.php), [`VendorAdminController`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/VendorAdminController.php), and [`CustomerController`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/CustomerController.php):
   ```php
   if ($user->location_id) {
       $activeLocationId = $user->location_id;
       if ($request->has('location_id') && (int) $request->get('location_id') !== (int) $user->location_id) {
           abort(403, 'Unauthorized access to other branch location.');
       }
   }
   ```

---

## 4. SuperAdmin Explicit Platform Governance

Previously, every tenant policy featured:
```php
// Insecure legacy pattern:
public function before(User $user, string $ability): ?bool {
    if ($user->isSuperAdmin()) return true;
    return null;
}
```

This bypass has been replaced across all policies with explicit permission checks:
```php
// Hardened pattern:
public function before(User $user, string $ability): ?bool {
    if ($user->isSuperAdmin()) {
        return $user->hasPermission(Permission::PLATFORM_VENDORS);
    }
    return null;
}
```
SuperAdmins without `platform.vendors` or `platform.access` are strictly rejected, preventing uncontrolled superuser bypasses.

---

## 5. Privilege Escalation Prevention

In [`VendorAdminController::storeTeamMember`](file:///Users/apple/Projects/qrmenu/app/Http/Controllers/VendorAdminController.php):
1. **Authorization Gate:** Calls `$this->authorize('team.manage');`.
2. **Role Whitelist:** Only assignable tenant roles are accepted:
   ```php
   'role' => 'required|string|in:manager,staff,chef,cashier'
   ```
3. **Owner Protection:** Attempting to assign `superadmin` or `vendor_owner` fails request validation with a validation error.
4. **User Deletion Protection:** In [`UserPolicy::delete`](file:///Users/apple/Projects/qrmenu/app/Policies/UserPolicy.php), users cannot delete themselves or the primary `vendor_owner`.

---

## 6. UI Navigation & Blade Directive Integration

Sidebar and header links in [`resources/views/layouts/app.blade.php`](file:///Users/apple/Projects/qrmenu/resources/views/layouts/app.blade.php) are wrapped with `@can(...)` directives:
- Kitchen chefs only see **Kitchen Orders** and **Menu Builder** (read-only). They do not see Billing, CRM, Settings, Team, or AI.
- Cashiers only see **Orders**, **Menu**, **Customers**, and **Reports**.
- Staff only see operational order handling for their assigned branch.
- Role badges render dynamically in the navbar and team list (`Սեփականատեր`, `Մենեջեր`, `Խոհարար`, `Գանձապահ`, `Անձնակազմ`).

---

## 7. Automated Test Suite

New comprehensive test suite: [`tests/Feature/FineGrainedRbacAuthorizationTest.php`](file:///Users/apple/Projects/qrmenu/tests/Feature/FineGrainedRbacAuthorizationTest.php)

| Test Case | Scope & Proof |
|---|---|
| `test_vendor_owner_has_full_tenant_permissions` | Proves vendor owner has access to all tenant modules including billing, settings, and team. |
| `test_manager_has_operational_permissions_but_cannot_modify_billing_or_manage_team` | Proves managers can manage menu/orders but get 403 on billing, raw AI keys, settings, and team invites. |
| `test_staff_cannot_modify_billing_ai_credentials_security_or_team` | Verifies staff get 403 on billing, team index, settings updates, AI updates, and AI tests. |
| `test_staff_and_chef_cannot_cancel_or_refund_orders` | Proves order cancellation and refunds require elevated permissions (`orders.cancel` / `orders.refund`). |
| `test_cashier_permissions_and_boundaries` | Confirms cashier can cancel/refund orders, but gets 403 on customer export and menu creation. |
| `test_chef_can_update_kitchen_order_status_but_cannot_access_customers_or_settings` | Confirms chef can transition order prep status (`preparing`), but gets 403 on CRM and Settings. |
| `test_location_isolation_prevents_cross_branch_order_access` | Proves staff assigned to Location A cannot view or update orders belonging to Location B. |
| `test_branch_assigned_user_cannot_tamper_location_id_query_parameter` | Proves passing `?location_id=LocationB` is rejected with 403 Forbidden. |
| `test_superadmin_requires_explicit_platform_permissions_rather_than_uncontrolled_bypass` | Confirms superadmins without `platform.vendors` cannot bypass tenant policies blindly. |
| `test_custom_permissions_override_role_defaults` | Proves dynamic `givePermission()` and `revokePermission()` override defaults dynamically. |
| `test_privilege_escalation_is_strictly_prevented_in_team_invitations` | Confirms attempts to invite `superadmin` or `vendor_owner` fail role validation. |

### Test Suite Execution:
```text
FineGrainedRbacAuthorizationTest: 11 passed, 102 assertions (525ms)
Full Suite: 335 passed, 0 failures, 1,785 assertions (12.22s)
Code formatting: Fully formatted with Laravel Pint
```
