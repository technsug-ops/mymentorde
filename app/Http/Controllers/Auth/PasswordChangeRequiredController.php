<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Manager paneli "Şifre Sıfırla" akışı sonrası geçici şifreyle giriş yapan
 * kullanıcının zorunlu olarak yeni şifre belirleme ekranı.
 */
class PasswordChangeRequiredController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect('/login');
        }

        // password_must_change false ise zaten bu sayfaya gelmemeli
        if (empty($user->password_must_change)) {
            return redirect('/auth/redirect');
        }

        return view('auth.password-change-required');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect('/login');
        }

        $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers(),
            ],
        ], [
            'password.required'  => 'Yeni şifre zorunludur.',
            'password.confirmed' => 'Şifreler eşleşmiyor.',
            'password.min'       => 'Şifre en az 8 karakter olmalı.',
        ]);

        $newPassword = (string) $request->input('password');

        // Mevcut (geçici) şifre ile aynı olmasın
        if (Hash::check($newPassword, $user->password)) {
            return back()->withErrors([
                'password' => 'Yeni şifre geçici şifreyle aynı olamaz.',
            ]);
        }

        $user->update([
            'password'             => Hash::make($newPassword),
            'password_must_change' => false,
        ]);

        return redirect()->to('/auth/redirect')
            ->with('status', 'Şifren güncellendi. Artık panele erişebilirsin.');
    }
}
