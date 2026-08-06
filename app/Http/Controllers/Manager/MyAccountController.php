<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\UserTwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "Hesabım" — kullanıcının kendi giriş bilgilerini yönetmesi.
 *
 * NEDEN GEREKLİ: firmanın kendi giriş e-postasını değiştirebileceği hiçbir
 * ekran yoktu. Platform sahibinden istemek zorundaydılar.
 *
 * SENKRON SORUNU YOK: e-posta tek bir yerde (`users.email`) tutuluyor,
 * kopyası çıkarılmıyor. Buradan değiştirildiğinde platform konsolunda da
 * anında yeni adres görünür; ters yönde de öyle.
 *
 * ── GÜVENLİK ─────────────────────────────────────────────────────────────
 * E-posta aynı zamanda giriş kimliği ve şifre sıfırlamanın gittiği adres.
 * Açık bir oturumu ele geçiren biri adresi değiştirip hesabı kalıcı olarak
 * devralabilirdi; bu yüzden MEVCUT ŞİFRE isteniyor.
 */
class MyAccountController extends Controller
{
    public function edit(Request $request): View
    {
        $twoFactor = UserTwoFactor::where('user_id', $request->user()->id)->first();

        return view('manager.account.edit', [
            'twoFactorEnabled' => (bool) ($twoFactor?->isEnabled()),
            'twoFactorSince'   => $twoFactor?->enabled_at,
        ]);
    }

    /**
     * 2FA'yı sıfırla — yeni cihazda yeniden kurulur.
     *
     * ── NEDEN GEREKLİ ────────────────────────────────────────────────────
     * 2FA yalnızca Require2FA'nın zorunlu yönlendirmesiyle, BİR KEZ
     * kurulabiliyordu. Kurulduktan sonra hiçbir ekranda görünmüyordu:
     * telefonunu değiştiren ya da authenticator'ı silen kullanıcı kendi
     * hesabına bir daha giremezdi — panelde çıkış yolu yoktu.
     *
     * Kaydı silmek yeterli: Require2FA bir sonraki istekte kullanıcıyı
     * kurulum ekranına götürüyor ve yeni QR üretiliyor.
     *
     * ⚠ MEVCUT ŞİFRE ŞART. Aksi halde açık bir oturumu ele geçiren biri
     * 2FA'yı sıfırlayıp ikinci faktörü tamamen devre dışı bırakabilirdi —
     * korumanın kendisi saldırı yüzeyi olurdu.
     */
    public function resetTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate(
            ['current_password' => ['required', 'string']],
            ['current_password.required' => 'İşlemi onaylamak için mevcut şifrenizi girin.']
        );

        if (! Hash::check((string) $request->input('current_password'), (string) $user->password)) {
            return back()->withErrors(['current_password' => 'Mevcut şifre yanlış.']);
        }

        UserTwoFactor::where('user_id', $user->id)->delete();

        // Bu oturumun 2FA muafiyeti de kalkmalı, yoksa kurulum ekranına
        // yönlendirme bir sonraki oturuma kalırdı.
        $request->session()->forget('2fa_passed');

        return redirect()->route('2fa.setup')
            ->with('status', 'İki faktörlü doğrulama sıfırlandı. Yeni cihazınızla QR kodu okutun.');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:120'],
            // GLOBAL unique: aynı adres başka bir şirkette bile olsa alınamaz.
            'email' => [
                'required', 'email', 'max:190',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => ['required', 'string'],
        ], [
            'email.unique'              => 'Bu e-posta adresi başka bir hesapta kullanılıyor.',
            'current_password.required' => 'Değişikliği onaylamak için mevcut şifrenizi girin.',
        ]);

        if (! Hash::check($data['current_password'], (string) $user->password)) {
            return back()->withInput()->withErrors([
                'current_password' => 'Mevcut şifre yanlış.',
            ]);
        }

        $newEmail = strtolower(trim((string) $data['email']));
        $emailChanged = $newEmail !== strtolower((string) $user->email);

        $user->forceFill([
            'name'  => trim((string) $data['name']),
            'email' => $newEmail,
        ])->save();

        return back()->with('status', $emailChanged
            ? 'Bilgileriniz güncellendi. Bundan sonra ' . $newEmail . ' ile giriş yapacaksınız.'
            : 'Bilgileriniz güncellendi.');
    }
}
