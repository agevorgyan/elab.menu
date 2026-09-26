<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
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

    public function view(User $user, Product $product): bool
    {
        return (int) $user->vendor_id === (int) $product->vendor_id;
    }

    public function create(User $user): bool
    {
        return ! empty($user->vendor_id) && in_array($user->role, ['vendor_owner', 'manager']);
    }

    public function update(User $user, Product $product): bool
    {
        return (int) $user->vendor_id === (int) $product->vendor_id && in_array($user->role, ['vendor_owner', 'manager', 'staff']);
    }

    public function delete(User $user, Product $product): bool
    {
        return (int) $user->vendor_id === (int) $product->vendor_id && in_array($user->role, ['vendor_owner', 'manager']);
    }

    public function export(User $user): bool
    {
        return ! empty($user->vendor_id) && in_array($user->role, ['vendor_owner', 'manager']);
    }
}
