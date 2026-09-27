<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;
use App\Security\Permission;

class VendorPolicy
{
    /**
     * Superadmin checks explicit platform permission rather than uncontrolled bypass.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return $user->hasPermission(Permission::PLATFORM_VENDORS);
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id
            && $user->hasPermission(Permission::SETTINGS_MANAGE);
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return false;
    }

    public function manageSettings(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id
            && $user->hasPermission(Permission::SETTINGS_MANAGE);
    }

    public function viewSettings(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id
            && $user->hasPermission(Permission::SETTINGS_VIEW);
    }

    public function manageBilling(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id
            && $user->hasPermission(Permission::BILLING_MANAGE);
    }

    public function viewBilling(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id
            && $user->hasPermission(Permission::BILLING_VIEW);
    }

    public function manageAi(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id
            && $user->hasPermission(Permission::AI_MANAGE);
    }

    public function viewAi(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id
            && $user->hasPermission(Permission::AI_VIEW);
    }

    public function manageTeam(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id
            && $user->hasPermission(Permission::TEAM_MANAGE);
    }

    public function viewTeam(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id
            && $user->hasPermission(Permission::TEAM_VIEW);
    }

    public function export(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id
            && $user->hasPermission(Permission::REPORTS_EXPORT);
    }
}
