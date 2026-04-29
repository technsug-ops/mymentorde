<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\BookingWaitlistRequest;
use App\Models\MarketingAdminSetting;
use App\Models\SeniorBookingSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Public booking landing — /randevu
 *   - Tüm public senior'ları listele (is_public=true & is_active=true)
 *   - Seçilen senior'un widget sayfasına yönlendir (/book/{slug})
 */
class BookingLandingController extends Controller
{
    public function index(Request $request): View
    {
        // Sadece is_public=true olan senior'lar bu listede
        $settings = SeniorBookingSetting::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->where('is_public', true)
            ->whereNotNull('public_slug')
            ->orderBy('display_name')
            ->get();

        $seniorIds = $settings->pluck('senior_user_id')->all();
        $seniors = User::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $seniorIds)
            ->get(['id', 'name', 'email', 'bio', 'photo_url', 'expertise_tags', 'senior_type'])
            ->keyBy('id');

        $list = $settings->map(function (SeniorBookingSetting $s) use ($seniors) {
            $u = $seniors->get($s->senior_user_id);
            $expertise = is_array($u?->expertise_tags) ? array_map('strval', $u->expertise_tags) : [];
            return [
                'slug'          => $s->public_slug,
                'display_name'  => $s->display_name ?: ($u?->name ?? 'Danışman'),
                'welcome'       => $s->welcome_message,
                'slot_duration' => $s->slot_duration,
                'timezone'      => $s->timezone,
                'name'          => $u?->name,
                'photo_url'     => $u?->photo_url,
                'bio'           => $u?->bio,
                'expertise'     => $expertise,
                // 3-kategori track çözümü: tags'tan derive
                // 'bachelor' / 'master' tag'i varsa ilgili kategori, yoksa 'other'
                'tracks'        => $this->resolveTracks($expertise),
            ];
        })->values();

        // 3 kategoriye ayır (bir senior birden fazla track'te görünebilir)
        $byTrack = [
            'bachelor' => $list->filter(fn ($s) => in_array('bachelor', $s['tracks'], true))->values(),
            'master'   => $list->filter(fn ($s) => in_array('master', $s['tracks'], true))->values(),
            'other'    => $list->filter(fn ($s) => in_array('other', $s['tracks'], true))->values(),
        ];

        return view('booking.public.landing', [
            'seniors'    => $list,
            'byTrack'    => $byTrack,
            'landingCms' => $this->loadLandingCms(),
        ]);
    }

    /**
     * Senior'ın expertise_tags'inden 3 kategoriye uygun track listesi türetir.
     * - 'bachelor' tag → ['bachelor']
     * - 'master' tag → ['master']
     * - İkisi birden → ['bachelor', 'master']
     * - Hiçbiri yoksa → ['other'] (genel danışman varsayar)
     *
     * @param  array<int,string>  $tags
     * @return array<int,string>
     */
    private function resolveTracks(array $tags): array
    {
        $normalized = array_map(fn ($t) => strtolower(trim((string) $t)), $tags);
        $tracks = [];
        if (in_array('bachelor', $normalized, true) || in_array('lisans', $normalized, true)) $tracks[] = 'bachelor';
        if (in_array('master', $normalized, true) || in_array('yuksek_lisans', $normalized, true) || in_array('yüksek_lisans', $normalized, true)) $tracks[] = 'master';
        if (empty($tracks)) $tracks[] = 'other';
        return $tracks;
    }

    /**
     * Bekleme listesi formu — kategori için uygun senior yoksa kullanıcı buraya bilgi bırakır.
     */
    public function joinWaitlist(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'    => 'required|string|max:120',
            'email'   => 'required|email|max:180',
            'phone'   => 'nullable|string|max:32',
            'track'   => 'required|in:bachelor,master,other',
            'message' => 'nullable|string|max:1000',
        ]);

        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        try {
            BookingWaitlistRequest::create($data + [
                'company_id'   => $companyId,
                'status'       => 'new',
                'utm_source'   => $request->cookie('utm_source'),
                'utm_medium'   => $request->cookie('utm_medium'),
                'utm_campaign' => $request->cookie('utm_campaign'),
                'referrer_url' => substr((string) $request->headers->get('referer', ''), 0, 500),
                'ip_address'   => $request->ip(),
                'user_agent'   => substr((string) $request->userAgent(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            Log::warning('booking.waitlist.failed', ['error' => $e->getMessage(), 'email' => $data['email']]);
            return back()->withErrors(['waitlist' => 'Kaydın oluşturulamadı, lütfen tekrar dene.'])->withInput();
        }

        return back()->with('waitlist_success', 'Kaydın alındı! Müsait danışman olduğunda email/telefon ile sana ulaşacağız.');
    }

    /**
     * @return array{video_url:string, welcome_title:string, welcome_body:string}
     */
    private function loadLandingCms(): array
    {
        $cid = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        return Cache::remember("landing_cms_{$cid}", 300, function () use ($cid): array {
            $rows = MarketingAdminSetting::query()
                ->withoutGlobalScopes()
                ->where('company_id', $cid)
                ->whereIn('setting_key', [
                    'landing_hero_video_url',
                    'landing_hero_welcome_title',
                    'landing_hero_welcome_body',
                ])
                ->pluck('setting_value', 'setting_key')
                ->map(fn ($v) => (string) $v)
                ->all();

            return [
                'video_url'     => $this->normalizeVideoUrl((string) ($rows['landing_hero_video_url'] ?? '')),
                'welcome_title' => (string) ($rows['landing_hero_welcome_title'] ?? 'Hoş geldin! 👋'),
                'welcome_body'  => (string) ($rows['landing_hero_welcome_body'] ?? 'Almanya\'ya üniversite başvurusu yapmayı düşünüyorsan doğru yerdesin. Uzman danışmanlarımızla birebir görüşerek süreç hakkında tüm sorularına yanıt bulabilirsin. Randevu almak tamamen ücretsiz.'),
            ];
        });
    }

    /** Watch URL'lerini iframe-uyumlu embed formatına çevir (render-time safety net). */
    private function normalizeVideoUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';
        if (preg_match('~^https?://(?:www\.)?youtube\.com/watch\?.*v=([a-zA-Z0-9_-]{6,})~i', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        if (preg_match('~^https?://youtu\.be/([a-zA-Z0-9_-]{6,})~i', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        if (preg_match('~^https?://(?:www\.)?vimeo\.com/(\d+)~i', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }
        return $url;
    }
}
