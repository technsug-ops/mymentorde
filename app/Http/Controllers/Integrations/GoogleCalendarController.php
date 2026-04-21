<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\GoogleCalendarConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * Google Calendar OAuth bağlantı akışı — login'den ayrı, calendar scope'lu.
 *
 * Routes:
 *  GET  /integrations/google-calendar/connect    → Google consent
 *  GET  /integrations/google-calendar/callback   → token kaydı
 *  POST /integrations/google-calendar/disconnect → bağlantıyı kaldır
 *  POST /integrations/google-calendar/toggle     → push/pull aç-kapat
 */
class GoogleCalendarController extends Controller
{
    private const SCOPES = [
        'openid',
        'profile',
        'email',
        'https://www.googleapis.com/auth/calendar',
        'https://www.googleapis.com/auth/calendar.events',
    ];

    public function connect()
    {
        return Socialite::driver('google')
            ->scopes(self::SCOPES)
            ->with([
                'access_type' => 'offline',   // refresh_token için şart
                'prompt'      => 'consent',   // refresh_token garanti
            ])
            ->redirectUrl(route('integrations.google-calendar.callback'))
            ->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(route('integrations.google-calendar.callback'))
                ->user();
        } catch (\Throwable $e) {
            Log::error('Google Calendar OAuth callback failed', [
                'error' => $e->getMessage(),
            ]);
            return redirect(route('senior.settings'))
                ->with('status', 'Google Calendar bağlanırken hata: ' . $e->getMessage());
        }

        $user = Auth::user();
        if (! $user) {
            return redirect('/login')->withErrors(['email' => 'Oturum açmanız gerekiyor.']);
        }

        GoogleCalendarConnection::updateOrCreate(
            ['user_id' => $user->id],
            [
                'google_email'     => $googleUser->getEmail(),
                'google_user_id'   => $googleUser->getId(),
                'access_token'     => $googleUser->token,
                'refresh_token'    => $googleUser->refreshToken ?: GoogleCalendarConnection::where('user_id', $user->id)->value('refresh_token'),
                'expires_at'       => $googleUser->expiresIn ? now()->addSeconds((int) $googleUser->expiresIn) : null,
                'scope'            => is_array($googleUser->approvedScopes ?? null)
                    ? implode(' ', $googleUser->approvedScopes)
                    : null,
                'calendar_id'      => 'primary',
                'sync_push'        => true,
                'sync_pull'        => false,
                'last_sync_status' => 'pending',
            ]
        );

        return redirect(route('senior.settings'))
            ->with('status', '✅ Google Calendar başarıyla bağlandı: ' . $googleUser->getEmail());
    }

    public function disconnect(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            GoogleCalendarConnection::where('user_id', $user->id)->delete();
        }
        return back()->with('status', 'Google Calendar bağlantısı kaldırıldı.');
    }

    public function toggle(Request $request)
    {
        $user = Auth::user();
        $conn = GoogleCalendarConnection::where('user_id', $user->id)->first();
        if (! $conn) {
            return back()->with('status', 'Önce Google Calendar\'ı bağlayın.');
        }

        $conn->update([
            'sync_push' => (bool) $request->boolean('sync_push', $conn->sync_push),
        ]);

        return back()->with('status', 'Senkronizasyon ayarları kaydedildi.');
    }
}
