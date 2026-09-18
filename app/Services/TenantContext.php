<?php

namespace App\Services;

use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;

class TenantContext
{
    protected ?int $tenantId = null;
    protected ?Vendor $tenant = null;
    protected bool $bypassed = false;

    /**
     * Explicitly set the active tenant ID.
     */
    public function setTenantId(?int $id): void
    {
        $this->tenantId = $id;
        if ($id === null) {
            $this->tenant = null;
        }
    }

    /**
     * Explicitly set the active tenant model instance.
     */
    public function setTenant(?Vendor $vendor): void
    {
        $this->tenant = $vendor;
        $this->tenantId = $vendor?->id;
    }

    /**
     * Get the active tenant instance if loaded.
     */
    public function getTenant(): ?Vendor
    {
        if ($this->tenant !== null) {
            return $this->tenant;
        }

        $id = $this->getTenantId();
        if ($id !== null) {
            $this->tenant = Vendor::find($id);
            return $this->tenant;
        }

        return null;
    }

    /**
     * Resolve the current tenant ID.
     * Returns null if bypassed, if SuperAdmin, or if no tenant context is active.
     */
    public function getTenantId(): ?int
    {
        if ($this->bypassed) {
            return null;
        }

        // If explicitly set (e.g. via storefront route parameter or middleware)
        if ($this->tenantId !== null) {
            return $this->tenantId;
        }

        // Fallback to authenticated user's vendor_id
        if (Auth::check()) {
            $user = Auth::user();
            // Superadmins are platform administrators and are not restricted to any single tenant
            if ($user && $user->role === 'superadmin') {
                return null;
            }

            if ($user && !empty($user->vendor_id)) {
                return (int) $user->vendor_id;
            }
        }

        return null;
    }

    /**
     * Check if tenant scoping is currently bypassed.
     */
    public function isBypassed(): bool
    {
        return $this->bypassed;
    }

    /**
     * Temporarily execute a callback without any tenant scoping applied.
     */
    public function bypass(callable $callback): mixed
    {
        $previous = $this->bypassed;
        $this->bypassed = true;

        try {
            return $callback();
        } finally {
            $this->bypassed = $previous;
        }
    }

    /**
     * Execute a callback in the context of a specific tenant.
     */
    public function runInTenantContext(int $vendorId, callable $callback): mixed
    {
        $previousTenantId = $this->tenantId;
        $previousTenant = $this->tenant;
        $previousBypassed = $this->bypassed;

        $this->tenantId = $vendorId;
        $this->tenant = null;
        $this->bypassed = false;

        try {
            return $callback();
        } finally {
            $this->tenantId = $previousTenantId;
            $this->tenant = $previousTenant;
            $this->bypassed = $previousBypassed;
        }
    }

    /**
     * Reset tenant state.
     */
    public function clear(): void
    {
        $this->tenantId = null;
        $this->tenant = null;
        $this->bypassed = false;
    }
}
