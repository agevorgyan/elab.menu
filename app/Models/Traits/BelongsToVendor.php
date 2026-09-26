<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\Vendor;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToVendor
{
    /**
     * Boot the trait to attach TenantScope and auto-assign vendor_id on creation.
     */
    protected static function bootBelongsToVendor(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model) {
            $tenantId = app(TenantContext::class)->getTenantId();
            if ($tenantId !== null) {
                if (! empty($model->vendor_id) && (int) $model->vendor_id !== (int) $tenantId) {
                    throw new \InvalidArgumentException('Cross-tenant entity creation forbidden.');
                }
                $model->vendor_id = $tenantId;
            }
        });

        static::updating(function (Model $model) {
            $tenantId = app(TenantContext::class)->getTenantId();
            if ($tenantId !== null && $model->isDirty('vendor_id')) {
                if ((int) $model->vendor_id !== (int) $tenantId) {
                    throw new \InvalidArgumentException('Cross-tenant entity transfer forbidden.');
                }
            }
        });
    }

    /**
     * Relationship to the owning vendor.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Scope a query to bypass the tenant global scope.
     */
    public function scopeWithoutTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Static helper to query records without the tenant scope.
     */
    public static function withoutTenantQuery(): Builder
    {
        return static::withoutGlobalScope(TenantScope::class);
    }
}
