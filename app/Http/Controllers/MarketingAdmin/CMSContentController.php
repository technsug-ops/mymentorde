<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\LeadSourceDatum;
use App\Models\Marketing\CmsCategory;
use App\Models\Marketing\CmsContent;
use App\Models\Marketing\CmsContentRevision;
use App\Models\MarketingCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CMSContentController extends Controller
{
    /**
     * Eski inline-form'lu liste sayfası emekli edildi.
     * Yeni UX: /mktg-admin/content/overview (tablo + checkbox + bulk action),
     * edit/yarat ayrı sayfada (/mktg-admin/content/{id}/edit, /mktg-admin/content/create).
     */
    public function index(Request $request)
    {
        // Eski ?edit_id=X query bookmark'larını koru
        $editId = (int) $request->query('edit_id', 0);
        if ($editId > 0) {
            return redirect('/mktg-admin/content/'.$editId.'/edit');
        }
        return redirect('/mktg-admin/content/overview');
    }

    public function create()
    {
        return view('marketing-admin.content.edit', [
            'pageTitle' => 'Yeni İçerik',
            'isEdit' => false,
            'editing' => null,
            'statusOptions' => $this->statusOptions(),
            'typeOptions' => $this->typeOptions(),
            'categories' => CmsCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(['code', 'name_tr']),
            'campaignOptions' => MarketingCampaign::query()->orderByDesc('id')->limit(150)->get(['id', 'name']),
        ]);
    }

    /**
     * İçerik tablosu: ID kodu, kapak görseli, başlık, kategori, durum.
     * Blog ismine tıklanınca modal içinde detayları gösterir (preview endpoint).
     */
    public function overview(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => (string) $request->query('category', 'all'),
            'status' => (string) $request->query('status', 'all'),
        ];

        $query = CmsContent::query()->orderBy('content_code');
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q): void {
                $w->where('content_code', 'like', "%{$q}%")
                    ->orWhere('title_tr', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }
        if ($filters['category'] !== 'all') {
            $query->where('category', $filters['category']);
        }
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        $rows = $query->paginate(50)->withQueryString();
        $categoryCounts = CmsContent::query()
            ->selectRaw('category, COUNT(*) as cnt')
            ->groupBy('category')
            ->pluck('cnt', 'category')
            ->all();

        return view('marketing-admin.content.overview', [
            'pageTitle' => 'İçerik Tablosu',
            'title' => 'Tüm İçerikler — ID Kod Listesi',
            'rows' => $rows,
            'filters' => $filters,
            'statusOptions' => $this->statusOptions(),
            'categoryCounts' => $categoryCounts,
            'categoryPrefixes' => CmsContent::CATEGORY_CODE_PREFIXES,
        ]);
    }

    /**
     * Tablo'dan çoklu işlem: delete / publish / unpublish / feature / unfeature.
     */
    public function bulkAction(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', Rule::in(['delete', 'publish', 'unpublish', 'feature', 'unfeature'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:cms_contents,id'],
        ]);

        $userId = (int) $request->user()->id;
        $query = CmsContent::query()->whereIn('id', $data['ids']);
        $count = 0;

        switch ($data['action']) {
            case 'delete':
                $count = $query->delete();
                break;
            case 'publish':
                $count = $query->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'approved_by' => $userId,
                    'last_edited_by' => $userId,
                ]);
                break;
            case 'unpublish':
                $count = $query->update([
                    'status' => 'draft',
                    'published_at' => null,
                    'last_edited_by' => $userId,
                ]);
                break;
            case 'feature':
                $count = $query->update(['is_featured' => true, 'last_edited_by' => $userId]);
                break;
            case 'unfeature':
                $count = $query->update(['is_featured' => false, 'featured_order' => null, 'last_edited_by' => $userId]);
                break;
        }

        return response()->json(['ok' => true, 'count' => $count, 'action' => $data['action']]);
    }

    /**
     * AI Labs üretim — verilen topic/kategori/dil ile Gemini'den JSON formatlı
     * blog içeriği (title + summary + content_tr HTML) çek, form'a inject etmek
     * için JSON döndür. DB'ye YAZMAZ — kullanıcı manuel olarak form'u kaydeder.
     *
     * generateDraft'tan farkı: dry-run, direkt edit form'una bilgi enjekte eder.
     */
    public function aiGenerate(Request $request, \App\Services\AiLabs\GeminiProvider $gemini): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'topic' => ['required', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:120'],
            'language' => ['nullable', Rule::in(['tr', 'de', 'en'])],
            'tone' => ['nullable', Rule::in(['professional', 'casual', 'inspiring', 'academic'])],
            'target_audience' => ['nullable', 'string', 'max:200'],
            'word_count' => ['nullable', 'integer', 'min:200', 'max:1500'],
        ]);

        $lang = $data['language'] ?? 'tr';
        $tone = $data['tone'] ?? 'professional';
        $category = $data['category'] ?? 'blog';
        $audience = $data['target_audience'] ?? 'Almanya\'da öğrenim görmek isteyen Türk öğrenciler';
        $wordCount = $data['word_count'] ?? 600;
        $topic = trim((string) $data['topic']);

        // Kategori context — AI'ya hangi tarz içerik olduğunu netleştirir
        $catMeta = CmsContent::AI_CATEGORY_CONTEXTS[$category] ?? null;
        $categoryLabel = $catMeta['label'] ?? $category;
        $categoryContext = $catMeta['context'] ?? '';

        $companyId = (int) ($request->user()?->company_id ?? 1);
        if (!$gemini->isConfigured($companyId)) {
            return response()->json(['ok' => false, 'message' => 'Gemini API anahtarı yapılandırılmamış. Marketing Admin → AI Ayarları sayfasından key gir.'], 503);
        }

        $systemPrompt = <<<TXT
