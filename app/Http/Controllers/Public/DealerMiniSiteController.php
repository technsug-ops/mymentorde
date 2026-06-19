<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Support\DealerLandingData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Bayi white-label mini-site — public /p/{slug}.
 * dealer-landing.blade.php'yi bayinin markası (logo/renk/hero) ile render eder.
 * CTA'lar /apply/partner/{code}'a gider → lead o bayiye etiketlenir.
 */
class DealerMiniSiteController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $dealer = Dealer::query()
            ->where('public_slug', $slug)
            ->where('is_active', true)
            ->where('is_archived', false)
            ->first();

        abort_if(!$dealer, 404);

        // Yayında değilse yalnız sahibi/manager ?preview=1 ile görebilir.
        if (!$dealer->site_enabled && !$request->boolean('preview')) {
            abort(404);
        }

        $logoUrl = $dealer->site_logo_path ? Storage::disk('public')->url($dealer->site_logo_path) : null;

        return view('public.dealer-landing', [
            'counters'      => DealerLandingData::counters(),
            'managerAccent' => $dealer->site_accent_color ?: '#1e40af',
            'accentColor'   => $dealer->site_accent_color ?: null,
            'brandName'     => $dealer->name,
            'brandLogoUrl'  => $logoUrl,
            'heroTitle'     => $dealer->site_hero_title ?: null,
            'heroSubtitle'  => $dealer->site_hero_subtitle ?: null,
            'aboutText'     => $dealer->site_about_text ?: null,
            'applyUrl'      => route('apply.partner', $dealer->code),
        ]);
    }
}
