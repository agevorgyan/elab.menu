<?php

namespace App\Policies;

use App\Models\AiWaiterSession;
use App\Models\User;
use App\Security\Permission;

class AiWaiterSessionPolicy
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
        return ! empty($user->vendor_id) && $user->hasPermission(Permission::AI_VIEW);
    }

    public function view(User $user, AiWaiterSession $session): bool
    {
        return (int) $user->vendor_id === (int) $session->vendor_id
            && $user->hasPermission(Permission::AI_VIEW);
    }

    public function delete(User $user, AiWaiterSession $session): bool
    {
        return (int) $user->vendor_id === (int) $session->vendor_id
            && $user->hasPermission(Permission::AI_MANAGE);
    }
}
