<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle()
    {
        // prompt=select_account → kullanıcı her zaman hesap seçicisini görsün
        // (önceki hesapla sessiz auto-login engellenir, yanlış hesap karışıklığı önlenir)
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'trace' => substr($e->getTraceAsString(), 0, 1500),
            ]);
            return redirect('/login')->withErrors([
                'email' => 'Google ile giriş başarısız oldu: ' . $e->getMessage(),
            ]);
        }

        $email = strtolower(trim((string) $googleUser->getEmail()));
        if ($email === '') {
            return redirect('/login')->withErrors([
                'email' => 'Google hesabında e-posta bilgisi bulunamadı.',
            ]);
        }

        // Önceki oturum varsa temizle — Socialite state validation bitti, artık güvenli.
        // Aksi halde başka Google hesabı ile gelen kullanıcı eski oturuma sticky kalabilir.
        if (Auth::check() && (string) Auth::user()->email !== $email) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // Mevcut kullanıcı var mı? (soft-deleted dahil)
        $existing = User::withTrashed()->withoutGlobalScopes()->where('email', $email)->first();

        if ($existing) {
            // Var olan kullanıcı — Google bilgisini bağla, login et
            try {
                $user = DB::transaction(function () use ($email, $googleUser, $existing) {
                    return $this->updateExistingUser($existing, $googleUser);
                });
            } catch (\Throwable $e) {
                Log::error('Google OAuth user update failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return redirect('/login')->withErrors([
                    'email' => 'Giriş işleminde bir hata oluştu: ' . $e->getMessage(),
                ]);
            }

            if ($user->locked_until && now()->lt($user->locked_until)) {
                $minutesLeft = (int) now()->diffInMinutes($user->locked_until, false);
                return redirect('/login')->withErrors([
                    'email' => "Hesap geçici olarak kilitlendi. {$minutesLeft} dakika sonra tekrar deneyin.",
                ]);
            }

            Auth::login($user, true);
            $request->session()->regenerate();
            $request->session()->put('current_company_id', (int) ($user->company_id ?? 0));
            return app(AuthController::class)->redirectByRole();
        }

        // Yeni kullanıcı — rol seçimini sor (Aday vs Partner Bayi)
        $request->session()->put('google_pending', [
            'email'        => $email,
            'google_id'    => (string) $googleUser->getId(),
            'display_name' => trim((string) ($googleUser->getName() ?? '')),
            'created_at'   => now()->timestamp,
        ]);

        return redirect()->route('auth.google.choose-role');
    }

    public function showRoleChoice(Request $request)
    {
        $pending = $request->session()->get('google_pending');
        if (! is_array($pending) || empty($pending['email'])) {
            return redirect('/login')->withErrors([
                'email' => 'Google oturumu bulunamadı, tekrar deneyin.',
            ]);
        }

        // 1 saatten eski pending'ler geçersiz
        if (time() - (int) ($pending['created_at'] ?? 0) > 3600) {
            $request->session()->forget('google_pending');
            return redirect('/login')->withErrors([
                'email' => 'Oturum süresi doldu, Google ile tekrar giriş yapın.',
            ]);
        }

        return view('auth.google.choose-role', [
            'email'       => $pending['email'],
            'displayName' => $pending['display_name'] ?? '',
        ]);
    }

    public function submitRoleChoice(Request $request)
    {
        $pending = $request->session()->get('google_pending');
        if (! is_array($pending) || empty($pending['email'])) {
            return redirect('/login')->withErrors([
                'email' => 'Google oturumu bulunamadı, tekrar deneyin.',
            ]);
        }

        $data = $request->validate([
            'role' => ['required', 'in:guest,dealer'],
        ]);

        $email       = (string) $pending['email'];
        $googleId    = (string) ($pending['google_id'] ?? '');
        $displayName = trim((string) ($pending['display_name'] ?? '')) ?: (explode('@', $email)[0] ?: 'Google User');
        $firstName   = Str::of($displayName)->explode(' ')->first() ?: 'Guest';
        $lastName    = (string) Str::of($displayName)->explode(' ')->skip(1)->implode(' ');
        if ($lastName === '') $lastName = '-';

        if ($data['role'] === 'dealer') {
            // Partner intent — kullanıcı yaratma; başvuru formuna prefill ile yönlendir
            $request->session()->forget('google_pending');
            $request->session()->flash('dealer_prefill', [
                'first_name' => $firstName,
                'last_name'  => $lastName === '-' ? '' : $lastName,
                'email'      => $email,
            ]);
            return redirect()->route('public.dealer-application.create');
        }

        // role === 'guest' — aday öğrenci kullanıcısını oluştur ve login et
        try {
            $user = DB::transaction(function () use ($email, $displayName, $firstName, $lastName, $googleId) {
                return $this->createGuestUser($email, $displayName, $firstName, $lastName, $googleId);
            });
        } catch (\Throwable $e) {
            Log::error('Google OAuth guest user creation failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect('/login')->withErrors([
                'email' => 'Kayıt oluşturulurken bir hata oluştu: ' . $e->getMessage(),
            ]);
        }

        $request->session()->forget('google_pending');
        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('current_company_id', (int) ($user->company_id ?? 0));
        return app(AuthController::class)->redirectByRole();
    }

    protected function updateExistingUser(User $user, $googleUser): User
    {
        if ($user->trashed()) {
            $user->restore();
        }
        $updates = [];
        if (empty($user->google_id)) {
            $updates['google_id'] = (string) $googleUser->getId();
        }
        if (empty($user->email_verified_at)) {
            $updates['email_verified_at'] = now();
        }
        $updates['failed_login_attempts'] = 0;
        $updates['locked_until']          = null;
        if (!empty($updates)) {
            $user->forceFill($updates)->save();
        }
        return $user;
    }

    protected function createGuestUser(string $email, string $displayName, string $firstName, string $lastName, string $googleId): User
    {
        $user = User::create([
            'name'              => $displayName,
            'email'             => $email,
            'password'          => Hash::make(Str::random(40)),
            'role'              => User::ROLE_GUEST,
            'google_id'         => $googleId,
            'email_verified_at' => now(),
        ]);

        GuestApplication::firstOrCreate(
            ['email' => $email],
            [
                'guest_user_id'       => $user->id,
                'first_name'          => $firstName,
                'last_name'           => $lastName,
                'tracking_token'      => Str::upper(Str::random(12)),
                'status'              => 'new',
                'contract_status'     => 'not_requested',
                'application_type'    => 'bachelor',
                'application_country' => 'Türkiye',
            ]
        );

        return $user;
    }
}
