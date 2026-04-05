<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\GuestPortalTrait;
use App\Models\GermanyCity;
use App\Models\Marketing\CmsContent;
use App\Services\CurrencyRateService;
use App\Services\GuestResolverService;
use App\Services\GuestViewDataService;
use Illuminate\Http\Request;

class GuestInfoController extends Controller
{
    use GuestPortalTrait;

    public function __construct(
        private readonly GuestResolverService $guestResolver,
        private readonly GuestViewDataService $viewData,
    ) {}

    public function helpCenter(Request $request)
    {
        $guest    = $this->resolveGuest($request);
        $data     = $this->buildViewData($request, $guest);
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
            'application' => ['label' => 'Başvuru Süreci',   'icon' => '📋'],
            'documents'   => ['label' => 'Belgeler',          'icon' => '📄'],
            'contract'    => ['label' => 'Sözleşme & Ödeme', 'icon' => '📝'],
            'visa'        => ['label' => 'Vize',              'icon' => '✈️'],
            'university'  => ['label' => 'Üniversiteler',     'icon' => '🎓'],
            'living'      => ['label' => 'Almanya\'da Yaşam', 'icon' => '🏠'],
        ];

        $grouped = $faqQuery->groupBy(fn ($faq) => collect($faq->tags ?? [])->first() ?? 'general');

        return view('guest.help-center', array_merge($data, [
            'faqs'           => $faqQuery,
            'grouped'        => $grouped,
            'categories'     => $categories,
            'search'         => $q,
            'activeCategory' => $category,
        ]));
    }

    public function costCalculator(Request $request)
    {
        $guest    = $this->resolveGuest($request);
        $data     = $this->buildViewData($request, $guest);
        $city     = strtolower($request->query('city', $guest?->target_city ?? 'other'));
        $cities   = config('cost_calculator.cities', []);
        $cityData = $cities[$city] ?? $cities['other'];

        $packagePrice = 0.0;
        $selectedPkg  = collect(config('service_packages.packages', []))->firstWhere('code', $guest?->selected_package_code);
        if ($selectedPkg) {
            $packagePrice = (float) ($selectedPkg['price_amount'] ?? 0);
        }

        $extraTotal    = collect($guest?->selected_extra_services ?? [])->sum(fn ($s) => (float) ($s['price_amount'] ?? 0));
        $monthlyLiving = $cityData['rent_avg'] + $cityData['food_avg'] + $cityData['transport_avg'] + ($cityData['misc_avg'] ?? 0);
        $yearlyLiving  = $monthlyLiving * 12;

        $allFixed     = collect(config('cost_calculator.fixed_costs', []));
        $fixedCosts   = $allFixed->where('is_deposit', '!=', true)->values()->toArray();
        $depositCosts = $allFixed->where('is_deposit', true)->values()->toArray();
        $fixedTotal   = collect($fixedCosts)->where('required', true)->sum('amount');
        $fixedOptional = collect($fixedCosts)->where('required', false)->sum('amount');
        $depositTotal = collect($depositCosts)->sum('amount');
        $eurTryRate   = app(CurrencyRateService::class)->getRate('EUR', 'TRY');

        $data['calculator'] = [
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

        return view('guest.cost-calculator', $data);
    }

    public function universityGuidePage(Request $request)
    {
        $guest = $this->resolveGuest($request);
        $data  = $this->buildViewData($request, $guest);
        $data['cmsGuideItems'] = CmsContent::query()
            ->where('category', 'university-guide')
            ->where('status', 'published')
            ->orderByDesc('featured_order')
            ->orderByDesc('published_at')
            ->get(['id', 'slug', 'title_tr', 'summary_tr', 'content_tr', 'cover_image_url', 'tags', 'is_featured']);

        return view('guest.university-guide', $data);
    }

    public function successStoriesPage(Request $request)
    {
        $guest = $this->resolveGuest($request);
        $data  = $this->buildViewData($request, $guest);
        $data['cmsStories'] = CmsContent::query()
            ->where('category', 'success-stories')
            ->where('status', 'published')
            ->orderBy('featured_order')
            ->orderByDesc('published_at')
            ->get(['id', 'slug', 'title_tr', 'summary_tr', 'content_tr', 'cover_image_alt', 'cover_image_url', 'tags', 'is_featured', 'video_url', 'video_thumbnail_url']);

        $data['heroVideo'] = $data['cmsStories']->where('is_featured', true)->whereNotNull('video_url')->first()
            ?? $data['cmsStories']->whereNotNull('video_url')->first();
        $heroId = $data['heroVideo']?->id;
        $data['cmsVideos'] = $data['cmsStories']
            ->whereNotNull('video_url')
            ->when($heroId, fn ($c) => $c->where('id', '!=', $heroId))
            ->take(3)->values();

        return view('guest.success-stories', $data);
    }

    public function livingGuidePage(Request $request)
    {
        $guest = $this->resolveGuest($request);
        $data  = $this->buildViewData($request, $guest);
        $data['cities']     = config('cost_calculator.cities', []);
        $data['eurTryRate'] = app(CurrencyRateService::class)->getRate('EUR', 'TRY');

        return view('guest.living-guide', $data);
    }

    public function vizeGuidePage(Request $request)
    {
        $guest = $this->resolveGuest($request);
        $data  = $this->buildViewData($request, $guest);
        $data['eurTryRate'] = app(CurrencyRateService::class)->getRate('EUR', 'TRY');

        return view('guest.vize-guide', $data);
    }

    public function documentGuidePage(Request $request)
    {
        $guest = $this->resolveGuest($request);
        $data  = $this->buildViewData($request, $guest);

        return view('guest.document-guide', $data);
    }

    public function cityDetail(Request $request, string $slug)
    {
        $cities = GermanyCity::allAsConfig();
        if (!isset($cities[$slug])) {
            abort(404);
        }
        $guest = $this->resolveGuest($request);
        $data  = $this->buildViewData($request, $guest);
        $data['city']      = $cities[$slug];
        $data['allCities'] = $cities;

        return view('guest.city-detail', $data);
    }
}
