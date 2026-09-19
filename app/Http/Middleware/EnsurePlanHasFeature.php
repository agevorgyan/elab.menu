<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanHasFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if ($user && $user->vendor) {
            $vendor = $user->vendor;

            if (! $vendor->hasFeature($feature)) {
                $featureNames = [
                    'orders' => 'Օնլայն պատվերների',
                    'customers' => 'Հաճախորդների CRM',
                    'locations' => 'Բազմամասնաճյուղ կառավարման',
                    'team' => 'Աշխատակիցների դերերի',
                ];

                $name = $featureNames[$feature] ?? 'այս';
                $planNeeded = in_array($feature, ['orders', 'customers']) ? 'Pro' : 'Business';
                $planName = $vendor->plan?->name ?? strtoupper($vendor->subscription_plan ?? 'BASIC');

                return redirect()->route('admin.subscription')
                    ->with('warning', "🔒 {$name} ֆունկցիան հասանելի չէ Ձեր ընթացիկ ({$planName}) փաթեթում։ Խնդրում ենք անցնել {$planNeeded} փաթեթի։");
            }
        }

        return $next($request);
    }
}
