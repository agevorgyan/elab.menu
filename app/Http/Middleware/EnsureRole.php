<?php

namespace App\Http\Middleware;

use App\Security\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (in_array($user->role, $roles) || ($user->isSuperAdmin() && $user->hasPermission(Permission::PLATFORM_ACCESS))) {
            return $next($request);
        }

        abort(403, 'Unauthorized action for your role.');
    }
}
