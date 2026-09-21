<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Services\TenantContext;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    /**
     * Display the primary sales landing page, or the vendor menu if accessing via custom domain.
     */
    public function index(Request $request, TenantContext $tenantContext)
    {
        $customDomainVendor = $tenantContext->getTenant();
        if ($customDomainVendor) {
            return app(ClientStorefrontController::class)->showMenu($request, $customDomainVendor->slug);
        }

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        $settings = SystemSetting::getAll();

        return view('landing.index', compact('plans', 'settings'));
    }
}
