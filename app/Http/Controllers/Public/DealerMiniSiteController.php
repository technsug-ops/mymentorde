<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Support\DealerLandingData;
use App\Support\PartnerSiteData;
use App\Support\PartnerTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Bayi white-label mini-site — public /p/{slug}.
 *
 * İki katman (Partner Frontend F1):
 *  • Operasyon partner (b2b_partner) → çok-bölümlü öğrenci-lead sitesi (partner-site.blade).
 *  • Diğer tier'lar (freelance / lead_generation) → mevcut tek-sayfa mini-site (dealer-landing).
 *
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

        // ── Operasyon partner: seçtiği template ile çok-bölümlü öğrenci-odaklı site ──
        if ($this->isOperationPartner($dealer)) {
            // Sahibi/manager önizlemede ?tpl=KEY ile diğer template'leri deneyebilir.
            $tplKey = $dealer->site_template;
            if ($request->boolean('preview') && PartnerTemplates::isValid($request->query('tpl'))) {
                $tplKey = $request->query('tpl');
            }
            return view(PartnerTemplates::view($tplKey), PartnerSiteData::forDealer($dealer, $logoUrl));
        }

        // ── Diğer tier'lar: mevcut tek-sayfa mini-site (recruiting landing) ──
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

    /** Primary tier b2b_partner mı? (rol setinde b2b varsa da say.) */
    private function isOperationPartner(Dealer $dealer): bool
    {
        return $dealer->dealer_type_code === 'b2b_partner'
            || $dealer->hasRole(Dealer::ROLE_B2B_PARTNER);
    }
}
