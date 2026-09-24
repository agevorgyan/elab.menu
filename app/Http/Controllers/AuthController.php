<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin(TenantContext $tenantContext)
    {
        $customVendor = $tenantContext->getTenant();

        if (Auth::check()) {
            $user = Auth::user();

            if ($customVendor && ! $user->isSuperAdmin() && (int) $user->vendor_id !== (int) $customVendor->id) {
                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();

                return view('auth.login', compact('customVendor'))->withErrors([
                    'email' => "Այս կառավարման վահանակը նախատեսված է միայն {$customVendor->name} ռեստորանի անձնակազմի համար։",
                ]);
            }

            return $this->redirectUser($user, $customVendor);
        }

        return view('auth.login', compact('customVendor'));
    }

    public function showDemoLogin(TenantContext $tenantContext)
    {
        $customVendor = $tenantContext->getTenant();

        return view('auth.demo_login', compact('customVendor'));
    }

    public function demoLogin(Request $request, TenantContext $tenantContext)
    {
        $role = $request->input('role', 'owner');

        // Only vendor accounts are permitted for demo; superadmin is strictly excluded
        $email = match ($role) {
            'manager' => 'manager@bistro.am',
            default => 'owner@bistro.am',
        };

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::where('role', 'vendor_owner')->first();
        }

        if (! $user) {
            return back()->withErrors([
                'email' => 'Դեմո օգտատերը չի գտնվել համակարգում։',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $customVendor = $tenantContext->getTenant();

        return $this->redirectUser($user, $customVendor);
    }

    public function login(Request $request, TenantContext $tenantContext)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            $customVendor = $tenantContext->getTenant();

            if ($customVendor && ! $user->isSuperAdmin() && (int) $user->vendor_id !== (int) $customVendor->id) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => "Այս կառավարման վահանակ կարող են մուտք գործել միայն {$customVendor->name} ռեստորանի օգտատերերը։",
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            return $this->redirectUser($user, $customVendor);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectUser($user, $customVendor = null)
    {
        if ($user->isSuperAdmin()) {
            if ($customVendor) {
                $platformUrl = rtrim(config('app.url', 'https://menu.elab.am'), '/');

                return redirect()->to($platformUrl.'/superadmin/dashboard');
            }

            return redirect()->route('superadmin.dashboard');
        }

        return redirect()->route('admin.dashboard');
    }
}