Sen MentorDE adlı Almanya üniversite başvuru danışmanlık platformu için içerik üreten bir editörsün.
Hedef kitle: {$audience}.
Tonlama: {$tone} (samimi-profesyonel arası, jargon yok, "siz" hitabı).
Format: HTML — sadece <h2>, <h3>, <p>, <ul>/<li>, <strong> kullan; başka tag yok.
Asla uydurma: kesin tarih, ücret, deadline iddia etme. Spesifik bilgi için "resmi siteden teyit edin" disclaimer'ı ekle.
ASLA <html>, <body>, <head> dahil etme — sadece içerik HTML'i.
Çıktı formatı KESİNLİKLE şu JSON yapısında olmalı (JSON dışı hiçbir metin yazma):
{"title_tr": "...", "summary_tr": "...", "content_tr": "..."}
TXT;

        $userPrompt = <<<TXT
Konu: {$topic}
Kategori: {$categoryLabel}
Kategori bağlamı: {$categoryContext}
Hedef kelime sayısı: ~{$wordCount}

Bu konu hakkında {$lang} dilinde, hedef kitleye yönelik ~{$wordCount} kelimelik kaliteli bir blog yazısı üret.

İçerik şu yapıda olsun:
- Giriş paragrafı (1 paragraf, konuya hızlı dalış)
- 3-5 ara başlık (<h2>) ile bölümlenmiş ana içerik
- Her bölümde 1-2 paragraf + gerekirse <ul>/<li> listesi
- Sonuç paragrafı (kapanış + MentorDE'nin yardımcı olabileceğine dair 1 cümle)

Başlık (title_tr): konuyu yansıtan, çekici, max 100 karakter.
Özet (summary_tr): 1-2 cümle, ~150 karakter, kart önyüzü için.
İçerik (content_tr): yukarıdaki HTML yapısı.

Spesifik tarih/ücret/deadline iddia etme. KESİNLİKLE JSON formatında dön, başka açıklama yazma.
TXT;

        $res = $gemini->chat($systemPrompt, $userPrompt, [], [
            'temperature' => 0.7,
            'max_output_tokens' => 4096,
            'response_mime_type' => 'application/json',
        ], $companyId);

        if (!($res['ok'] ?? false)) {
            return response()->json(['ok' => false, 'message' => 'Gemini hata: ' . ($res['error'] ?? 'unknown')], 502);
        }

        $raw = trim((string) ($res['content'] ?? ''));
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $raw);
        $parsed = json_decode($raw, true);
        if (!is_array($parsed) || empty($parsed['content_tr'])) {
            return response()->json(['ok' => false, 'message' => 'AI yanıtı parse edilemedi.'], 502);
        }

        return response()->json([
            'ok' => true,
            'title' => (string) ($parsed['title_tr'] ?? $parsed['title_de'] ?? $parsed['title_en'] ?? ''),
            'summary' => (string) ($parsed['summary_tr'] ?? $parsed['summary_de'] ?? $parsed['summary_en'] ?? ''),
            'content' => (string) ($parsed['content_tr'] ?? $parsed['content_de'] ?? $parsed['content_en'] ?? ''),
            'tokens' => (int) ($res['tokens_output'] ?? 0),
        ]);
    }

    /**
     * AI ile kapak görseli öner — Gemini'ye "bu içerik için Wikipedia'da hangi başlığı
     * arasak alakalı görsel gelir?" diye sor, dönen başlığı WikipediaImageFetcher'a ver.
     *
     * 2-adımlı akış (image generation YOK — Wikimedia Commons CC-licensed kullanır):
     *   Adım 1: Gemini → keyword (örn "Krankenversicherung", "Universität Heidelberg")
     *   Adım 2: WikipediaImageFetcher → URL + atıf
     */
    public function aiSuggestCover(
        Request $request,
        \App\Services\AiLabs\GeminiProvider $gemini,
        \App\Services\Marketing\WikipediaImageFetcher $imageFetcher
    ): \Illuminate\Http\JsonResponse {
        $data = $request->validate([
            'topic' => ['required', 'string', 'max:500'],
            'title' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
        ]);

        $companyId = (int) ($request->user()?->company_id ?? 1);
        if (!$gemini->isConfigured($companyId)) {
            return response()->json(['ok' => false, 'message' => 'Gemini API anahtarı yapılandırılmamış.'], 503);
        }

        $catLabel = CmsContent::AI_CATEGORY_CONTEXTS[$data['category'] ?? '']['label'] ?? '';
        $topic = trim((string) $data['topic']);
        $title = trim((string) ($data['title'] ?? ''));

        // Gemini'ye 3 alternatif keyword sor — fallback'li
        // ÖNEMLİ: Türkçe değil, gerçek Almanca/İngilizce Wikipedia makale başlıkları öner
        $systemPrompt = <<<'PROMPT'
Sen Wikipedia kapak görseli arama uzmanısın. Verilen Türkçe blog konusunu Wikipedia'da görsel arayacağımız 3 GERÇEK Almanca/İngilizce makale başlığına çevir.

KRİTİK KURALLAR:
1. Türkçe başlık ASLA önerme — Türkçe Wiki çoğu konuda zayıf, DE Wiki güçlü.
2. ASLA blog-tarzı uzun başlık ("Berlin Gece Hayatı Rehberi") — kavram/yer/kurum adı ver ("Nachtleben Berlin", "Berghain").
3. Tercih: 1-3 kelimelik sade kavram (Almanca tercih: "Oktoberfest", "Krankenversicherung", "Brandenburger Tor", "Maschinenbau", "Lebenslauf").
4. Spesifik kuruluş varsa tam Wikipedia adı: "Technische Universität München", "Universität Heidelberg", "Goethe-Universität Frankfurt am Main".
5. Strateji: keyword[0]=en spesifik, keyword[1]=orta genel, keyword[2]=güvenli/generic (kesin görsel gelir).

Çıktı KESİNLİKLE bu JSON: {"keywords":["DE_keyword_1","DE_keyword_2","DE_keyword_3"],"reason":"kısa açıklama"} — başka metin yok.

Örnekler:
- "Almanya öğrenci sağlık sigortası" → ["Krankenversicherung", "Krankenkasse", "Krankenhaus"]
- "Berlin gece hayatı" → ["Berghain", "Berliner Nachtleben", "Berlin"]
- "Almanya CV yazma" → ["Lebenslauf", "Bewerbung", "Bewerbungsmappe"]
- "Oktoberfest gezi rehberi" → ["Oktoberfest", "Theresienwiese", "München"]
- "RWTH Aachen başvurusu" → ["RWTH Aachen", "Aachen", "Hochschule"]
PROMPT;

        $userPrompt = "Konu: {$topic}"
            . ($title ? "\nBaşlık: {$title}" : '')
            . ($catLabel ? "\nKategori: {$catLabel}" : '')
            . "\n\nBu Türkçe konuyu, DE/EN Wikipedia'da kesin görsel gelecek 3 GERÇEK makale başlığına çevir.";

        $res = $gemini->chat($systemPrompt, $userPrompt, [], [
            'temperature' => 0.3,
            'max_output_tokens' => 512,
            'response_mime_type' => 'application/json',
        ], $companyId);

        if (!($res['ok'] ?? false)) {
            return response()->json(['ok' => false, 'message' => 'AI keyword önerisi alınamadı: ' . ($res['error'] ?? '?')], 502);
        }

        $raw = trim((string) ($res['content'] ?? ''));
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $raw);
        $parsed = json_decode($raw, true);
        $keywords = is_array($parsed) ? (array) ($parsed['keywords'] ?? []) : [];
        $reason = is_array($parsed) ? trim((string) ($parsed['reason'] ?? '')) : '';

        if (empty($keywords)) {
            return response()->json(['ok' => false, 'message' => 'AI uygun keyword öneremedi.'], 502);
        }

        // Sırayla Wikipedia'da ara — ilk tutan kazanır
        $tried = [];
        foreach ($keywords as $kw) {
            $kw = trim((string) $kw);
            if ($kw === '') continue;
            $tried[] = $kw;
            $fetchResult = $imageFetcher->fetch($kw);
            if ($fetchResult['ok'] ?? false) {
                return response()->json([
                    'ok' => true,
                    'url' => $fetchResult['url'],
                    'path' => $fetchResult['path'],
                    'attribution' => $fetchResult['attribution'] ?? null,
                    'suggested_keyword' => $kw,
                    'reason' => $reason,
                    'lang' => $fetchResult['lang'] ?? null,
                    'tried_count' => count($tried),
                ]);
            }
        }

        return response()->json([
            'ok' => false,
            'message' => 'AI önerdi ama hiçbirinde Wiki görseli yok: ' . implode(' / ', $tried),
            'tried_keywords' => $tried,
        ], 404);
    }

    /**
     * AI ile SEO alanları öner — Gemini'ye mevcut title + summary + content'i ver,
     * Google SEO için optimize meta_title, meta_description, keywords ve tags üretsin.
     * Tek tıkla form'a inject edilir.
     */
    public function aiSuggestSeo(
        Request $request,
        \App\Services\AiLabs\GeminiProvider $gemini
    ): \Illuminate\Http\JsonResponse {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:120'],
            'language' => ['nullable', Rule::in(['tr', 'de', 'en'])],
        ]);

        $companyId = (int) ($request->user()?->company_id ?? 1);
        if (!$gemini->isConfigured($companyId)) {
            return response()->json(['ok' => false, 'message' => 'Gemini API anahtarı yapılandırılmamış.'], 503);
        }

        $title = trim((string) $data['title']);
        $summary = trim((string) ($data['summary'] ?? ''));
        $contentText = trim(strip_tags((string) ($data['content'] ?? '')));
        $contentText = mb_substr($contentText, 0, 2000); // Token limit için kısalt
        $lang = $data['language'] ?? 'tr';
        $catLabel = CmsContent::AI_CATEGORY_CONTEXTS[$data['category'] ?? '']['label'] ?? '';

        $systemPrompt = <<<'PROMPT'
