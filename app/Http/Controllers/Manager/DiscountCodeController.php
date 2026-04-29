<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\DiscountCodeRedemption;
use App\Services\AiWritingService;
use App\Services\EventLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Manager — indirim kodu CRUD + kullanım listesi.
 *
 * MVP sadece: kod, % veya sabit EUR, expiry, max use, max use/kişi, aktif/pasif.
 * Future kolonları (paket-spesifik, dealer attribution, min tutar) DB'de var
 * ama bu controller'ın UI'sında görünmez — sonraki sprint açılır.
 */
class DiscountCodeController extends Controller
{
    public function __construct(
        private readonly EventLogService $eventLogService,
    ) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status', 'all'); // all|active|inactive

        $query = DiscountCode::query()->orderByDesc('id');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('code', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%");
            });
        }
        if ($status === 'active')   $query->where('is_active', true);
        if ($status === 'inactive') $query->where('is_active', false);

        return view('manager.discount-codes.index', [
            'codes'  => $query->paginate(30)->appends($request->query()),
            'q'      => $q,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('manager.discount-codes.form', [
            'mode' => 'create',
            'code' => new DiscountCode(['discount_type' => 'percent', 'max_per_user' => 1, 'is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $companyId = (int) (Auth::user()?->company_id ?? 0);

        $exists = DiscountCode::query()
            ->where('company_id', $companyId)
            ->whereRaw('UPPER(code) = ?', [strtoupper($data['code'])])
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['code' => 'Bu kod zaten mevcut.']);
        }

        $code = DiscountCode::create(array_merge($data, [
            'company_id' => $companyId,
            'created_by' => Auth::id(),
        ]));

        $this->eventLogService->log(
            eventType: 'discount_code_created',
            entityType: 'discount_code',
            entityId: (string) $code->id,
            message: 'Manager #' . (Auth::id() ?? '?') . ' indirim kodu oluşturdu: ' . $code->code,
            meta: ['type' => $code->discount_type, 'value' => (float) $code->discount_value],
        );

        return redirect()->route('manager.discount-codes.index')->with('success', 'Kod oluşturuldu.');
    }

    public function edit(DiscountCode $discountCode): View
    {
        return view('manager.discount-codes.form', [
            'mode' => 'edit',
            'code' => $discountCode,
        ]);
    }

    public function update(Request $request, DiscountCode $discountCode): RedirectResponse
    {
        $data = $this->validatePayload($request, $discountCode);

        // Kod değişimine izin ver ama aynı şirkette unique kalsın
        $exists = DiscountCode::query()
            ->where('company_id', $discountCode->company_id)
            ->where('id', '!=', $discountCode->id)
            ->whereRaw('UPPER(code) = ?', [strtoupper($data['code'])])
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['code' => 'Bu kod başka bir kayıtta var.']);
        }

        $discountCode->update($data);

        return redirect()->route('manager.discount-codes.index')->with('success', 'Kod güncellendi.');
    }

    public function toggleActive(DiscountCode $discountCode): RedirectResponse
    {
        $discountCode->update(['is_active' => ! $discountCode->is_active]);
        return back()->with('success', $discountCode->is_active ? 'Kod aktif edildi.' : 'Kod pasif edildi.');
    }

    /**
     * AI ile paylaşım kartı metinlerini öner (4 alan: title, subtitle, cta, disclaimer).
     * Form'daki current state'e göre context kurar — tone template'e göre değişir.
     */
    public function aiSuggest(Request $request, AiWritingService $ai): JsonResponse
    {
        $data = $request->validate([
            'code'           => 'nullable|string|max:64',
            'description'    => 'nullable|string|max:255',
            'discount_type'  => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0',
            'template_id'    => 'nullable|integer|min:1|max:5',
            'valid_until'    => 'nullable|date',
        ]);

        if (! $ai->isAvailable()) {
            return response()->json([
                'ok'    => false,
                'error' => 'AI yazıcı yapılandırılmamış. Marketing Admin → AI Asistan ayarlarından provider + API key girilmesi gerekiyor.',
            ], 422);
        }

        $tplId = (int) ($data['template_id'] ?? 1);
        $tone  = $this->toneForTemplate($tplId);

        $discountText = $data['discount_type'] === 'percent'
            ? '%' . rtrim(rtrim(number_format((float) $data['discount_value'], 2, '.', ''), '0'), '.') . ' indirim'
            : number_format((float) $data['discount_value'], 0, ',', '.') . ' EUR sabit indirim';

        $brandName = config('brand.name', 'MentorDE');
        $tagline   = config('brand.tagline', 'Almanya Eğitim Danışmanlığı');

        $systemPrompt = <<<TXT
Sen {$brandName} ({$tagline}) için pazarlama metni yazan bir uzman copywriter'sın.
Hedef kitle: Türkiye'den Almanya'da üniversite okumak isteyen genç adaylar (18–28 yaş).
Yazım dili: TÜRKÇE. Doğal, akıcı, samimi ama kalitesiz değil.

Tone for this kart: {$tone}

Bu indirim kuponu için 4 metin oluştur:
- title: Hero başlık. Dikkat çekici, max 60 karakter. Gerekirse 1 emoji.
- subtitle: Açıklayıcı alt başlık. Max 200 karakter, 1-2 cümle.
- cta: Buton metni. Eylemsel (hadi/başla/yararlan tarzı), max 25 karakter.
- disclaimer: Küçük disclaimer. Max 150 karakter. "Kullanım koşulları geçerlidir, X tarihine kadar..." tarzı.

KURAL:
- Başlık her seferinde aynı klişe olmasın ("Sana özel" gibi). Yaratıcı ol.
- Almanya/üniversite/öğrenci yolculuğu temasını kullan.
- Yapay/abartı reklam dilinden kaçın ("inanılmaz fırsat!" gibi). Doğal kal.
- "Sen" hitabı kullan (resmi "siz" değil).
- {$brandName} markasından bahsetme metnin içinde — kart üstünde zaten görünüyor.

ÇIKTI: SADECE geçerli JSON. ` veya markdown code fence YOK. Başka açıklama YOK.
Format:
{"title": "...", "subtitle": "...", "cta": "...", "disclaimer": "..."}
TXT;

        $userParts = [
            "İndirim: {$discountText}",
        ];
        if (! empty($data['description'])) {
            $userParts[] = "Manager iç notu: " . $data['description'];
        }
        if (! empty($data['valid_until'])) {
            $userParts[] = "Son kullanma: " . \Carbon\Carbon::parse($data['valid_until'])->format('d.m.Y');
        }
        if (! empty($data['code'])) {
            $userParts[] = "Kod: " . strtoupper($data['code']);
        }

        $result = $ai->chat($systemPrompt, implode("\n", $userParts), maxTokens: 600);

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'ok'    => false,
                'error' => 'AI çağrısı başarısız: ' . ($result['error'] ?? 'unknown'),
            ], 502);
        }

        $content = (string) ($result['content'] ?? '');
        $parsed  = $this->extractJson($content);

        if (! $parsed) {
            return response()->json([
                'ok'    => false,
                'error' => 'AI yanıtı parse edilemedi. Yeniden dene.',
                'raw'   => $content,
            ], 502);
        }

        return response()->json([
            'ok'         => true,
            'title'      => (string) ($parsed['title'] ?? ''),
            'subtitle'   => (string) ($parsed['subtitle'] ?? ''),
            'cta'        => (string) ($parsed['cta'] ?? ''),
            'disclaimer' => (string) ($parsed['disclaimer'] ?? ''),
            'provider'   => $result['provider'] ?? null,
            'model'      => $result['model'] ?? null,
        ]);
    }

    public function redemptions(Request $request): View
    {
        $codeId = $request->query('code_id');
        $query = DiscountCodeRedemption::query()
            ->with(['discountCode', 'guestApplication'])
            ->orderByDesc('redeemed_at');

        if ($codeId) {
            $query->where('discount_code_id', (int) $codeId);
        }

        return view('manager.discount-codes.redemptions', [
            'redemptions' => $query->paginate(50)->appends($request->query()),
            'filteredCode' => $codeId ? DiscountCode::find($codeId) : null,
        ]);
    }

    /**
     * Template'a göre AI'a tone talimatı.
     */
    private function toneForTemplate(int $tplId): string
    {
        return match ($tplId) {
            2 => 'Canlı, genç, enerjik. Emoji kullan. Heyecanlı kelimeler.',
            3 => 'Lüks, ayrıcalıklı, sofistike. Az emoji. Şık, hafif resmi ama soğuk değil.',
            4 => 'Eğlenceli, samimi, oyuncu. Bol emoji. Genç-arkadaş gibi konuşur.',
            5 => 'Aciliyet hissi. "Kaçırma", "sadece X gün", "son fırsat" tarzı. Direkt eylem çağrısı.',
            default => 'Profesyonel ama sıcak. 1-2 emoji. Güven veren, net, sade.',
        };
    }

    /**
     * AI bazen ```json ... ``` markdown wrap'i ekleyebilir veya başına/sonuna text koyabilir.
     * İlk { ile son } arasını alıp parse et.
     */
    private function extractJson(string $raw): ?array
    {
        $start = strpos($raw, '{');
        $end   = strrpos($raw, '}');
        if ($start === false || $end === false || $end <= $start) return null;

        $candidate = substr($raw, $start, $end - $start + 1);
        $decoded = json_decode($candidate, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array<string,mixed>
     */
    private function validatePayload(Request $request, ?DiscountCode $existing = null): array
    {
        $data = $request->validate([
            'code'               => 'required|string|min:3|max:64|regex:/^[A-Za-z0-9_\-]+$/',
            'description'        => 'nullable|string|max:255',
            'discount_type'      => 'required|in:percent,fixed',
            'discount_value'     => 'required|numeric|min:0',
            'max_redemptions'    => 'nullable|integer|min:1',
            'max_per_user'       => 'required|integer|min:1|max:100',
            'valid_from'         => 'nullable|date',
            'valid_until'        => 'nullable|date|after_or_equal:valid_from',
            'is_active'          => 'sometimes|boolean',
            // Paylaşım kartı
            'template_id'        => 'nullable|integer|min:1|max:5',
            'landing_title'      => 'nullable|string|max:255',
            'landing_subtitle'   => 'nullable|string|max:500',
            'landing_cta_text'   => 'nullable|string|max:120',
            'landing_disclaimer' => 'nullable|string|max:1000',
        ]);

        // Yüzde sınırı
        if ($data['discount_type'] === 'percent' && (float) $data['discount_value'] > 100) {
            abort(422, 'Yüzde indirim 100\'den büyük olamaz.');
        }

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }
}
