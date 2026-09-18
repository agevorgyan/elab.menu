<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->vendor) {
            $vendor = $user->vendor;

            // Allow access to subscription status page and logout regardless of subscription status
            if ($request->routeIs('admin.subscription*') || $request->routeIs('logout')) {
                return $next($request);
            }

            if ($vendor->isExpired()) {
                return redirect()->route('admin.subscription')
                    ->with('warning', 'Ձեր բաժանորդագրության/փորձնական ժամկետն ավարտվել է։ Խնդրում ենք երկարաձգել այն՝ համակարգից օգտվելու համար։');
            }
        }

        return $next($request);
    }
}
