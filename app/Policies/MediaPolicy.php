<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class MediaPolicy
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

    public function manage(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id && in_array($user->role, ['vendor_owner', 'manager']);
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id && in_array($user->role, ['vendor_owner', 'manager']);
    }
}
