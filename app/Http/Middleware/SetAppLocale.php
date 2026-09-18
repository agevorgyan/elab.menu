<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetAppLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestedLocale = $request->get('lang') ?? $request->get('locale');
        if ($requestedLocale && in_array($requestedLocale, ['hy', 'en', 'ru'])) {
            session(['app_locale' => $requestedLocale, 'locale' => $requestedLocale]);
            App::setLocale($requestedLocale);
        } else {
            $locale = session('app_locale') ?? session('locale', 'hy');
            if (in_array($locale, ['hy', 'en', 'ru'])) {
                App::setLocale($locale);
            } else {
                App::setLocale('hy');
            }
        }

        return $next($request);
    }
}
