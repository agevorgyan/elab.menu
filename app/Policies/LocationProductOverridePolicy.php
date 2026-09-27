<?php

namespace App\Policies;

use App\Models\LocationProductOverride;
use App\Models\User;
use App\Security\Permission;

class LocationProductOverridePolicy
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
        return ! empty($user->vendor_id) && $user->hasPermission(Permission::MENU_VIEW);
    }

    public function view(User $user, LocationProductOverride $override): bool
    {
        return (int) $user->vendor_id === (int) $override->vendor_id
            && $user->canAccessLocationId($override->location_id)
            && $user->hasPermission(Permission::MENU_VIEW);
    }

    public function create(User $user): bool
    {
        return ! empty($user->vendor_id) && $user->hasPermission(Permission::MENU_UPDATE);
    }

    public function update(User $user, LocationProductOverride $override): bool
    {
        return (int) $user->vendor_id === (int) $override->vendor_id
            && $user->canAccessLocationId($override->location_id)
            && $user->hasPermission(Permission::MENU_UPDATE);
    }

    public function delete(User $user, LocationProductOverride $override): bool
    {
        return (int) $user->vendor_id === (int) $override->vendor_id
            && $user->canAccessLocationId($override->location_id)
            && $user->hasPermission(Permission::MENU_UPDATE);
    }
}
