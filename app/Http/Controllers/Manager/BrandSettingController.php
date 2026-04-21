<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\MarketingAdminSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BrandSettingController extends Controller
{
    public function show()
    {
        $cid = auth()->user()?->company_id ?? 0;

        $brandName    = MarketingAdminSetting::where('company_id', $cid)
                            ->where('setting_key', 'brand_name')
                            ->value('setting_value') ?? config('brand.name', 'MentorDE');

        $brandLogoUrl = MarketingAdminSetting::where('company_id', $cid)
                            ->where('setting_key', 'brand_logo_url')
                            ->value('setting_value') ?? '';

        $brandLogoBg = MarketingAdminSetting::where('company_id', $cid)
                            ->where('setting_key', 'brand_logo_bg')
                            ->value('setting_value') ?? 'light';

        // Landing /randevu CMS alanları
        $landingVideoUrl = MarketingAdminSetting::where('company_id', $cid)
                            ->where('setting_key', 'landing_hero_video_url')
                            ->value('setting_value') ?? '';

        $landingWelcomeTitle = MarketingAdminSetting::where('company_id', $cid)
                            ->where('setting_key', 'landing_hero_welcome_title')
                            ->value('setting_value') ?? 'Hoş Geldin!';

        $landingWelcomeBody = MarketingAdminSetting::where('company_id', $cid)
                            ->where('setting_key', 'landing_hero_welcome_body')
                            ->value('setting_value') ?? '';

        return view('manager.brand', compact(
            'brandName', 'brandLogoUrl', 'brandLogoBg',
            'landingVideoUrl', 'landingWelcomeTitle', 'landingWelcomeBody'
        ));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'brand_name'                   => 'required|string|max:60',
            'brand_logo_url'               => 'nullable|url|max:500',
            'brand_logo_bg'                => 'nullable|in:light,dark,transparent',
            'landing_hero_video_url'       => 'nullable|url|max:500',
            'landing_hero_welcome_title'   => 'nullable|string|max:120',
            'landing_hero_welcome_body'    => 'nullable|string|max:2000',
        ]);

        $cid = auth()->user()?->company_id ?? 0;

        $settings = [
            'brand_name'                 => $data['brand_name'],
            'brand_logo_url'             => $data['brand_logo_url'] ?? '',
            'brand_logo_bg'              => $data['brand_logo_bg'] ?? 'light',
            'landing_hero_video_url'     => $this->normalizeVideoUrl($data['landing_hero_video_url'] ?? ''),
            'landing_hero_welcome_title' => $data['landing_hero_welcome_title'] ?? '',
            'landing_hero_welcome_body'  => $data['landing_hero_welcome_body'] ?? '',
        ];

        foreach ($settings as $key => $value) {
            MarketingAdminSetting::updateOrCreate(
                ['company_id' => $cid, 'setting_key' => $key],
                ['setting_value' => $value, 'updated_by_user_id' => auth()->id()]
            );
        }

        // Tüm portallardaki brand cache'ini temizle
        Cache::forget("brand_settings_{$cid}");
        Cache::forget("landing_cms_{$cid}");

        return back()->with('status', 'Marka ve landing ayarları kaydedildi.');
    }

    /**
     * YouTube/Vimeo watch URL'lerini iframe-uyumlu embed URL'lere çevir.
     * Kullanıcı "watch?v=" link'i yapıştırsa da otomatik embed'e dönüştürülür.
     *
     * Desteklenen formatlar:
     *   - youtube.com/watch?v=XXX       → youtube.com/embed/XXX
     *   - youtu.be/XXX                  → youtube.com/embed/XXX
     *   - youtube.com/embed/XXX         → aynen korunur
     *   - vimeo.com/12345               → player.vimeo.com/video/12345
     *   - player.vimeo.com/video/12345  → aynen korunur
     *   - Tanınmayan format             → aynen korunur (user'a kalmış)
     */
    private function normalizeVideoUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        // YouTube watch URL: https://www.youtube.com/watch?v=VIDEO_ID
        if (preg_match('~^https?://(?:www\.)?youtube\.com/watch\?.*v=([a-zA-Z0-9_-]{6,})~i', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        // YouTube short URL: https://youtu.be/VIDEO_ID
        if (preg_match('~^https?://youtu\.be/([a-zA-Z0-9_-]{6,})~i', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        // Vimeo watch URL: https://vimeo.com/12345
        if (preg_match('~^https?://(?:www\.)?vimeo\.com/(\d+)~i', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        // Zaten embed formatında ise dokunma
        return $url;
    }
}
