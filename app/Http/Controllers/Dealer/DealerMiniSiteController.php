<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dealer\Concerns\DealerPortalTrait;
use App\Models\Dealer;
use App\Rules\ValidFileMagicBytes;
use App\Services\EventLogService;
use App\Services\NotificationService;
use App\Services\TaskAutomationService;
use App\Support\PartnerSiteSections;
use App\Support\PartnerTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Bayi mini-site ayarları (white-label). Bayi logo/renk/metinleri ve slug önerisini
 * düzenler; yayına alma (site_enabled) manager onayına bağlıdır.
 */
class DealerMiniSiteController extends Controller
{
    use DealerPortalTrait;

    /** Mini-site slug'ı olarak kullanılamayacak rezerve yollar. */
    private const RESERVED_SLUGS = [
        'admin', 'api', 'manager', 'dealer', 'apply', 'p', 'satis-ortagi', 'platform',
        'kayit', 'fiyatlar', 'pricing', 'login', 'randevu', 'uzman', 'go', 'promo',
        'share', 'brand', 'partner', 'signup', 'sss', 'uni-match',
    ];

    public function __construct(
        private readonly TaskAutomationService $taskAutomationService,
        private readonly EventLogService $eventLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function edit(Request $request): View
    {
        $data = $this->baseData($request);
        return view('dealer.mini-site.edit', $data);
    }

    public function update(Request $request): RedirectResponse
    {
        $data   = $this->baseData($request);
        $dealer = $data['dealer'];
        abort_if(!$dealer instanceof Dealer, 403, 'Dealer not found');

        $validated = $request->validate([
            'public_slug'        => ['nullable', 'string', 'min:3', 'max:64', 'regex:/^[a-z0-9-]+$/',
                                     Rule::unique('dealers', 'public_slug')->ignore($dealer->id)],
            'site_accent_color'  => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'site_hero_title'    => ['nullable', 'string', 'max:160'],
            'site_hero_subtitle' => ['nullable', 'string', 'max:300'],
            'site_about_text'    => ['nullable', 'string', 'max:4000'],
            'site_phone'         => ['nullable', 'string', 'max:50'],
            'site_whatsapp'      => ['nullable', 'string', 'max:50'],
            'site_instagram'     => ['nullable', 'string', 'max:100'],
            'logo'               => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', new ValidFileMagicBytes()],
            // Operasyon partner (b2b) çok-bölümlü site alanları — hepsi opsiyonel.
            'site_address'       => ['nullable', 'string', 'max:300'],
            'site_show_badge'    => ['nullable', 'boolean'],
            'site_template'      => ['nullable', Rule::in(array_keys(PartnerTemplates::all()))],
            'site_services'      => ['nullable', 'array', 'max:12'],
            'site_services.*.title' => ['nullable', 'string', 'max:120'],
            'site_services.*.desc'  => ['nullable', 'string', 'max:400'],
            'site_services.*.icon'  => ['nullable', 'string', 'max:32'],
            'site_services.*.items' => ['nullable', 'string', 'max:600'],
            'site_stats'         => ['nullable', 'array', 'max:8'],
            'site_stats.*.value' => ['nullable', 'string', 'max:40'],
            'site_stats.*.label' => ['nullable', 'string', 'max:60'],
            'site_team'          => ['nullable', 'array', 'max:12'],
            'site_team.*.name'   => ['nullable', 'string', 'max:80'],
            'site_team.*.title'  => ['nullable', 'string', 'max:80'],
            'site_team.*.photo'  => ['nullable', 'url', 'max:500'],
            // Öğrenci yorumları — partner yalnız GERÇEK yorumlarını girer, boşsa bölüm gizlenir.
            'site_testimonials'          => ['nullable', 'array', 'max:12'],
            'site_testimonials.*.text'   => ['nullable', 'string', 'max:600'],
            'site_testimonials.*.name'   => ['nullable', 'string', 'max:80'],
            'site_testimonials.*.school' => ['nullable', 'string', 'max:120'],
            // Destek paketleri — partner girmediyse bölüm gizlenir (default paket üretilmez).
            'site_packages'             => ['nullable', 'array', 'max:6'],
            'site_packages.*.name'      => ['nullable', 'string', 'max:60'],
            'site_packages.*.tag'       => ['nullable', 'string', 'max:40'],
            'site_packages.*.desc'      => ['nullable', 'string', 'max:400'],
            'site_packages.*.items'     => ['nullable', 'string', 'max:600'],
            'site_packages.*.featured'  => ['nullable', 'boolean'],
            'site_package_note'         => ['nullable', 'string', 'max:300'],
            // S.S.S. — boşsa Almanya süreci hakkında firmadan bağımsız default set gösterilir.
            'site_faq'                  => ['nullable', 'array', 'max:10'],
            'site_faq.*.q'              => ['nullable', 'string', 'max:200'],
            'site_faq.*.a'              => ['nullable', 'string', 'max:1000'],
            // Öğrencilerin yerleştiği üniversiteler (her satıra bir ad).
            'site_universities'         => ['nullable', 'string', 'max:600'],
            // Sayfa kurgusu: bölüm sırası + aç/kapa (sıra = dizideki sıra).
            'site_sections'             => ['nullable', 'array', 'max:30'],
            'site_sections.*.key'       => ['nullable', 'string', 'max:40'],
            'site_sections.*.on'        => ['nullable', 'boolean'],
        ]);

        // Slug rezerve kontrolü
        if (!empty($validated['public_slug']) && in_array($validated['public_slug'], self::RESERVED_SLUGS, true)) {
            return back()->withErrors(['public_slug' => 'Bu slug rezerve, başka bir tane seçin.'])->withInput();
        }

        $payload = [
            'public_slug'        => $validated['public_slug'] ?: $dealer->public_slug,
            'site_accent_color'  => $validated['site_accent_color'] ?? null,
            'site_hero_title'    => $validated['site_hero_title'] ?? null,
            'site_hero_subtitle' => $validated['site_hero_subtitle'] ?? null,
            'site_about_text'    => $validated['site_about_text'] ?? null,
            'site_phone'         => $validated['site_phone'] ?? null,
            'site_whatsapp'      => $validated['site_whatsapp'] ?? null,
            'site_instagram'     => $validated['site_instagram'] ?? null,
            'site_address'       => $validated['site_address'] ?? null,
            'site_show_badge'    => $request->boolean('site_show_badge'),
            'site_template'      => $validated['site_template'] ?? PartnerTemplates::DEFAULT,
            'site_services'      => $this->cleanCards($validated['site_services'] ?? null, ['title', 'desc', 'icon'], ['title', 'desc'], ['items']),
            'site_stats'         => $this->cleanCards($validated['site_stats'] ?? null, ['value', 'label'], ['value', 'label']),
            'site_team'          => $this->cleanCards($validated['site_team'] ?? null, ['name', 'title', 'photo'], ['name']),
            'site_testimonials'  => $this->cleanCards($validated['site_testimonials'] ?? null, ['text', 'name', 'school'], ['text']),
            'site_packages'      => $this->cleanCards($validated['site_packages'] ?? null, ['name', 'tag', 'desc', 'featured'], ['name'], ['items']),
            'site_package_note'  => $validated['site_package_note'] ?? null,
            'site_faq'           => $this->cleanCards($validated['site_faq'] ?? null, ['q', 'a'], ['q']),
            'site_universities'  => $this->cleanLines($validated['site_universities'] ?? null, 12),
            'site_sections'      => $this->cleanSections($validated['site_sections'] ?? null),
        ];

        if ($request->hasFile('logo')) {
            // Eski logoyu temizle
            if ($dealer->site_logo_path && Storage::disk('public')->exists($dealer->site_logo_path)) {
                Storage::disk('public')->delete($dealer->site_logo_path);
            }
            $path = $request->file('logo')->store('dealer-sites/' . Str::slug($dealer->code), 'public');
            $payload['site_logo_path'] = $path;
        }

        $dealer->update($payload);

        try {
            $this->eventLogService->log(
                'dealer_minisite_updated',
                'dealer',
                (string) $dealer->id,
                "Mini-site guncellendi: {$dealer->code}",
                ['slug' => $dealer->public_slug],
                $request->user()?->email,
                (int) $dealer->company_id,
            );
        } catch (\Throwable $e) {
        }

        $msg = 'Mini-site ayarların kaydedildi.';
        if (!$dealer->site_enabled) {
            $msg .= ' Yayına alınması için yönetici onayı gerekiyor.';
        }

        return redirect('/dealer/mini-site')->with('status', $msg);
    }

    /**
     * Bölüm kurgusunu (sıra + aç/kapa) temizle. Bilinmeyen/yinelenen key düşer;
     * eksik bölümler render sırasında `PartnerSiteSections::resolve()` tarafından
     * varsayılan sırayla sona eklenir. Hiç geçerli satır yoksa null (= varsayılan kurgu).
     *
     * @return list<array{key:string,on:bool}>|null
     */
    private function cleanSections($rows): ?array
    {
        if (!is_array($rows)) {
            return null;
        }
        $out  = [];
        $seen = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = isset($row['key']) && is_scalar($row['key']) ? trim((string) $row['key']) : '';
            if (!PartnerSiteSections::isValid($key) || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = ['key' => $key, 'on' => filter_var($row['on'] ?? false, FILTER_VALIDATE_BOOLEAN)];
        }
        return $out === [] ? null : $out;
    }

    /**
     * Satır satır girilen metni (textarea) temiz string listesine çevir.
     * Tamamı boşsa null → DB null → şablon o bölümü hiç basmaz.
     *
     * @return list<string>|null
     */
    private function cleanLines($raw, int $max): ?array
    {
        if (!is_string($raw)) {
            return null;
        }
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $v = trim($line);
            if ($v !== '') {
                $out[] = $v;
            }
            if (count($out) >= $max) {
                break;
            }
        }
        return $out === [] ? null : $out;
    }

