<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IdentifyTenant
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Check for storefront vendor_slug in route parameters
        $slug = $request->route('vendor_slug');
        if ($slug) {
            $vendor = Vendor::where('slug', $slug)->first();
            if ($vendor) {
                $this->tenantContext->setTenant($vendor);
            }
        } elseif (Auth::check()) {
            $user = Auth::user();
            if ($user && $user->role === 'superadmin') {
                // SuperAdmin platform administrative context
                $this->tenantContext->clear();
            } elseif ($user && $user->vendor_id) {
                $this->tenantContext->setTenantId((int) $user->vendor_id);
            }
        }

        return $next($request);
    }
}
