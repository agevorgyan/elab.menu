<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;
use App\Security\Permission;

class MediaPolicy
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

    public function manage(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id
            && $user->hasPermission(Permission::STORAGE_MANAGE);
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id
            && $user->hasPermission(Permission::STORAGE_MANAGE);
    }
}
