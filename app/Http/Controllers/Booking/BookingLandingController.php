<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\MarketingAdminSetting;
use App\Models\SeniorBookingSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            return [
                'slug'          => $s->public_slug,
                'display_name'  => $s->display_name ?: ($u?->name ?? 'Danışman'),
                'welcome'       => $s->welcome_message,
                'slot_duration' => $s->slot_duration,
                'timezone'      => $s->timezone,
                'name'          => $u?->name,
                'photo_url'     => $u?->photo_url,
                'bio'           => $u?->bio,
                'expertise'     => is_array($u?->expertise_tags) ? $u->expertise_tags : [],
            ];
        })->values();

        return view('booking.public.landing', [
            'seniors' => $list,
            'landingCms' => $this->loadLandingCms(),
        ]);
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
