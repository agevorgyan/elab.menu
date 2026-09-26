<?php

namespace App\Services;

use App\Models\Order;
use App\Models\WaiterCall;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderService
{
    /**
     * Get paginated orders (automatically scoped by TenantScope).
     */
    public function getOrders(?int $locationId = null, string $status = 'all', int $perPage = 15): LengthAwarePaginator
    {
        return Order::query()
            ->when($locationId, function ($query, $locId) {
                return $query->where('location_id', $locId);
            })
            ->when($status && $status !== 'all', function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->with('items')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get the latest order ID (automatically scoped by TenantScope).
     */
    public function getLatestOrderId(?int $locationId = null): int
    {
        return Order::query()
            ->when($locationId, function ($query, $locId) {
                return $query->where('location_id', $locId);
            })
            ->max('id') ?? 0;
    }

    /**
     * Get the count of pending orders (automatically scoped by TenantScope).
     */
    public function getPendingOrdersCount(?int $locationId = null): int
    {
        return Order::query()
            ->when($locationId, function ($query, $locId) {
                return $query->where('location_id', $locId);
            })
            ->where('status', 'pending')
            ->count();
    }

    /**
     * Get pending waiter calls (automatically scoped by TenantScope).
     */
    public function getPendingWaiterCalls(?int $locationId = null): Collection
    {
        return WaiterCall::query()
            ->when($locationId, function ($query, $locId) {
                return $query->where('location_id', $locId);
            })
            ->where('status', 'pending')
            ->latest()
            ->get();
    }
}
