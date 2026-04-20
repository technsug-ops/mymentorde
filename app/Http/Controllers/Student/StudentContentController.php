<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\StudentPortalTrait;
use App\Models\KnowledgeBaseArticle;
use App\Models\Marketing\CmsContent;
use App\Models\StudentMaterialRead;
use App\Models\UserContentReaction;
use App\Models\UserSavedContent;
use App\Services\CurrencyRateService;
use App\Support\SchemaCache;
use Illuminate\Http\Request;

class StudentContentController extends Controller
{
    use StudentPortalTrait;

    // ── Materials & Knowledge Base ───────────────────────────────────────────

    public function materials(Request $request)
    {
        $base      = $this->baseData($request, 'materials', 'Materyaller', 'Senior tarafindan paylasilan rehber, kontrol listesi ve dokumanlar.');
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $studentId = (string) ($base['studentId'] ?? '');
        $catFilter = $request->query('cat', '');

        $query = KnowledgeBaseArticle::query()
            ->when($companyId > 0 && SchemaCache::hasColumn('knowledge_base_articles', 'company_id'), fn ($q) => $q->where('company_id', $companyId))
            ->where('is_published', true)
            ->when($catFilter !== '', fn ($q) => $q->where('category', $catFilter))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(150);

        $articles = $query->get([
            'id', 'title_tr', 'title_de', 'title_en',
            'body_tr', 'body_de', 'body_en',
            'category', 'tags', 'target_roles', 'author_id',
            'media_type', 'source_url', 'file_path', 'original_filename',
            'updated_at',
        ]);

        $allCategories = KnowledgeBaseArticle::query()
            ->when($companyId > 0 && SchemaCache::hasColumn('knowledge_base_articles', 'company_id'), fn ($q) => $q->where('company_id', $companyId))
            ->where('is_published', true)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $readIds = $studentId !== ''
            ? StudentMaterialRead::query()
                ->where('student_id', $studentId)
                ->pluck('knowledge_base_article_id')
                ->map(fn ($v) => (int) $v)
                ->all()
            : [];

        return view('student.materials', array_merge($base, [
            'materials'       => $articles,
            'readMaterialIds' => $readIds,
            'categories'      => $allCategories,
            'activeCat'       => $catFilter,
        ]));
    }

