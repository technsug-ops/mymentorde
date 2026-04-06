<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS  = 10;  // Bu kadar başarısız deneme → kilitle
    private const LOCKOUT_MINS  = 30;  // Kilit süresi (dakika)

    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole();
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Hesap kilit kontrolü
        $user = User::query()->where('email', strtolower(trim((string) $credentials['email'])))->first();
        if ($user && $user->locked_until && now()->lt($user->locked_until)) {
            $minutesLeft = (int) now()->diffInMinutes($user->locked_until, false);
            throw ValidationException::withMessages([
                'email' => "Hesap geçici olarak kilitlendi. {$minutesLeft} dakika sonra tekrar deneyin.",
            ]);
        }

        if (!Auth::attempt($credentials, true)) {
            // Başarısız giriş sayacını artır
            if ($user) {
                $attempts = (int) ($user->failed_login_attempts ?? 0) + 1;
                $updates  = [
                    'failed_login_attempts' => $attempts,
                    'last_failed_login_at'  => now(),
                ];
                if ($attempts >= self::MAX_ATTEMPTS) {
                    $updates['locked_until'] = now()->addMinutes(self::LOCKOUT_MINS);
                }
                $user->forceFill($updates)->save();
            }

            throw ValidationException::withMessages([
                'email' => 'E-posta veya sifre hatali.',
            ]);
        }

        // Başarılı giriş — sayacı sıfırla
        Auth::user()->forceFill([
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'last_failed_login_at'  => null,
        ])->save();

        $request->session()->regenerate();
        $request->session()->put('current_company_id', (int) (optional(Auth::user())->company_id ?? 0));

        // Login'de panel mode session'ı temizle (stale mod kalmasın)
        $request->session()->forget('mktg_panel_mode');

        return $this->redirectByRole();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->forget('current_company_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function redirectByRole()
    {
        $role = (string) optional(Auth::user())->role;

        return match ($role) {
            User::ROLE_MANAGER,
            User::ROLE_SYSTEM_ADMIN,
            User::ROLE_OPERATIONS_ADMIN,
            User::ROLE_FINANCE_ADMIN,
            User::ROLE_SYSTEM_STAFF,
            User::ROLE_OPERATIONS_STAFF,
            User::ROLE_FINANCE_STAFF => redirect('/manager/dashboard'),
            User::ROLE_SENIOR => redirect('/senior/dashboard'),
            User::ROLE_MENTOR => redirect('/senior/dashboard'),
            User::ROLE_GUEST => redirect('/guest/dashboard'),
            User::ROLE_STUDENT => redirect('/student/dashboard'),
            User::ROLE_DEALER => redirect('/dealer/dashboard'),
            User::ROLE_MARKETING_ADMIN,
            User::ROLE_SALES_ADMIN,
            User::ROLE_SALES_STAFF,
            User::ROLE_MARKETING_STAFF => redirect('/mktg-admin/dashboard'),
            default => tap(redirect('/login')->withErrors(['email' => 'Hesap rolü tanımsız. Sistem yöneticisiyle iletişime geçin.']), fn() => Auth::logout()),
        };
    }
}
