<?php

namespace App\Policies;

use App\Models\LocationProductOverride;
use App\Models\User;

class LocationProductOverridePolicy
{
    /**
     * Superadmin bypasses all policy checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return ! empty($user->vendor_id);
    }

    public function view(User $user, LocationProductOverride $override): bool
    {
        return (int) $user->vendor_id === (int) $override->vendor_id;
    }

    public function create(User $user): bool
    {
        return ! empty($user->vendor_id) && in_array($user->role, ['vendor_owner', 'manager']);
    }

    public function update(User $user, LocationProductOverride $override): bool
    {
        return (int) $user->vendor_id === (int) $override->vendor_id && in_array($user->role, ['vendor_owner', 'manager']);
    }

    public function delete(User $user, LocationProductOverride $override): bool
    {
        return (int) $user->vendor_id === (int) $override->vendor_id && in_array($user->role, ['vendor_owner', 'manager']);
    }
}
