<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ResetPasswordController extends Controller
{
    public function show(Request $request, string $token)
    {
        // Güvenlik: reset linkine tıklayan kullanıcı zaten login'se önce çıkış
        // yap — karışıklık olmasın, form gerçekten hangi hesap için olduğu net
        // görünsün ve reset sonrası tek ve temiz bir oturum başlasın.
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        return view('auth.reset-password', ['token' => $token]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password): void {
                    $user->forceFill(['password' => Hash::make($password)])->save();
                }
            );
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['email' => 'Şifre belirlenemedi, lütfen tekrar deneyin. Sorun sürerse yöneticinize bildirin.']);
        }

        if ($status === Password::PASSWORD_RESET) {
            return redirect('/login')->with('status', 'Şifreniz belirlendi. Şimdi giriş yapabilirsiniz.');
        }

        // lang/ klasörü yok → __($status) İngilizce framework metnini basıyordu
        // ("This password reset token is invalid."). Token ve kullanıcı hatası
        // aynı mesajı alır: form e-posta alanını da içerdiği için "kullanıcı
        // bulunamadı" demek e-posta varlığını sızdırırdı.
        return back()->withErrors(['email' => match ($status) {
            Password::INVALID_TOKEN,
            Password::INVALID_USER => 'Şifre sıfırlama bağlantısı geçersiz veya süresi dolmuş. Lütfen giriş sayfasından yeni bir bağlantı isteyin.',
            Password::RESET_THROTTLED => 'Çok sık denediniz. Lütfen bir dakika bekleyip tekrar deneyin.',
            default => 'Şifre belirlenemedi, lütfen tekrar deneyin.',
        }]);
    }
}