    public function materialFile(KnowledgeBaseArticle $article, Request $request)
    {
        abort_unless($article->is_published && $article->file_path && \Storage::disk('local')->exists($article->file_path), 404);
        $inline   = $request->query('download') !== '1';
        $filename = $article->original_filename ?? basename($article->file_path);

        return response()->file(
            storage_path('app/' . $article->file_path),
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => ($inline ? 'inline' : 'attachment') . '; filename="' . $filename . '"',
            ]
        );
    }

    // ── Info / Static Pages ──────────────────────────────────────────────────

    public function infoUniversityGuide(Request $request)
    {
        return view('student.info.university-guide', $this->baseData($request, 'info', 'Almanya Üniversite Rehberi', ''));
    }

    public function infoSuccessStories(Request $request)
    {
        $base       = $this->baseData($request, 'info', 'Başarı Hikayeleri', '');
        $cmsStories = CmsContent::query()
            ->where('category', 'success-stories')
            ->where('status', 'published')
            ->orderBy('featured_order')
            ->orderByDesc('published_at')
            ->get(['id', 'slug', 'title_tr', 'summary_tr', 'content_tr', 'cover_image_alt', 'cover_image_url', 'tags', 'is_featured', 'video_url', 'video_thumbnail_url']);

        $heroVideo = $cmsStories->where('is_featured', true)->whereNotNull('video_url')->first()
            ?? $cmsStories->whereNotNull('video_url')->first();
        $heroId = $heroVideo?->id;
        $cmsVideos = $cmsStories
            ->whereNotNull('video_url')
            ->when($heroId, fn ($c) => $c->where('id', '!=', $heroId))
            ->take(3)->values();

        return view('student.info.success-stories', array_merge($base, [
            'cmsStories' => $cmsStories,
            'heroVideo'  => $heroVideo,
            'cmsVideos'  => $cmsVideos,
        ]));
    }

    public function infoLivingGuide(Request $request)
    {
        $base = $this->baseData($request, 'info', 'Almanya\'da Yaşam Rehberi', '');
        $base['cities']     = config('cost_calculator.cities', []);
        $base['eurTryRate'] = app(CurrencyRateService::class)->getRate('EUR', 'TRY');
        return view('student.info.living-guide', $base);
    }

    public function infoDocumentGuide(Request $request)
    {
        return view('student.info.document-guide', $this->baseData($request, 'info', 'Belge Hazırlık Kılavuzu', ''));
    }

    public function infoVizeGuide(Request $request)
    {
        $base              = $this->baseData($request, 'info', 'Vize & Sperrkonto Rehberi', '');
        $base['eurTryRate'] = app(\App\Services\CurrencyRateService::class)->getRate('EUR', 'TRY');

        return view('student.info.vize-guide', $base);
    }

    // ── Content Hub (Keşfet) ─────────────────────────────────────────────────

    public function discoverPage(Request $request)
    {
        $base     = $this->baseData($request, 'discover', 'Keşfet', 'İçerikler, rehberler ve daha fazlası.');
        $category = $request->get('cat');
        $type     = $request->get('type');
        $search   = trim((string) $request->get('q', ''));

        $query = CmsContent::query()
            ->where('status', 'published')
            ->where(function ($q) {
                $q->where('target_audience', 'all')->orWhere('target_audience', 'students');
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('featured_order')
            ->orderByDesc('published_at');

        if ($category) $query->where('category', $category);
        if ($type)     $query->where('type', $type);
        if ($search)   $query->where('title_tr', 'like', '%' . $search . '%');

        $items    = $query->paginate(12);
        $featured = CmsContent::where('is_featured', true)->where('status', 'published')
            ->where(fn ($q) => $q->where('target_audience', 'all')->orWhere('target_audience', 'students'))
            ->orderByDesc('featured_order')->limit(3)->get();
        $popular  = CmsContent::where('status', 'published')
            ->where(fn ($q) => $q->where('target_audience', 'all')->orWhere('target_audience', 'students'))
            ->orderByDesc('metric_total_views')->limit(5)->get(['id', 'slug', 'title_tr', 'type', 'category']);

        return view('student.discover', array_merge($base, [
            'items'    => $items,
            'featured' => $featured,
            'popular'  => $popular,
            'cat'      => $category,
            'type'     => $type,
            'search'   => $search,
        ]));
    }

    public function discoverMore(Request $request): \Illuminate\Http\JsonResponse
    {
        $category = $request->get('cat');
        $type     = $request->get('type');

        $query = CmsContent::query()->where('status', 'published')
            ->where(fn ($q) => $q->where('target_audience', 'all')->orWhere('target_audience', 'students'))
            ->orderByDesc('published_at');

        if ($category) $query->where('category', $category);
        if ($type)     $query->where('type', $type);

        $items = $query->paginate(12);

        return response()->json([
            'ok'        => true,
            'items'     => $items->items(),
            'next_page' => $items->nextPageUrl(),
        ]);
    }

    public function contentDetail(Request $request, string $slug)
    {
        $item = CmsContent::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $item->incrementViews(unique: true);

        if (!$item->metric_avg_read_time_seconds && $item->content_tr) {
            $words = str_word_count(strip_tags($item->content_tr));
            $item->metric_avg_read_time_seconds = max(60, (int) round($words / 200) * 60);
        }

        $related  = CmsContent::where('status', 'published')->where('category', $item->category)->where('id', '!=', $item->id)->orderByDesc('published_at')->limit(4)->get();
        $prevItem = CmsContent::where('status', 'published')->where('category', $item->category)->where('published_at', '<', $item->published_at)->orderByDesc('published_at')->first(['slug', 'title_tr', 'type']);
        $nextItem = CmsContent::where('status', 'published')->where('category', $item->category)->where('published_at', '>', $item->published_at)->orderBy('published_at')->first(['slug', 'title_tr', 'type']);

        $userId = auth()->id();
        $base   = $this->baseData($request, 'discover', 'İçerik', '');

        return view('student.content-detail', array_merge($base, [
            'item'      => $item,
            'related'   => $related,
            'prevItem'  => $prevItem,
            'nextItem'  => $nextItem,
            'isSaved'   => $userId ? UserSavedContent::where('user_id', $userId)->where('cms_content_id', $item->id)->exists() : false,
            'isLiked'   => $userId ? UserContentReaction::where('user_id', $userId)->where('cms_content_id', $item->id)->where('type', 'like')->exists() : false,
            'likeCount' => UserContentReaction::where('cms_content_id', $item->id)->where('type', 'like')->count(),
        ]));
    }

    // ── Favorilerim / Reactions ──────────────────────────────────────────────

    public function toggleSave(Request $request, string $slug): \Illuminate\Http\JsonResponse
    {
        $item   = CmsContent::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['ok' => false, 'message' => 'Giriş yapmalısınız.'], 401);
        }

        $existing = UserSavedContent::where('user_id', $userId)->where('cms_content_id', $item->id)->first();
        if ($existing) {
            $existing->delete();
            return response()->json(['ok' => true, 'saved' => false]);
        }
        UserSavedContent::create(['user_id' => $userId, 'cms_content_id' => $item->id]);

        return response()->json(['ok' => true, 'saved' => true]);
    }

    public function savedList(Request $request)
    {
        $userId   = auth()->id();
        $savedIds = $userId ? UserSavedContent::where('user_id', $userId)->pluck('cms_content_id') : collect();
        $items    = CmsContent::whereIn('id', $savedIds)->where('status', 'published')->orderByDesc('id')->paginate(12);
        $base     = $this->baseData($request, 'saved', 'Favorilerim', '');
        $base['items'] = $items;

        return view('student.saved', $base);
    }

    public function toggleReaction(Request $request, string $slug): \Illuminate\Http\JsonResponse
    {
        $item   = CmsContent::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['ok' => false, 'message' => 'Giriş yapmalısınız.'], 401);
        }

        $existing = UserContentReaction::where('user_id', $userId)->where('cms_content_id', $item->id)->where('type', 'like')->first();
        if ($existing) {
            $existing->delete();
            $reacted = false;
        } else {
            UserContentReaction::create(['user_id' => $userId, 'cms_content_id' => $item->id, 'type' => 'like']);
            $reacted = true;
        }
        $count = UserContentReaction::where('cms_content_id', $item->id)->where('type', 'like')->count();

        return response()->json(['ok' => true, 'reacted' => $reacted, 'count' => $count]);
    }

    // ── Yardım Merkezi ───────────────────────────────────────────────────────

    public function helpCenter(Request $request)
    {
        $base     = $this->baseData($request, 'help-center', 'Yardım Merkezi', 'Sık sorulan sorular ve destek kaynakları.');
        $q        = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', 'all'));

        $faqQuery = CmsContent::query()
            ->where('status', 'published')
            ->where(fn ($w) => $w->where('category', 'faq')->orWhere('type', 'faq'))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x
                ->where('title_tr', 'like', "%{$q}%")
                ->orWhere('content_tr', 'like', "%{$q}%")))
            ->when($category !== 'all', fn ($w) => $w->whereJsonContains('tags', $category))
            ->orderBy('featured_order')
            ->orderByDesc('published_at')
            ->limit(50)
            ->get(['id', 'title_tr', 'content_tr', 'tags', 'category']);

        $categories = [
            'process'     => ['label' => 'Süreç Takibi',      'icon' => '📋'],
            'documents'   => ['label' => 'Belgeler',           'icon' => '📄'],
            'contract'    => ['label' => 'Sözleşme & Ödeme',  'icon' => '📝'],
            'visa'        => ['label' => 'Vize',               'icon' => '✈️'],
            'university'  => ['label' => 'Üniversiteler',      'icon' => '🎓'],
            'living'      => ['label' => 'Almanya\'da Yaşam',  'icon' => '🏠'],
        ];

        $grouped = $faqQuery->groupBy(fn ($faq) => collect($faq->tags ?? [])->first() ?? 'general');

        return view('student.help-center', array_merge($base, [
            'faqs'           => $faqQuery,
            'grouped'        => $grouped,
            'categories'     => $categories,
            'search'         => $q,
            'activeCategory' => $category,
        ]));
    }

    // ── Maliyet Hesaplama ────────────────────────────────────────────────────

    public function costCalculator(Request $request)
    {
        $base       = $this->baseData($request, 'cost-calculator', 'Maliyet Hesaplama', 'Almanya\'da eğitim maliyetlerinizi hesaplayın.');
        $guestApp   = $base['guestApplication'] ?? null;
        $city       = strtolower($request->query('city', $guestApp?->target_city ?? 'other'));
        $cities     = config('cost_calculator.cities', []);
        $cityData   = $cities[$city] ?? $cities['other'];

        $packagePrice = 0.0;
        $selectedPkg  = collect(config('service_packages.packages', []))->firstWhere('code', $guestApp?->selected_package_code);
        if ($selectedPkg) {
            $packagePrice = (float) ($selectedPkg['price_amount'] ?? 0);
        }

        $extraTotal    = collect($guestApp?->selected_extra_services ?? [])->sum(fn ($s) => (float) ($s['price_amount'] ?? 0));
        $monthlyLiving = $cityData['rent_avg'] + $cityData['food_avg'] + $cityData['transport_avg'] + ($cityData['misc_avg'] ?? 0);
        $yearlyLiving  = $monthlyLiving * 12;
        $allFixed      = collect(config('cost_calculator.fixed_costs', []));
        $fixedCosts    = $allFixed->where('is_deposit', '!=', true)->values()->toArray();
        $depositCosts  = $allFixed->where('is_deposit', true)->values()->toArray();
        $fixedTotal    = collect($fixedCosts)->where('required', true)->sum('amount');
        $fixedOptional = collect($fixedCosts)->where('required', false)->sum('amount');
        $depositTotal  = collect($depositCosts)->sum('amount');
        $eurTryRate    = app(CurrencyRateService::class)->getRate('EUR', 'TRY');

        $base['calculator'] = [
            'city'                  => $city,
            'cityLabel'             => $cityData['label'],
            'cities'                => $cities,
            'packagePrice'          => $packagePrice,
            'extraServices'         => $extraTotal,
            'monthlyLiving'         => $monthlyLiving,
            'yearlyLiving'          => $yearlyLiving,
            'fixedCosts'            => $fixedCosts,
            'fixedTotal'            => $fixedTotal,
            'fixedOptional'         => $fixedOptional,
            'depositCosts'          => $depositCosts,
            'depositTotal'          => $depositTotal,
            'eurTryRate'            => $eurTryRate,
            'grandTotalEur'         => $yearlyLiving + $fixedTotal + $depositTotal + $packagePrice + $extraTotal,
            'grandTotalTry'         => ($yearlyLiving + $fixedTotal + $depositTotal + $packagePrice + $extraTotal) * ($eurTryRate ?? 40),
            'turkey_private_yearly' => config('cost_calculator.turkey_private_yearly_try', 150000),
            'germany_total_try'     => (($yearlyLiving + $fixedTotal) * ($eurTryRate ?? 40)),
        ];

        return view('student.cost-calculator', $base);
    }
}
