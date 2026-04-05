<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserTwoFactor;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function show(Request $request)
    {
        if ($request->session()->get('2fa_passed')) {
            return redirect()->intended('/auth/redirect');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6|regex:/^\d{6}$/']);

        $user      = $request->user();
        $twoFactor = UserTwoFactor::where('user_id', $user->id)
            ->whereNotNull('enabled_at')
            ->first();

        if (!$twoFactor) {
            // 2FA kaydı yoksa geç (henüz setup yapmamış)
            $request->session()->put('2fa_passed', true);
            return redirect()->intended('/auth/redirect');
        }

        $secret = decrypt($twoFactor->secret);
        $valid  = $this->google2fa->verifyKey($secret, $request->input('code'));

        if (!$valid) {
            return back()->withErrors(['code' => 'Kod hatalı veya süresi dolmuş. Tekrar deneyin.']);
        }

        $twoFactor->update(['last_used_at' => now()]);
        $request->session()->put('2fa_passed', true);

        return redirect()->intended('/auth/redirect');
    }
}
