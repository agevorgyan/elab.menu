<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
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

    public function view(User $user, Category $category): bool
    {
        return (int) $user->vendor_id === (int) $category->vendor_id;
    }

    public function create(User $user): bool
    {
        return ! empty($user->vendor_id) && in_array($user->role, ['vendor_owner', 'manager']);
    }

    public function update(User $user, Category $category): bool
    {
        return (int) $user->vendor_id === (int) $category->vendor_id && in_array($user->role, ['vendor_owner', 'manager']);
    }

    public function delete(User $user, Category $category): bool
    {
        return (int) $user->vendor_id === (int) $category->vendor_id && in_array($user->role, ['vendor_owner', 'manager']);
    }
}