    /**
     * Repeatable kart girişini temizle: her satırda sadece $keys'i tut (string'e çevir),
     * $requiredAny anahtarlarından en az biri doluysa satırı sakla. Tümü boşsa null döner
     * (DB null → view default içeriğine düşer).
     *
     * $listKeys: değeri newline'lı metin (veya dizi) olan → temiz string listesine çevrilir.
     *
     * @param  array<int,mixed>|null  $rows
     * @param  list<string>  $keys
     * @param  list<string>  $requiredAny
     * @param  list<string>  $listKeys
     * @return array<int,array<string,mixed>>|null
     */
    private function cleanCards($rows, array $keys, array $requiredAny, array $listKeys = []): ?array
    {
        if (!is_array($rows)) {
            return null;
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $card = [];
            foreach ($keys as $k) {
                $card[$k] = isset($row[$k]) && is_scalar($row[$k]) ? trim((string) $row[$k]) : '';
            }
            $keep = false;
            foreach ($requiredAny as $rk) {
                if (($card[$rk] ?? '') !== '') {
                    $keep = true;
                    break;
                }
            }
            if (!$keep) {
                continue;
            }
            foreach ($listKeys as $lk) {
                $raw = $row[$lk] ?? null;
                if (is_string($raw)) {
                    $raw = preg_split('/\r\n|\r|\n/', $raw) ?: [];
                }
                $list = [];
                if (is_array($raw)) {
                    foreach ($raw as $it) {
                        if (is_scalar($it) && trim((string) $it) !== '') {
                            $list[] = trim((string) $it);
                        }
                        if (count($list) >= 6) {
                            break;
                        }
                    }
                }
                $card[$lk] = $list;
            }
            $out[] = $card;
        }
        return $out === [] ? null : $out;
    }
}
