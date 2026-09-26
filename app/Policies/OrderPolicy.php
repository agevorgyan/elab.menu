<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
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

    public function view(User $user, Order $order): bool
    {
        return (int) $user->vendor_id === (int) $order->vendor_id;
    }

    public function create(User $user): bool
    {
        return ! empty($user->vendor_id);
    }

    public function update(User $user, Order $order): bool
    {
        return (int) $user->vendor_id === (int) $order->vendor_id;
    }

    public function delete(User $user, Order $order): bool
    {
        return (int) $user->vendor_id === (int) $order->vendor_id && in_array($user->role, ['vendor_owner', 'manager']);
    }

    public function export(User $user): bool
    {
        return ! empty($user->vendor_id) && in_array($user->role, ['vendor_owner', 'manager']);
    }
}
