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
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
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

        // Find-or-create atomic: transaction + withoutGlobalScopes + withTrashed
        try {
            $user = DB::transaction(function () use ($email, $googleUser) {
                return $this->findOrCreateUser($email, $googleUser);
            });
        } catch (\Throwable $e) {
            Log::error('Google OAuth user provisioning failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect('/login')->withErrors([
                'email' => 'Kayıt oluşturulurken bir hata oluştu: ' . $e->getMessage(),
            ]);
        }

        // Hesap kilitli kontrolü
        if ($user->locked_until && now()->lt($user->locked_until)) {
            $minutesLeft = (int) now()->diffInMinutes($user->locked_until, false);
            return redirect('/login')->withErrors([
                'email' => "Hesap geçici olarak kilitlendi. {$minutesLeft} dakika sonra tekrar deneyin.",
            ]);
        }

        // Login + session regenerate
        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('current_company_id', (int) ($user->company_id ?? 0));

        return app(AuthController::class)->redirectByRole();
    }

    protected function findOrCreateUser(string $email, $googleUser): User
    {
        // Name parsing
        $displayName = trim((string) ($googleUser->getName() ?? '')) ?: (explode('@', $email)[0] ?: 'Google User');
        $firstName   = Str::of($displayName)->explode(' ')->first() ?: 'Guest';
        $lastName    = (string) Str::of($displayName)->explode(' ')->skip(1)->implode(' ');
        if ($lastName === '') $lastName = '-';

        // Soft-deleted ve tüm scope'lar dahil — duplicate key hatasını önle
        $user = User::withTrashed()->withoutGlobalScopes()->where('email', $email)->first();

        if ($user) {
            if ($user->trashed()) {
                $user->restore();
            }
            // Google bilgilerini user'a bağla (ilk kez login ise)
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
        } else {
            // Yeni guest kullanıcı oluştur
            $user = User::create([
                'name'              => $displayName,
                'email'             => $email,
                'password'          => Hash::make(Str::random(40)),
                'role'              => User::ROLE_GUEST,
                'google_id'         => (string) $googleUser->getId(),
                'email_verified_at' => now(),
            ]);
        }

        // Guest role user'lar için guest_application kaydı garanti et
        if ((string) $user->role === User::ROLE_GUEST) {
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
        }

        return $user;
    }
}
