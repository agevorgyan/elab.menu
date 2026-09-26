<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
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
        return (int) $user->vendor_id === (int) $vendor->id && in_array($user->role, ['vendor_owner', 'manager']);
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return false;
    }

    public function manageSettings(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id && in_array($user->role, ['vendor_owner', 'manager']);
    }

    public function manageBilling(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id && $user->role === 'vendor_owner';
    }

    public function export(User $user, Vendor $vendor): bool
    {
        return (int) $user->vendor_id === (int) $vendor->id && in_array($user->role, ['vendor_owner', 'manager']);
    }
}
