<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserTwoFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorSetupController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function show(Request $request)
    {
        $user      = $request->user();
        $existing  = UserTwoFactor::where('user_id', $user->id)->first();

        // Zaten aktifse challenge'a yönlendir
        if ($existing && $existing->isEnabled()) {
            $request->session()->put('2fa_passed', true);
            return redirect()->intended('/auth/redirect');
        }

        // Secret üret (veya mevcutu kullan)
        if (!$existing) {
            $secret    = $this->google2fa->generateSecretKey();
            $existing  = UserTwoFactor::create([
                'user_id'        => $user->id,
                'secret'         => encrypt($secret),
                'recovery_codes' => collect(range(1, 8))->map(fn () => Str::random(10))->values()->all(),
            ]);
        } else {
            $secret = decrypt($existing->secret);
        }

        $qrUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'MentorDE'),
            $user->email,
            $secret
        );

        return view('auth.two-factor-setup', compact('secret', 'qrUrl'));
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6|regex:/^\d{6}$/']);

        $user      = $request->user();
        $twoFactor = UserTwoFactor::where('user_id', $user->id)->firstOrFail();
        $secret    = decrypt($twoFactor->secret);

        $valid = $this->google2fa->verifyKey($secret, $request->input('code'));

        if (!$valid) {
            return back()->withErrors(['code' => 'Kod hatalı. Authenticator uygulamanızdan tekrar okuyun.']);
        }

        $twoFactor->update([
            'enabled_at'   => now(),
            'last_used_at' => now(),
        ]);

        $request->session()->put('2fa_passed', true);

        return redirect('/auth/redirect')->with('status', '2FA başarıyla etkinleştirildi.');
    }
}
