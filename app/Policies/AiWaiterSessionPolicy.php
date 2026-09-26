<?php

namespace App\Policies;

use App\Models\AiWaiterSession;
use App\Models\User;

class AiWaiterSessionPolicy
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

    public function view(User $user, AiWaiterSession $session): bool
    {
        return (int) $user->vendor_id === (int) $session->vendor_id;
    }

    public function delete(User $user, AiWaiterSession $session): bool
    {
        return (int) $user->vendor_id === (int) $session->vendor_id && in_array($user->role, ['vendor_owner', 'manager']);
    }
}
