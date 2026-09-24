<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CaptchaService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    /**
     * Display the password reset form for the given token.
     */
    public function showResetForm(Request $request, string $token, TenantContext $tenantContext)
    {
        $email = $request->query('email');
        $customVendor = $tenantContext->getTenant();
        $captcha = CaptchaService::generate();

        return view('auth.reset_password', compact('token', 'email', 'customVendor', 'captcha'));
    }

    /**
     * Reset the given user's password.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'captcha' => ['required', 'string'],
        ]);

        if (! CaptchaService::validate($request->input('captcha'))) {
            return back()->withErrors([
                'captcha' => 'Անվտանգության հարցի (CAPTCHA) պատասխանը սխալ է։',
            ])->withInput();
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record) {
            return back()->withErrors([
                'email' => 'Գաղտնաբառի վերականգնման հարցում չի գտնվել կամ հղումն անվավեր է։',
            ]);
        }

        // Token lifetime: 60 minutes
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return back()->withErrors([
                'email' => 'Գաղտնաբառի վերականգնման հղման ժամկետն ավարտվել է։ Խնդրում ենք կատարել նոր հարցում։',
            ]);
        }

        if (! Hash::check($request->token, $record->token)) {
            return back()->withErrors([
                'token' => 'Գաղտնաբառի վերականգնման տոկենը սխալ է։',
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'Տվյալ էլ․ հասցեով օգտատեր չի գտնվել։',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('status', 'Ձեր գաղտնաբառը հաջողությամբ փոխվեց։ Այժմ կարող եք մուտք գործել նոր գաղտնաբառով։');
    }
}
