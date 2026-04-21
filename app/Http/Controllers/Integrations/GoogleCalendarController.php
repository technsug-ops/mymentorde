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
            'sync_push' => $request->boolean('sync_push'),
            'sync_pull' => $request->boolean('sync_pull'),
        ]);

        return back()->with('status', 'Senkronizasyon ayarları kaydedildi.');
    }

    /** Manuel pull — "Şimdi Senkronize Et" butonu. */
    public function manualPull(Request $request, \App\Services\GoogleCalendarService $service)
    {
        $user = Auth::user();
        $conn = GoogleCalendarConnection::where('user_id', $user->id)->first();
        if (! $conn) {
            return back()->with('status', 'Önce Google Calendar\'ı bağlayın.');
        }

        // Manuel pull için geçici olarak sync_pull'u true yapmadan da çalışsın
        if (! $conn->sync_pull) {
            $conn->update(['sync_pull' => true]);
        }

        try {
            $stats = $service->pullForConnection($conn);
            $msg = "Sync tamam: {$stats['processed']} event işlendi, {$stats['updated']} güncellendi, {$stats['cancelled']} iptal edildi.";
            if ($stats['errors'] > 0) {
                $msg .= " ⚠ {$stats['errors']} hata — log'a bakın.";
            }
            return back()->with('status', $msg);
        } catch (\Throwable $e) {
            return back()->with('status', '❌ Sync hatası: ' . $e->getMessage());
        }
    }
}
