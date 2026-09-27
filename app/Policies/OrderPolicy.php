<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Security\Permission;

class OrderPolicy
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
        return ! empty($user->vendor_id) && $user->hasPermission(Permission::ORDERS_VIEW);
    }

    public function view(User $user, Order $order): bool
    {
        return (int) $user->vendor_id === (int) $order->vendor_id
            && $user->canAccessLocationId($order->location_id)
            && $user->hasPermission(Permission::ORDERS_VIEW);
    }

    public function create(User $user): bool
    {
        return ! empty($user->vendor_id) && $user->hasPermission(Permission::ORDERS_UPDATE);
    }

    public function update(User $user, Order $order): bool
    {
        return (int) $user->vendor_id === (int) $order->vendor_id
            && $user->canAccessLocationId($order->location_id)
            && $user->hasPermission(Permission::ORDERS_UPDATE);
    }

    public function cancel(User $user, Order $order): bool
    {
        return (int) $user->vendor_id === (int) $order->vendor_id
            && $user->canAccessLocationId($order->location_id)
            && $user->hasPermission(Permission::ORDERS_CANCEL);
    }

    public function refund(User $user, Order $order): bool
    {
        return (int) $user->vendor_id === (int) $order->vendor_id
            && $user->canAccessLocationId($order->location_id)
            && $user->hasPermission(Permission::ORDERS_REFUND);
    }

    public function delete(User $user, Order $order): bool
    {
        return (int) $user->vendor_id === (int) $order->vendor_id
            && $user->canAccessLocationId($order->location_id)
            && $user->hasPermission(Permission::ORDERS_CANCEL);
    }

    public function export(User $user): bool
    {
        return ! empty($user->vendor_id) && $user->hasPermission(Permission::REPORTS_EXPORT);
    }
}