Sen SEO uzmanısın. Verilen blog için Google arama sonuçlarında ÖNE ÇIKACAK SEO meta alanları öner.

KURALLAR:
- seo_meta_title: Max 60 karakter, anahtar kelime öne, çekici. Çok uzunsa kes.
- seo_meta_description: Max 160 karakter, içeriği özetler + tıklamaya teşvik eden bir CTA cümlesi. Açıklayıcı ve net.
- seo_keywords: 5-8 anahtar kelime (DİL HEDEF KİTLEYE GÖRE — TR içerik için Türkçe + bazı Almanca terim), arama hacmi yüksek olanlar.
- tags: 4-6 etiket, SEO'ya değil içerik kategorizasyonuna yönelik (tema, hedef kitle, lokasyon).

Çıktı KESİNLİKLE bu JSON: {"seo_meta_title":"...","seo_meta_description":"...","seo_keywords":["...","..."],"tags":["...","..."]}
PROMPT;

        $userPrompt = "Hedef dil: {$lang}\n"
            . ($catLabel ? "Kategori: {$catLabel}\n" : '')
            . "Başlık: {$title}\n"
            . ($summary ? "Özet: {$summary}\n" : '')
            . ($contentText ? "İçerik özü:\n{$contentText}\n" : '')
            . "\nBu blog için SEO optimize seo_meta_title, seo_meta_description, seo_keywords (5-8 adet) ve tags (4-6 adet) öner.";

        $res = $gemini->chat($systemPrompt, $userPrompt, [], [
            'temperature' => 0.4,
            'max_output_tokens' => 1024,
            'response_mime_type' => 'application/json',
        ], $companyId);

        if (!($res['ok'] ?? false)) {
            return response()->json(['ok' => false, 'message' => 'AI SEO önerisi alınamadı: ' . ($res['error'] ?? '?')], 502);
        }

        $raw = trim((string) ($res['content'] ?? ''));
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $raw);
        $parsed = json_decode($raw, true);
        if (!is_array($parsed)) {
            return response()->json(['ok' => false, 'message' => 'AI yanıtı parse edilemedi.'], 502);
        }

        $keywords = (array) ($parsed['seo_keywords'] ?? []);
        $tags = (array) ($parsed['tags'] ?? []);

        return response()->json([
            'ok' => true,
            'seo_meta_title' => (string) ($parsed['seo_meta_title'] ?? ''),
            'seo_meta_description' => (string) ($parsed['seo_meta_description'] ?? ''),
            'seo_keywords' => array_values(array_filter(array_map(fn ($k) => trim((string) $k), $keywords))),
            'tags' => array_values(array_filter(array_map(fn ($t) => trim((string) $t), $tags))),
            'tokens' => (int) ($res['tokens_output'] ?? 0),
        ]);
    }

    /**
     * Tek veya toplu içerikleri PDF olarak export et.
     *
     * Kullanım:
     *   GET  /content/{id}/pdf         → tek içerik
     *   POST /content/bulk-pdf {ids[]} → toplu (checkbox seçimi)
     *
     * DomPDF asset() dış URL'leri çekemediği için cover image'leri lokal
     * storage path'inden okuyup base64-data URI olarak gömüyoruz.
     */
    public function exportPdf(Request $request, ?string $id = null)
    {
        // Tek mi toplu mu?
        if ($id !== null) {
            $rows = CmsContent::query()->whereKey($id)->get();
            $filename = 'mentorde-icerik-' . ($rows->first()->content_code ?? $id) . '.pdf';
        } else {
            $data = $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', 'exists:cms_contents,id'],
            ]);
            $rows = CmsContent::query()->whereIn('id', $data['ids'])->orderBy('content_code')->get();
            $filename = 'mentorde-icerikler-' . now()->format('Ymd-Hi') . '-' . $rows->count() . '.pdf';
        }

        if ($rows->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'İçerik bulunamadı.'], 404);
        }

        // Cover image'leri lokal path'e çevir — DomPDF base64-data URI'ları otomatik ekler
        $localCoverPaths = [];
        foreach ($rows as $row) {
            $url = (string) ($row->cover_image_url ?? '');
            if ($url === '') continue;
            // /storage/ ile başlayan veya tam URL — public/ içine map et
            $localPath = $this->resolveLocalAssetPath($url);
            if ($localPath && file_exists($localPath)) {
                $localCoverPaths[$row->id] = $localPath;
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('marketing-admin.content.pdf', [
            'rows' => $rows,
            'embedImages' => true,
            'localCoverPaths' => $localCoverPaths,
        ])->setPaper('a4');

        return $pdf->download($filename);
    }

    /**
     * cover_image_url (asset URL) → lokal disk path. DomPDF için.
     */
    private function resolveLocalAssetPath(string $url): ?string
    {
        // asset('storage/cms-covers/x.jpg') → http://localhost/storage/cms-covers/x.jpg
        $parsed = parse_url($url, PHP_URL_PATH);
        if (!$parsed) return null;
        // /storage/cms-covers/x.jpg → storage/app/public/cms-covers/x.jpg
        if (str_starts_with($parsed, '/storage/')) {
            $rel = substr($parsed, strlen('/storage/'));
            $local = storage_path('app/public/' . $rel);
            return file_exists($local) ? $local : null;
        }
        // public/ içindeki diğer asset'ler
        $publicPath = public_path(ltrim($parsed, '/'));
        return file_exists($publicPath) ? $publicPath : null;
    }

    /**
     * Status chip'ine tıklayınca tek satır durum değiştirme (publish/draft toggle).
     */
    public function toggleStatus(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in($this->statusOptions())],
        ]);
        $row = CmsContent::query()->findOrFail($id);
        $row->status = $data['status'];
        $row->last_edited_by = (int) $request->user()->id;
        if ($data['status'] === 'published') {
            $row->published_at = $row->published_at ?? now();
            $row->approved_by = (int) $request->user()->id;
        }
        $row->save();
        return response()->json(['ok' => true, 'id' => $row->id, 'status' => $row->status]);
    }

    /**
     * Tek içerik detayını JSON döner — overview tablosunda modal'da render edilir.
     */
    public function preview(string $id): \Illuminate\Http\JsonResponse
    {
        $row = CmsContent::query()->findOrFail($id);
        return response()->json([
            'ok' => true,
            'item' => [
                'id' => $row->id,
                'content_code' => $row->content_code,
                'slug' => $row->slug,
                'type' => $row->type,
                'category' => $row->category,
                'status' => $row->status,
                'title_tr' => $row->title_tr,
                'summary_tr' => $row->summary_tr,
                'content_tr' => $row->content_tr,
                'cover_image_url' => $row->cover_image_url,
                'cover_image_alt' => $row->cover_image_alt,
                'is_featured' => (bool) $row->is_featured,
                'published_at' => $row->published_at?->format('Y-m-d H:i'),
                'created_at' => $row->created_at?->format('Y-m-d H:i'),
                'updated_at' => $row->updated_at?->format('Y-m-d H:i'),
                'metric_total_views' => (int) $row->metric_total_views,
                'author_name' => $row->author_name,
                'author_role' => $row->author_role,
                'edit_url' => url('/mktg-admin/content/' . $row->id . '/edit'),
            ],
        ]);
    }

    /**
     * Cover image upload — başarı hikayeleri vb. için gerçek müşteri foto'sunu
     * yükler, public/storage'a kaydedilir, URL JSON olarak döner.
     * Frontend bu URL'i cover_image_url input'una yazar.
     */
    public function uploadCover(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5 MB
        ]);

        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension());
        $name = 'cms-cover-' . now()->format('Ymd-His') . '-' . \Illuminate\Support\Str::random(6) . '.' . $ext;
        $path = $file->storeAs('cms-covers', $name, 'public');

        return response()->json([
            'ok'  => true,
            'url' => asset('storage/' . $path),
            'path' => $path,
        ]);
    }

    /**
     * Wikipedia'dan üniversite kapak görseli çek — DE wiki öncelikli, TR/EN fallback.
     * Atıf zorunluluğu (CC-BY-SA): cover_image_alt input'una yazılan atfı manager
     * gerekirse manuel düzenleyebilir.
     */
    public function fetchUniversityImage(Request $request, \App\Services\Marketing\WikipediaImageFetcher $fetcher): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'university_name' => ['required', 'string', 'max:200'],
        ]);
        $result = $fetcher->fetch(trim((string) $data['university_name']));
        $status = $result['ok'] ? Response::HTTP_OK : (str_contains($result['message'] ?? '', 'indirilemedi') ? Response::HTTP_BAD_GATEWAY : Response::HTTP_NOT_FOUND);
        return response()->json($result, $status);
    }

    private function sanitizeBody(string $body): string
    {
        // Script tag'larini ve inline event handler'lari temizle (HTML korunsun)
        $body = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $body);
        $body = preg_replace('/\son\w+\s*=\s*["\'][^"\']*["\']/i', '', (string) $body);
        return (string) $body;
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request, true);
        $userId = (int) $request->user()->id;

        // Auto-generate slug from title_tr if not provided
        if (empty($data['slug'])) {
            $base = Str::slug($data['title_tr']);
            $slug = $base;
            $i = 2;
            while (CmsContent::query()->where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $data['slug'] = $slug;
        }

        $row = CmsContent::query()->create([
            'type' => $data['type'],
            'slug' => $data['slug'],
            'title_tr' => $data['title_tr'],
            'title_de' => Arr::get($data, 'title_de'),
            'title_en' => Arr::get($data, 'title_en'),
            'summary_tr' => Arr::get($data, 'summary_tr'),
            'summary_de' => Arr::get($data, 'summary_de'),
            'summary_en' => Arr::get($data, 'summary_en'),
            'content_tr' => $this->sanitizeBody($data['content_tr']),
            'content_de' => Arr::get($data, 'content_de'),
            'content_en' => Arr::get($data, 'content_en'),
            'cover_image_url' => Arr::get($data, 'cover_image_url'),
            'cover_image_alt' => Arr::get($data, 'cover_image_alt'),
            'gallery_urls' => $this->normalizeCsv($request->input('gallery_urls', '')),
            'video_url' => Arr::get($data, 'video_url'),
            'video_thumbnail_url' => Arr::get($data, 'video_thumbnail_url'),
            'seo_meta_title_tr' => Arr::get($data, 'seo_meta_title_tr'),
            'seo_meta_description_tr' => Arr::get($data, 'seo_meta_description_tr'),
            'seo_keywords' => $this->normalizeCsv($request->input('seo_keywords', '')),
            'seo_canonical_url' => Arr::get($data, 'seo_canonical_url'),
            'seo_og_image_url' => Arr::get($data, 'seo_og_image_url'),
            'status' => Arr::get($data, 'status', 'draft'),
            'published_at' => Arr::get($data, 'status') === 'published' ? now() : null,
            'scheduled_at' => Arr::get($data, 'scheduled_at'),
            'is_featured' => $request->boolean('is_featured', false),
            'featured_order' => Arr::get($data, 'featured_order'),
            'target_audience' => Arr::get($data, 'target_audience', 'all'),
            'target_student_types' => $this->normalizeCsv($request->input('target_student_types', '')),
            'linked_campaign_id' => Arr::get($data, 'linked_campaign_id'),
            'category' => Arr::get($data, 'category'),
            'tags' => $this->normalizeCsv($request->input('tags', '')),
            'author_name' => Arr::get($data, 'author_name'),
            'author_role' => Arr::get($data, 'author_role'),
            'current_revision' => 1,
            'created_by' => $userId,
            'last_edited_by' => $userId,
            'approved_by' => Arr::get($data, 'status') === 'published' ? $userId : null,
        ]);

        $this->createRevision($row, $userId, (string) ($data['change_note'] ?? 'initial create'));

        return $this->responseFor($request, ['ok' => true, 'id' => $row->id], 'Icerik olusturuldu.', Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        return redirect('/mktg-admin/content/'.$id.'/edit');
    }

    public function edit(string $id)
    {
        $editing = CmsContent::query()->findOrFail($id);
        return view('marketing-admin.content.edit', [
            'pageTitle' => 'İçerik Düzenle',
            'isEdit' => true,
            'editing' => $editing,
            'statusOptions' => $this->statusOptions(),
            'typeOptions' => $this->typeOptions(),
            'categories' => CmsCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(['code', 'name_tr']),
            'campaignOptions' => MarketingCampaign::query()->orderByDesc('id')->limit(150)->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $row = CmsContent::query()->findOrFail($id);
        $data = $this->validatePayload($request, false, $row->id);
        $payload = array_filter([
            'type' => Arr::get($data, 'type'),
            'slug' => Arr::get($data, 'slug'),
            'title_tr' => Arr::get($data, 'title_tr'),
            'title_de' => Arr::get($data, 'title_de'),
            'title_en' => Arr::get($data, 'title_en'),
            'summary_tr' => Arr::get($data, 'summary_tr'),
            'summary_de' => Arr::get($data, 'summary_de'),
            'summary_en' => Arr::get($data, 'summary_en'),
            'content_tr' => isset($data['content_tr']) ? $this->sanitizeBody($data['content_tr']) : null,
            'content_de' => Arr::get($data, 'content_de'),
            'content_en' => Arr::get($data, 'content_en'),
            'cover_image_url' => Arr::get($data, 'cover_image_url'),
            'cover_image_alt' => Arr::get($data, 'cover_image_alt'),
            'video_url' => Arr::get($data, 'video_url'),
            'video_thumbnail_url' => Arr::get($data, 'video_thumbnail_url'),
            'seo_meta_title_tr' => Arr::get($data, 'seo_meta_title_tr'),
            'seo_meta_description_tr' => Arr::get($data, 'seo_meta_description_tr'),
            'seo_canonical_url' => Arr::get($data, 'seo_canonical_url'),
            'seo_og_image_url' => Arr::get($data, 'seo_og_image_url'),
            'status' => Arr::get($data, 'status'),
            'scheduled_at' => Arr::get($data, 'scheduled_at'),
            'featured_order' => Arr::get($data, 'featured_order'),
            'target_audience' => Arr::get($data, 'target_audience'),
            'linked_campaign_id' => Arr::get($data, 'linked_campaign_id'),
            'category' => Arr::get($data, 'category'),
            'author_name' => Arr::get($data, 'author_name'),
            'author_role' => Arr::get($data, 'author_role'),
        ], fn ($v) => $v !== null);

        if ($request->has('is_featured')) {
            $payload['is_featured'] = $request->boolean('is_featured');
        }
        if ($request->has('gallery_urls')) {
            $payload['gallery_urls'] = $this->normalizeCsv($request->input('gallery_urls', ''));
        }
        if ($request->has('seo_keywords')) {
            $payload['seo_keywords'] = $this->normalizeCsv($request->input('seo_keywords', ''));
        }
        if ($request->has('target_student_types')) {
            $payload['target_student_types'] = $this->normalizeCsv($request->input('target_student_types', ''));
        }
        if ($request->has('tags')) {
            $payload['tags'] = $this->normalizeCsv($request->input('tags', ''));
        }
        if (($payload['status'] ?? null) === 'published') {
            $payload['published_at'] = now();
            $payload['approved_by'] = (int) $request->user()->id;
        }

        $payload['last_edited_by'] = (int) $request->user()->id;

        if ($payload !== []) {
            $row->fill($payload);
            $row->current_revision = (int) $row->current_revision + 1;
            $row->save();
            $this->createRevision($row, (int) $request->user()->id, (string) ($data['change_note'] ?? 'manual update'));
        }

        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Icerik guncellendi.');
    }

    public function destroy(Request $request, string $id)
    {
        $row = CmsContent::query()->findOrFail($id);
        $row->delete();
        return $this->responseFor($request, ['ok' => true, 'id' => $id], 'Icerik silindi.');
    }

    public function publish(Request $request, string $id)
    {
        $row = CmsContent::query()->findOrFail($id);
        $row->update([
            'status' => 'published',
            'published_at' => now(),
            'approved_by' => (int) $request->user()->id,
        ]);
        return $this->responseFor($request, ['ok' => true, 'id' => $id, 'status' => 'published'], 'Icerik publish edildi.');
    }

    public function unpublish(Request $request, string $id)
    {
        $row = CmsContent::query()->findOrFail($id);
        $row->update([
            'status' => 'draft',
            'published_at' => null,
            'approved_by' => null,
        ]);
        return $this->responseFor($request, ['ok' => true, 'id' => $id, 'status' => 'draft'], 'Icerik drafta cekildi.');
    }

    public function schedule(Request $request, string $id)
    {
        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
        ]);
        $row = CmsContent::query()->findOrFail($id);
        $row->update([
            'status' => 'scheduled',
            'scheduled_at' => Carbon::parse((string) $data['scheduled_at']),
        ]);
        return $this->responseFor($request, ['ok' => true, 'id' => $id, 'status' => 'scheduled'], 'Icerik schedule edildi.');
    }

    public function toggleFeatured(Request $request, string $id)
    {
        $row = CmsContent::query()->findOrFail($id);
        $featured = !((bool) $row->is_featured);
        $row->update([
            'is_featured' => $featured,
            'featured_order' => $featured ? ((int) ($request->input('featured_order') ?: ($row->featured_order ?: 999))) : null,
        ]);
        return $this->responseFor($request, ['ok' => true, 'id' => $id, 'featured' => $featured], $featured ? 'Featured yapildi.' : 'Featured kaldirildi.');
    }

    public function stats(string $id)
    {
        $row = CmsContent::query()->findOrFail($id);
        $leadCount = (int) LeadSourceDatum::query()->where('cms_content_id', $row->id)->count();
        $converted = (int) LeadSourceDatum::query()->where('cms_content_id', $row->id)->where('funnel_converted', true)->count();
        $conversionRate = $leadCount > 0 ? round(($converted / $leadCount) * 100, 2) : 0;

        return view('marketing-admin.content.stats', [
            'pageTitle' => 'Icerik Istatistikleri',
            'title' => 'Icerik #'.$id.' istatistikleri',
            'content' => $row,
            'summary' => [
                'views' => (int) $row->metric_total_views,
                'unique_views' => (int) $row->metric_unique_views,
                'avg_read' => (int) $row->metric_avg_read_time_seconds,
                'bounce' => (float) $row->metric_bounce_rate,
                'shares' => (int) $row->metric_shares,
                'lead_count' => $leadCount,
                'lead_converted' => $converted,
                'lead_conversion_rate' => $conversionRate,
            ],
        ]);
    }

    public function revisions(string $id)
    {
        $row = CmsContent::query()->findOrFail($id);
        $revisions = CmsContentRevision::query()
            ->where('cms_content_id', $row->id)
            ->orderByDesc('revision_number')
            ->paginate(20);

        return view('marketing-admin.content.revisions', [
            'pageTitle' => 'Revizyon Gecmisi',
            'title' => 'Icerik #'.$id.' revizyonlari',
            'content' => $row,
            'revisions' => $revisions,
        ]);
    }

    private function validatePayload(Request $request, bool $isCreate, ?int $currentId = null): array
    {
        $rules = [
            'type' => [$isCreate ? 'required' : 'sometimes', Rule::in($this->typeOptions())],
            'slug' => array_filter([
                'nullable',
                'string',
                'max:190',
                Rule::unique('cms_contents', 'slug')->ignore($currentId),
            ]),
            'title_tr' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'title_de' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'summary_tr' => ['nullable', 'string'],
            'summary_de' => ['nullable', 'string'],
            'summary_en' => ['nullable', 'string'],
            'content_tr' => [$isCreate ? 'required' : 'sometimes', 'string'],
            'content_de' => ['nullable', 'string'],
            'content_en' => ['nullable', 'string'],
            'cover_image_url' => ['nullable', 'string', 'max:500'],
            'cover_image_alt' => ['nullable', 'string', 'max:190'],
            'gallery_urls' => ['nullable', 'string'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'video_thumbnail_url' => ['nullable', 'string', 'max:500'],
            'seo_meta_title_tr' => ['nullable', 'string', 'max:255'],
            'seo_meta_description_tr' => ['nullable', 'string', 'max:300'],
            'seo_keywords' => ['nullable', 'string'],
            'seo_canonical_url' => ['nullable', 'string', 'max:500'],
            'seo_og_image_url' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in($this->statusOptions())],
            'scheduled_at' => ['nullable', 'date'],
            'is_featured' => ['nullable'],
            'featured_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'target_audience' => ['nullable', 'string', 'max:80'],
            'target_student_types' => ['nullable', 'string'],
            'linked_campaign_id' => ['nullable', 'integer', 'exists:marketing_campaigns,id'],
            'category' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'string'],
            'author_name' => ['nullable', 'string', 'max:120'],
            'author_role' => ['nullable', 'string', 'max:80'],
            'change_note' => ['nullable', 'string', 'max:255'],
        ];

        return $request->validate($rules);
    }

    private function normalizeCsv(mixed $raw): array
    {
        $txt = trim((string) $raw);
        if ($txt === '') {
            return [];
        }
        return collect(explode(',', $txt))
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function createRevision(CmsContent $row, int $editorId, string $changeNote = ''): void
    {
        CmsContentRevision::query()->create([
            'cms_content_id' => $row->id,
            'revision_number' => (int) $row->current_revision,
            'edited_by' => $editorId,
            'change_note' => trim($changeNote) !== '' ? trim($changeNote) : 'manual update',
            'snapshot_data' => [
                'title_tr' => $row->title_tr,
                'summary_tr' => $row->summary_tr,
                'content_tr' => $row->content_tr,
                'status' => $row->status,
                'category' => $row->category,
                'tags' => $row->tags,
            ],
            'created_at' => now(),
        ]);
    }

    private function typeOptions(): array
    {
        return ['blog', 'landing', 'guide', 'faq', 'event', 'video_feature', 'podcast', 'presentation', 'experience', 'career_guide', 'tip'];
    }

    private function statusOptions(): array
    {
        return ['draft', 'published', 'scheduled', 'archived'];
    }

    private function responseFor(Request $request, array $payload, string $statusMessage, int $statusCode = Response::HTTP_OK)
    {
        if ($request->expectsJson()) {
            return response()->json($payload, $statusCode);
        }
        // Yeni içerikse edit sayfasına yönlendir; aksi takdirde tabloya
        if (!empty($payload['id']) && $statusCode === Response::HTTP_CREATED) {
            return redirect('/mktg-admin/content/'.$payload['id'].'/edit')->with('status', $statusMessage);
        }
        return redirect('/mktg-admin/content/overview')->with('status', $statusMessage);
    }
}
