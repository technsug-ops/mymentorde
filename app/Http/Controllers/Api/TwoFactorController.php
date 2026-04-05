<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserTwoFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function enable(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $existing = UserTwoFactor::where('user_id', $user->id)->first();
        if ($existing && $existing->isEnabled()) {
            return response()->json(['error' => '2FA zaten aktif.'], 422);
        }

        $secret = $this->google2fa->generateSecretKey();

        UserTwoFactor::updateOrCreate(
            ['user_id' => $user->id],
            [
                'secret'         => encrypt($secret),
                'recovery_codes' => collect(range(1, 8))->map(fn () => Str::random(10))->all(),
                'enabled_at'     => null,
            ]
        );

        $qrUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'MentorDE'),
            $user->email,
            $secret
        );

        return response()->json([
            'secret'  => $secret,
            'qr_url'  => $qrUrl,
            'message' => 'QR kodu tarayın ve /api/v1/2fa/verify ile doğrulayın.',
        ]);
    }

    public function verify(Request $request): \Illuminate\Http\JsonResponse
    {
        $data      = $request->validate(['code' => 'required|string|min:6|max:6']);
        $user      = $request->user();
        $twoFactor = UserTwoFactor::where('user_id', $user->id)->firstOrFail();

        $secret = decrypt($twoFactor->secret);
        $valid  = $this->google2fa->verifyKey($secret, $data['code']);

        if (!$valid) {
            return response()->json(['error' => 'Kod geçersiz veya süresi dolmuş.'], 422);
        }

        $twoFactor->update(['enabled_at' => now(), 'last_used_at' => now()]);

        return response()->json(['ok' => true, 'message' => '2FA aktifleştirildi.']);
    }

    public function disable(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['password' => 'required|string']);

        if (!\Illuminate\Support\Facades\Hash::check($request->input('password'), $request->user()->password)) {
            return response()->json(['error' => 'Şifre hatalı.'], 403);
        }

        UserTwoFactor::where('user_id', $request->user()->id)->delete();

        return response()->json(['ok' => true, 'message' => '2FA devre dışı bırakıldı.']);
    }

    public function challenge(Request $request): \Illuminate\Http\JsonResponse
    {
        $data      = $request->validate(['code' => 'required|string|min:6|max:6']);
        $user      = $request->user();
        $twoFactor = UserTwoFactor::where('user_id', $user->id)
            ->whereNotNull('enabled_at')
            ->firstOrFail();

        $secret = decrypt($twoFactor->secret);
        $valid  = $this->google2fa->verifyKey($secret, $data['code']);

        if (!$valid) {
            return response()->json(['error' => 'Kod geçersiz veya süresi dolmuş.'], 422);
        }

        $twoFactor->update(['last_used_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
