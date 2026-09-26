<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
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

    public function view(User $user, Location $location): bool
    {
        return (int) $user->vendor_id === (int) $location->vendor_id;
    }

    public function create(User $user): bool
    {
        return ! empty($user->vendor_id) && in_array($user->role, ['vendor_owner', 'manager']);
    }

    public function update(User $user, Location $location): bool
    {
        return (int) $user->vendor_id === (int) $location->vendor_id && in_array($user->role, ['vendor_owner', 'manager']);
    }

    public function delete(User $user, Location $location): bool
    {
        return (int) $user->vendor_id === (int) $location->vendor_id && $user->role === 'vendor_owner';
    }
}
