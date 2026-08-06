<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
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
    public function edit(): View
    {
        return view('manager.account.edit');
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
