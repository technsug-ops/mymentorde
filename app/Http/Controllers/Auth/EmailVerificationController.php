<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Public welcome verification — login gerektirmez.
     *
     * Apply form sonrası gönderilen "hoş geldin" mailindeki linke tıklayınca
     * çalışır. Signed URL doğrulanır, email_verified_at set edilir, kullanıcı
     * /login sayfasına yönlendirilir (success message ile). Şifre mailde
     * plaintext olarak verilmiş; user oradan kopyalayıp girer.
     */
    public function verifyPublic(Request $request, int $id, string $hash)
    {
        $user = User::query()->withoutGlobalScope('company')->find($id);
        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'Geçersiz doğrulama bağlantısı.']);
        }

        if (!hash_equals(sha1((string) $user->getEmailForVerification()), (string) $hash)) {
            abort(403, 'Geçersiz doğrulama bağlantısı.');
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()->route('login')->with('status', '✓ E-posta adresiniz doğrulandı. Mailde gönderilen şifreyle giriş yapabilirsiniz.');
    }

    public function notice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended('/');
        }

        return view('auth.verify-email', ['user' => $request->user()]);
    }

    public function verify(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended('/auth/redirect');
        }

        $request->fulfill();

        return redirect('/auth/redirect')->with('status', 'email-verified');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended('/auth/redirect');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
