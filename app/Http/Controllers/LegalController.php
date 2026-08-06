<?php

namespace App\Http\Controllers;

use App\Models\PolicyDocument;
use Illuminate\Http\Request;

/**
 * Public yasal sayfalar — manager paneli üzerinden düzenlenen
 * policy_documents içeriğini render eder.
 *
 * Locale: ?lang=tr|de|en query veya browser Accept-Language'den seçilir.
 * Şu an için company resolution single-tenant; multi-tenant'a geçince
 * subdomain/path-based lookup eklenecek.
 */
class LegalController extends Controller
{
    public function privacy(Request $request)   { return $this->render($request, PolicyDocument::KIND_PRIVACY); }
    public function cookies(Request $request)   { return $this->render($request, PolicyDocument::KIND_COOKIE); }
    public function terms(Request $request)     { return $this->render($request, PolicyDocument::KIND_TERMS); }
    public function imprint(Request $request)   { return $this->render($request, PolicyDocument::KIND_IMPRINT); }

    private function render(Request $request, string $kind)
    {
        $locale = $this->resolveLocale($request);
        $companyId = $this->resolveCompanyId();

        $row = PolicyDocument::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('kind', $kind)
            ->where('locale', $locale)
            ->first();

        // Seçilen dilde kayıt yoksa diğer dillerden fallback (TR → DE → EN)
        if (!$row || trim((string) $row->body) === '') {
            $row = PolicyDocument::query()
                ->withoutGlobalScope('company')
                ->where('company_id', $companyId)
                ->where('kind', $kind)
                ->whereNotNull('body')
                ->whereRaw('LENGTH(body) > 0')
                // FIELD() MySQL'e özgü — SQLite'ta yok ve sorgu patlıyordu.
                // Sonuç: hukuki sayfalar canlıda çalışıyor ama TEST EDİLEMİYORDU.
                // CASE WHEN her sürücüde aynı sırayı verir.
                ->orderByRaw("CASE locale WHEN 'tr' THEN 1 WHEN 'de' THEN 2 WHEN 'en' THEN 3 ELSE 4 END")
                ->first();
        }

        return view('legal.show', [
            'kind'           => $kind,
            'locale'         => $row?->locale ?? $locale,
            'requestedLocale'=> $locale,
            'title'          => $row?->title ?? $this->fallbackTitle($kind, $locale),
            'body'           => (string) ($row?->body ?? ''),
            'updatedAt'      => $row?->updated_at,
            'meta'           => $this->kindMeta($kind),
        ]);
    }

    private function resolveLocale(Request $request): string
    {
        $valid = PolicyDocument::LOCALES;

        $req = strtolower((string) $request->query('lang', ''));
        if (in_array($req, $valid, true)) return $req;

        // Cookie'den (kullanıcının daha önceki tercihi)
        $cookie = strtolower((string) $request->cookie('legal_locale', ''));
        if (in_array($cookie, $valid, true)) return $cookie;

        // Accept-Language header
        $accept = strtolower(substr((string) $request->header('Accept-Language', 'tr'), 0, 2));
        if (in_array($accept, $valid, true)) return $accept;

        return 'tr';
    }

    private function resolveCompanyId(): int
    {
        if (app()->bound('current_company_id')) {
            return (int) app('current_company_id');
        }
        return 1; // single-tenant default
    }

    /**
     * @return array{label_tr:string,label_de:string,label_en:string,emoji:string}
     */
    private function kindMeta(string $kind): array
    {
        return match ($kind) {
            'privacy' => ['emoji' => '📜', 'label_tr' => 'Gizlilik / KVKK', 'label_de' => 'Datenschutzerklärung', 'label_en' => 'Privacy Policy'],
            'cookie'  => ['emoji' => '🍪', 'label_tr' => 'Çerez Politikası', 'label_de' => 'Cookie-Richtlinie', 'label_en' => 'Cookie Policy'],
            'terms'   => ['emoji' => '📋', 'label_tr' => 'Kullanım Şartları', 'label_de' => 'AGB', 'label_en' => 'Terms of Use'],
            'imprint' => ['emoji' => '🏛️', 'label_tr' => 'Künye', 'label_de' => 'Impressum', 'label_en' => 'Imprint'],
            default   => ['emoji' => '📄', 'label_tr' => 'Yasal', 'label_de' => 'Legal', 'label_en' => 'Legal'],
        };
    }

    private function fallbackTitle(string $kind, string $locale): string
    {
        $meta = $this->kindMeta($kind);
        return $meta['label_' . $locale] ?? $meta['label_tr'];
    }
}
