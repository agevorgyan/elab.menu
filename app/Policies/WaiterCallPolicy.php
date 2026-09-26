<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WaiterCall;

class WaiterCallPolicy
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

    public function view(User $user, WaiterCall $waiterCall): bool
    {
        return (int) $user->vendor_id === (int) $waiterCall->vendor_id;
    }

    public function create(User $user): bool
    {
        return ! empty($user->vendor_id);
    }

    public function update(User $user, WaiterCall $waiterCall): bool
    {
        return (int) $user->vendor_id === (int) $waiterCall->vendor_id;
    }

    public function delete(User $user, WaiterCall $waiterCall): bool
    {
        return (int) $user->vendor_id === (int) $waiterCall->vendor_id && in_array($user->role, ['vendor_owner', 'manager']);
    }
}
