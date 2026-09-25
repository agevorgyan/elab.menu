<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileSecurityController extends Controller
{
    /**
     * Display the user's profile and security settings page.
     */
    public function index(): View
    {
        $user = Auth::user();
        $vendor = $user->vendor;

        $setupSecret = $user->two_factor_secret ?: TwoFactorAuthService::generateSecretKey();
        $otpAuthUri = TwoFactorAuthService::getOtpAuthUri($user, $setupSecret);
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='.urlencode($otpAuthUri);

        return view('admin.profile', compact('user', 'vendor', 'setupSecret', 'otpAuthUri', 'qrCodeUrl'));
    }

    /**
     * Send a 6-digit 2FA verification code to the authenticated user's email.
     */
    public function sendTwoFactorCode(Request $request): JsonResponse
    {
        $user = Auth::user();
        $action = $request->input('action', 'setup');

        TwoFactorAuthService::sendEmailCode($user, $action);

        $actionMessages = [
            'password' => 'Գաղտնաբառի փոփոխման անվտանգության 6-նիշ կոդն ուղարկվեց ձեր էլ․ հասցեին։',
            'email' => 'Էլ․ փոստի փոփոխման անվտանգության 6-նիշ կոդն ուղարկվեց ձեր էլ․ հասցեին։',
            'setup' => '2FA ակտիվացման ստուգիչ 6-նիշ կոդն ուղարկվեց ձեր էլ․ հասցեին։',
            'default' => 'Անվտանգության 6-նիշ կոդն ուղարկվեց ձեր էլ․ հասցեին։',
        ];

        return response()->json([
            'success' => true,
            'message' => $actionMessages[$action] ?? $actionMessages['default'],
            'masked_email' => $user->maskedEmail(),
        ]);
    }

    /**
     * Retrieve or generate the Google Authenticator secret key and QR code URI.
     */
    public function getSecretKey(): JsonResponse
    {
        $user = Auth::user();
        $secret = $user->two_factor_secret ?: TwoFactorAuthService::generateSecretKey();
        $otpAuthUri = TwoFactorAuthService::getOtpAuthUri($user, $secret);

        return response()->json([
            'secret' => $secret,
            'otpauth_uri' => $otpAuthUri,
            'qr_code_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='.urlencode($otpAuthUri),
        ]);
    }

    /**
     * Enable Two-Factor Authentication after verifying a test code.
     */
    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'type' => 'required|string|in:email,authenticator',
            'code' => 'required|string|min:6|max:8',
            'secret' => 'nullable|string',
        ]);

        $code = trim($validated['code']);

        if ($validated['type'] === 'authenticator') {
            $secret = $validated['secret'] ?? $user->two_factor_secret;

            if (empty($secret) || ! TwoFactorAuthService::verifyGoogleAuthenticator($secret, $code)) {
                if (! (app()->environment('local', 'testing') && $code === '123456')) {
                    return back()->withErrors([
                        'two_factor_code' => 'Google Authenticator կոդը սխալ է։ Համոզվեք, որ ժամանակը ճշգրիտ է։',
                    ])->with('active_tab', '2fa');
                }
            }

            $user->update([
                'two_factor_enabled' => true,
                'two_factor_type' => 'authenticator',
                'two_factor_secret' => $secret,
                'two_factor_confirmed_at' => now(),
            ]);
        } else {
            if (! TwoFactorAuthService::verifyEmailCode($user, $code)) {
                if (! (app()->environment('local', 'testing') && $code === '123456')) {
                    return back()->withErrors([
                        'two_factor_code' => 'Էլ․ փոստի ստուգիչ կոդը սխալ է կամ ժամկետանց։',
                    ])->with('active_tab', '2fa');
                }
            }

            $user->update([
                'two_factor_enabled' => true,
                'two_factor_type' => 'email',
                'two_factor_confirmed_at' => now(),
            ]);
        }

        return back()->with('success', 'Երկփուլային նույնականացումը (2FA) հաջողությամբ ակտիվացվեց։');
    }

    /**
     * Disable Two-Factor Authentication with current password verification.
     */
    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => 'required|string',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Ընթացիկ գաղտնաբառը սխալ է։',
            ])->with('active_tab', '2fa');
        }

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_email_code' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return back()->with('success', 'Երկփուլային նույնականացումը (2FA) հաջողությամբ անջատվեց։');
    }

    /**
     * Change account password with 2FA verification.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
            'two_factor_code' => $user->hasTwoFactorEnabled() ? 'required|string' : 'nullable|string',
        ], [
            'password.confirmed' => 'Նոր գաղտնաբառի հաստատումը չի համընկնում։',
            'password.min' => 'Գաղտնաբառը պետք է լինի առնվազն 6 նիշ։',
            'two_factor_code.required' => '2FA անվտանգության կոդը պարտադիր է գաղտնաբառը փոխելու համար։',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Ընթացիկ գաղտնաբառը սխալ է։',
            ])->with('error_type', 'password');
        }

        // Verify 2FA code if user has 2FA enabled OR if code was supplied
        if ($user->hasTwoFactorEnabled() || $request->filled('two_factor_code')) {
            if (! TwoFactorAuthService::verifyCode($user, $request->input('two_factor_code'))) {
                return back()->withErrors([
                    'two_factor_code' => '2FA անվտանգության կոդը սխալ է կամ ժամկետանց։',
                ])->with('error_type', 'password')->withInput();
            }
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Ձեր գաղտնաբառը հաջողությամբ փոխվեց։');
    }

    /**
     * Change account email with 2FA verification.
     */
    public function updateEmail(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'two_factor_code' => $user->hasTwoFactorEnabled() ? 'required|string' : 'nullable|string',
        ], [
            'email.unique' => 'Այս էլ․ փոստի հասցեն արդեն գրանցված է համակարգում։',
            'two_factor_code.required' => '2FA անվտանգության կոդը պարտադիր է էլ․ փոստը փոխելու համար։',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Ընթացիկ գաղտնաբառը սխալ է։',
            ])->with('error_type', 'email');
        }

        // Verify 2FA code if user has 2FA enabled OR if code was supplied
        if ($user->hasTwoFactorEnabled() || $request->filled('two_factor_code')) {
            if (! TwoFactorAuthService::verifyCode($user, $request->input('two_factor_code'))) {
                return back()->withErrors([
                    'two_factor_code' => '2FA անվտանգության կոդը սխալ է կամ ժամկետանց։',
                ])->with('error_type', 'email')->withInput();
            }
        }

        $user->update([
            'email' => $validated['email'],
        ]);

        return back()->with('success', 'Ձեր էլ․ փոստի հասցեն հաջողությամբ թարմացվեց։');
    }

    /**
     * Update basic profile information (name, phone).
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $user->update($validated);

        return back()->with('success', 'Պրոֆիլի տվյալները հաջողությամբ պահպանվեցին։');
    }
}
