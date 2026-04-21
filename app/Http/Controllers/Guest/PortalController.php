<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\UsesRequiredDocuments;
use App\Http\Controllers\Concerns\UsesServicePackages;
use App\Http\Controllers\Guest\Concerns\GuestPortalTrait;
use App\Models\ContractTemplate;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\GuestAchievement;
use App\Models\GuestApplication;
use App\Models\GuestOnboardingStep;
use App\Models\GuestReferral;
use App\Models\GuestTicket;
use App\Models\Marketing\CmsContent;
use App\Models\MarketingCampaign;
use App\Models\NotificationDispatch;
use App\Models\User;
use App\Services\ContractTemplateService;
use App\Services\CurrencyRateService;
use App\Services\GuestRegistrationFieldSchemaService;
use App\Services\GuestResolverService;
use App\Services\GuestViewDataService;
use App\Support\SchemaCache;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    use UsesRequiredDocuments, UsesServicePackages, GuestPortalTrait;

    public function __construct(
        private readonly ContractTemplateService $contractTemplateService,
        private readonly GuestResolverService    $guestResolver,
        private readonly GuestViewDataService    $viewData,
    ) {}

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard(Request $request)
    {
        $guest = $this->resolveGuest($request);

        // Guest application bulunamadıysa: kullanıcı email'i guest_applications
        // tablosunda karşılık bulmuyor (cleanup sonrası drift olabilir veya
        // kayıt henüz oluşmamış). 500 yerine açıklayıcı bir sayfa gönder.
        if (!$guest) {
            return view('guest.dashboard-no-application', [
                'userEmail' => (string) ($request->user()?->email ?? ''),
            ]);
        }

        $data  = $this->viewData->build($request, $guest);

        $data['activeCampaigns'] = MarketingCampaign::query()
            ->whereIn('status', ['active', 'running', 'scheduled'])
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get(['name', 'description', 'channel', 'target_country', 'end_date']);

        $assignedSeniorEmail = (string) ($guest?->assigned_senior_email ?? '');
        $assignedSenior      = null;
        if ($assignedSeniorEmail !== '') {
            $assignedSenior = User::query()
                ->where('email', strtolower($assignedSeniorEmail))
                ->first(['id', 'name', 'email', 'role']);
        }
        $data['assignedSenior'] = $assignedSenior;

        $ticketSummary = ['open' => 0, 'waiting_response' => 0, 'unread_like' => 0];
        if ($guest) {
            $row = GuestTicket::query()
                ->where('guest_application_id', (int) $guest->id)
                ->selectRaw(
                    "COUNT(CASE WHEN status IN ('open','in_progress','waiting_response') THEN 1 END) AS open_count,"
                    . "COUNT(CASE WHEN status = 'waiting_response' THEN 1 END) AS waiting_count"
                )
                ->first();
            $ticketSummary['open']             = (int) ($row->open_count    ?? 0);
            $ticketSummary['waiting_response'] = (int) ($row->waiting_count ?? 0);
            $ticketSummary['unread_like']      = $ticketSummary['waiting_response'];
        }
        $data['ticketSummary']  = $ticketSummary;
        $data['contentBlocks']  = $this->viewData->loadDashboardContentBlocks();

        $applicationType = (string) ($guest?->application_type ?? '');
        $data['banners'] = CmsContent::query()
            ->where('status', 'published')
            ->where('category', 'guest_banner')
            ->where('is_featured', true)
            ->where(fn ($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('archived_at')->orWhere('archived_at', '>', now()))
            ->where(function ($q) use ($applicationType) {
                $q->whereNull('target_student_types')
                  ->orWhereJsonContains('target_student_types', $applicationType);
            })
            ->orderBy('featured_order')
            ->limit(5)
            ->get(['id', 'title_tr', 'summary_tr', 'cover_image_url', 'seo_canonical_url', 'slug']);

        $stepIcons      = ['📋', '📄', '📦', '📝', '🎓'];
        $firstUndoneIdx = collect($data['progress'])->search(fn ($s) => !$s['done']);
        $data['progressVisual'] = collect($data['progress'])->map(function ($step, $idx) use ($stepIcons, $firstUndoneIdx) {
            return array_merge($step, [
                'step_number' => $idx + 1,
                'icon'        => $stepIcons[$idx] ?? '⬜',
                'color'       => $step['done'] ? 'green' : ($idx === $firstUndoneIdx ? 'blue-pulse' : 'gray'),
            ]);
        });

        $nextStep = collect($data['progress'])->slice(0, 4)->first(fn ($s) => !$s['done']);
        $data['nextStep']     = $nextStep;
        $data['heroNextStep'] = $nextStep ? [
            'label'          => $nextStep['label'],
            'url'            => $nextStep['url'],
            'icon'           => match ($nextStep['label']) {
                'Kayıt Formu'     => '📋',
                'Belgeler'        => '📄',
                'Paket Seçimi'    => '📦',
                'Sözleşme / Onay' => '📝',
                default           => '➡️',
            },
            'cta_text'       => match ($nextStep['label']) {
                'Kayıt Formu'     => 'Formu Doldur',
                'Belgeler'        => 'Belgelerini Yükle',
                'Paket Seçimi'    => 'Paketini Seç',
                'Sözleşme / Onay' => 'Sözleşmeyi İncele',
                default           => 'Devam Et',
            },
            'estimated_time' => match ($nextStep['label']) {
                'Kayıt Formu'     => '~10 dakika',
                'Belgeler'        => '~5 dakika (belgeler hazırsa)',
                'Paket Seçimi'    => '~2 dakika',
                'Sözleşme / Onay' => '~5 dakika',
                default           => '',
            },
        ] : null;

        $percent = (int) $data['progressPercent'];
        $data['motivationMessage'] = match (true) {
            $percent === 0 => ['emoji' => '🚀', 'text' => 'Almanya\'daki eğitim yolculuğun başlıyor! İlk adımı at.'],
            $percent <= 20 => ['emoji' => '💪', 'text' => 'Harika bir başlangıç yaptın! Devam et.'],
            $percent <= 40 => ['emoji' => '📈', 'text' => 'İlerlemen güzel! Her adım seni hedefe yaklaştırıyor.'],
            $percent <= 60 => ['emoji' => '🔥', 'text' => 'Yarıyı geçtin! Çok iyi gidiyorsun.'],
            $percent <= 80 => ['emoji' => '⭐', 'text' => 'Neredeyse tamam! Son birkaç adım kaldı.'],
            $percent < 100 => ['emoji' => '🏁', 'text' => 'Bitişe çok az kaldı! Son hamleyi yap.'],
            default        => ['emoji' => '🎓', 'text' => 'Tebrikler! Almanya yolculuğun resmileşti!'],
        };

        $firstName         = trim((string) ($guest?->first_name ?? ''));
        $data['seniorCard'] = $assignedSenior ? [
            'name'           => (string) ($assignedSenior->name ?? ''),
            'email'          => (string) ($assignedSenior->email ?? ''),
            'photo'          => $assignedSenior->photo_url ? (string) $assignedSenior->photo_url : null,
            'bio'            => (string) ($assignedSenior->bio ?? ''),
            'expertise_tags' => method_exists($assignedSenior, 'expertiseTags') ? $assignedSenior->expertiseTags() : [],
            'success_count'  => method_exists($assignedSenior, 'successfulStudentsCount') ? $assignedSenior->successfulStudentsCount() : 0,
            'title'          => 'Eğitim Danışmanı',
            'message'        => "Merhaba" . ($firstName !== '' ? " {$firstName}" : '') . ", ben senin danışmanın. Herhangi bir sorun olursa bana yazabilirsin!",
            'dm_url'         => '/guest/messages',
        ] : null;

        $data['showAppointmentCta'] = $assignedSenior !== null;
        if ($guest) {
            $data['showAppointmentCta'] = $data['showAppointmentCta']
                && !\App\Models\StudentAppointment::where('student_id', $guest->id)->exists();
        }

        $onboardingPending = false;
        $onboardingSteps   = [];
        if ($guest) {
            $existingSteps     = GuestOnboardingStep::where('guest_application_id', $guest->id)->get()->keyBy('step_code');
            $onboardingTouched = $existingSteps->filter(fn ($s) => $s->completed_at || $s->skipped_at)->count();
            $onboardingPending = ($onboardingTouched === 0);
            foreach (GuestOnboardingStep::STEPS as $code) {
                $row  = $existingSteps[$code] ?? null;
                $done = $row && ($row->completed_at || $row->skipped_at);
                $onboardingSteps[] = [
                    'code'    => $code,
                    'label'   => GuestOnboardingStep::STEP_LABELS[$code] ?? $code,
                    'icon'    => GuestOnboardingStep::STEP_ICONS[$code] ?? '⬜',
                    'url'     => GuestOnboardingStep::STEP_URLS[$code] ?? '/guest/dashboard',
                    'done'    => $done,
                    'skipped' => $row && !$row->completed_at && $row->skipped_at,
                ];
            }
        }
        $data['onboardingPending'] = $onboardingPending;
        $data['onboardingSteps']   = $onboardingSteps;

        if ($guest) {
            $earnedCodes     = GuestAchievement::where('guest_application_id', $guest->id)->pluck('achievement_code')->all();
            $earnedWithMeta  = GuestAchievement::where('guest_application_id', $guest->id)->orderByDesc('earned_at')->get()
                ->map(fn ($a) => array_merge(
                    config("guest_achievements.{$a->achievement_code}", []),
                    ['code' => $a->achievement_code, 'earned_at' => $a->earned_at->format('d.m.Y')]
                ))->filter(fn ($a) => !empty($a['icon']))->values();
            $data['achievements']    = $earnedWithMeta;
            $data['totalPoints']     = (int) $earnedWithMeta->sum('points');
            $data['nextAchievement'] = collect(config('guest_achievements'))
                ->reject(fn ($def, $code) => in_array($code, $earnedCodes))->first();
        } else {
            $data['achievements']    = collect();
            $data['totalPoints']     = 0;
            $data['nextAchievement'] = null;
        }

        if ($guest) {
            $ref = GuestReferral::where('referrer_guest_id', $guest->id)
                ->selectRaw("COUNT(*) as total_sent, SUM(status = 'registered') as registered, SUM(status = 'converted') as converted, MAX(referral_code) as referral_code")
                ->first();
            $data['referralStats'] = [
                'total_sent'    => (int) ($ref->total_sent ?? 0),
                'registered'    => (int) ($ref->registered ?? 0),
                'converted'     => (int) ($ref->converted ?? 0),
                'referral_code' => $ref->referral_code ?? null,
            ];
        } else {
            $data['referralStats'] = ['total_sent' => 0, 'registered' => 0, 'converted' => 0, 'referral_code' => null];
        }

        $data['upcomingAppointments'] = $guest
            ? \App\Models\StudentAppointment::where('student_id', $guest->id)->where('scheduled_at', '>=', now())->orderBy('scheduled_at')->limit(3)->get()
            : collect();

        $targetCity  = strtolower((string) ($guest?->target_city ?? 'other'));
        $costCities  = config('cost_calculator.cities', []);
        $dashCity    = $costCities[$targetCity] ?? $costCities['other'];
        $dashMonthly = ($dashCity['rent_avg'] ?? 0) + ($dashCity['food_avg'] ?? 0) + ($dashCity['transport_avg'] ?? 0) + ($dashCity['misc_avg'] ?? 0) + 110;
        $data['dashboardCity'] = [
            'label'     => $dashCity['label'] ?? 'Diğer',
            'rent'      => $dashCity['rent_avg']      ?? 0,
            'food'      => $dashCity['food_avg']      ?? 0,
            'transport' => $dashCity['transport_avg'] ?? 0,
            'misc'      => $dashCity['misc_avg']      ?? 0,
            'health'    => 110,
            'monthly'   => $dashMonthly,
            'eur_rate'  => app(CurrencyRateService::class)->getRate('EUR', 'TRY'),
        ];

        $activityFeed = [];
        if ($guest) {
            $feedOwnerId      = $this->viewData->resolveDocumentOwnerId($guest);
            $docActivities    = Document::query()
                ->where('student_id', $feedOwnerId)
                ->orderByDesc('updated_at')
                ->limit(3)
                ->get()
                ->map(fn ($d) => [
                    'icon'       => match ((string) ($d->status ?? '')) {
                        'approved'            => '✅',
                        'review', 'uploaded'  => '⏳',
                        default               => '📤',
                    },
                    'text'       => ($d->title ?? $d->document_code ?? 'Belge') . ' güncellendi',
                    'time_label' => $d->updated_at?->diffForHumans() ?? '',
                    'sort_ts'    => $d->updated_at?->timestamp ?? 0,
                ]);
            $ticketActivities = GuestTicket::query()
                ->where('guest_application_id', (int) $guest->id)
                ->orderByDesc('created_at')
                ->limit(2)
                ->get()
                ->map(fn ($t) => [
                    'icon'       => '🎧',
                    'text'       => ($t->subject ?? 'Destek talebi') . ' oluşturuldu',
                    'time_label' => $t->created_at?->diffForHumans() ?? '',
                    'sort_ts'    => $t->created_at?->timestamp ?? 0,
                ]);
            $activityFeed = $docActivities->concat($ticketActivities)->sortByDesc('sort_ts')->take(5)->values()->all();
        }
        $data['activityFeed'] = $activityFeed;

        // ── Guest Analytics (audit gap fix) ──
        $guestAnalytics = [];
        if ($guest) {
            // UTM kaynak şeffaflığı
            $guestAnalytics['source'] = [
                'utm_source'   => $guest->utm_source ?: null,
                'utm_medium'   => $guest->utm_medium ?: null,
                'utm_campaign' => $guest->utm_campaign ?: null,
                'lead_source'  => $guest->lead_source ?: null,
                'dealer_code'  => $guest->dealer_code ?: null,
            ];

            // Süreçte geçen süre
            $guestAnalytics['daysSinceRegistration'] = $guest->created_at ? (int) $guest->created_at->diffInDays(now()) : 0;

            // Belge durumu özeti
            $docStats = ['uploaded' => 0, 'approved' => 0, 'rejected' => 0, 'missing' => 0];
            $ownerId = $guest->converted_student_id ?: ('GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT));
            $docs = \App\Models\Document::where('student_id', $ownerId)->get(['status']);
            $docStats['uploaded'] = $docs->where('status', 'uploaded')->count();
            $docStats['approved'] = $docs->where('status', 'approved')->count();
            $docStats['rejected'] = $docs->where('status', 'rejected')->count();
            $docStats['total'] = $docs->count();
            $guestAnalytics['docStats'] = $docStats;
        }
        $data['guestAnalytics'] = $guestAnalytics;

        return view('guest.dashboard', $data);
    }

    // ── Onboarding ───────────────────────────────────────────────────────────

    public function onboarding(Request $request)
    {
        $guest = $this->resolveGuest($request);
        if (!$guest) {
            return redirect()->route('guest.dashboard');
        }
        $steps       = GuestOnboardingStep::where('guest_application_id', $guest->id)->get()->keyBy('step_code');
        $stepOrder   = GuestOnboardingStep::STEPS;
        $doneCnt     = $steps->filter(fn ($s) => $s->completed_at || $s->skipped_at)->count();
        if ($doneCnt >= count($stepOrder)) {
            return redirect()->route('guest.dashboard');
        }
        $currentStep = collect($stepOrder)->first(fn ($code) =>
            !($steps->get($code)?->completed_at) && !($steps->get($code)?->skipped_at));

        $data                = $this->viewData->build($request, $guest);
        $assignedSeniorEmail = (string) ($guest->assigned_senior_email ?? '');
        $assignedSenior      = $assignedSeniorEmail !== ''
            ? User::where('email', strtolower($assignedSeniorEmail))->first(['id', 'name', 'email'])
            : null;

        return view('guest.onboarding', array_merge($data, [
            'currentStep'    => $currentStep,
            'stepOrder'      => $stepOrder,
            'steps'          => $steps,
            'completedSteps' => $doneCnt,
            'totalSteps'     => count($stepOrder),
            'stepLabels'     => GuestOnboardingStep::STEP_LABELS,
            'stepIcons'      => GuestOnboardingStep::STEP_ICONS,
            'stepUrls'       => GuestOnboardingStep::STEP_URLS,
            'assignedSenior' => $assignedSenior,
        ]));
    }

    // ── Registration ─────────────────────────────────────────────────────────

    public function registrationForm(Request $request)
    {
        $guest     = $this->resolveGuest($request);
        $data      = $this->viewData->build($request, $guest);
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $data['registrationFieldGroups'] = app(GuestRegistrationFieldSchemaService::class)->groups($companyId);

        return view('guest.registration-form', $data);
    }

    public function registrationDocuments(Request $request)
    {
        $guest   = $this->resolveGuest($request);
        $data    = $this->viewData->build($request, $guest);
        $ownerId = $guest ? $this->viewData->resolveDocumentOwnerId($guest) : '__guest__';

        $data['documents'] = $guest
            ? Document::query()
                ->where('student_id', $ownerId)
                ->with('category:id,code,name_tr')
                ->latest()
                ->limit(20)
                ->get(['id', 'category_id', 'original_file_name', 'mime_type', 'status', 'review_note', 'updated_at'])
            : collect();

        $data['documentsByCategory'] = $data['documents']
            ->groupBy(fn (Document $d) => (string) ($d->category->code ?? ''))
            ->map(fn ($rows) => $rows->first());

        $data['documentCategories'] = DocumentCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name_tr')
            ->limit(120)
            ->get(SchemaCache::hasColumn('document_categories', 'top_category_code')
                ? ['code', 'name_tr', 'top_category_code']
                : ['code', 'name_tr']);

        $uploadedCodes = $data['documents']
            ->map(fn (Document $d) => (string) ($d->category->code ?? ''))
            ->filter()->values()->all();

        $data['requiredDocumentChecklist'] = $this->requiredDocumentsByApplicationType(
            (string) ($guest?->application_type ?? ''),
            $uploadedCodes
        );

        $labels = DocumentCategory::topCategoryOptions();
        $data['documentTopCategoryLabels'] = $labels;
        $data['requiredDocumentChecklist'] = collect($data['requiredDocumentChecklist'])
            ->map(function (array $item) use ($labels): array {
                $topCode = (string) ($item['top_category_code'] ?? DocumentCategory::defaultTopCategoryCode());
                $item['top_category_code']  = $topCode;
                $item['top_category_label'] = (string) ($labels[$topCode] ?? $labels[DocumentCategory::defaultTopCategoryCode()]);
                return $item;
            })
            ->sortBy([['top_category_code', 'asc'], ['document_code', 'asc']])
            ->values()->all();

        $data['documentCards'] = collect($data['requiredDocumentChecklist'])->map(function ($doc) use ($data) {
            $code      = (string) ($doc['document_code'] ?? $doc['code'] ?? '');
            $catCode   = (string) ($doc['category_code'] ?? $code);
            $docRecord = $data['documentsByCategory'][$catCode] ?? null;
            $status    = $doc['uploaded'] ? (string) ($docRecord?->status ?? 'uploaded') : 'missing';
            return [
                'code'         => $code,
                'name'         => (string) ($doc['name'] ?? $code),
                'description'  => (string) ($doc['description'] ?? ''),
                'is_required'  => (bool) ($doc['is_required'] ?? false),
                'uploaded'     => (bool) $doc['uploaded'],
                'accepted'     => '.pdf,.jpg,.jpeg,.png',
                'max_mb'       => 10,
                'top_category' => (string) ($doc['top_category_code'] ?? 'diger_dokumanlar'),
                'status'       => $status,
                'review_note'  => $docRecord?->review_note,
                'status_color' => match ($status) { 'approved' => 'green', 'rejected' => 'red', 'uploaded' => 'yellow', default => 'gray' },
                'status_label' => match ($status) { 'approved' => 'Onaylandı', 'rejected' => 'Reddedildi', 'uploaded' => 'İnceleniyor', default => 'Yüklenmedi' },
                'guide'        => $this->documentGuide($code),
            ];
        })->values()->all();

        return view('guest.registration-documents', $data);
    }

    // ── Servisler ────────────────────────────────────────────────────────────

    public function services(Request $request)
    {
        $guest = $this->resolveGuest($request);
        $data  = $this->viewData->build($request, $guest);

        $rawPackages = collect(config('service_packages.packages', []))->where('is_active', true)->values();

        $data['packages']            = $rawPackages;
        $data['eurTryRate']          = app(CurrencyRateService::class)->getRate('EUR', 'TRY');
        $data['comparisonTable']     = ['packages' => $rawPackages, 'popular' => 'pkg_plus'];
        $data['selectedPackageCode'] = (string) ($guest?->selected_package_code ?? '');
        $data['selectedPackageTitle']= (string) ($guest?->selected_package_title ?? '');
        $data['selectedPackagePrice']= (string) ($guest?->selected_package_price ?? '');
        $data['selectedExtras']      = is_array($guest?->selected_extra_services) ? $guest->selected_extra_services : [];
        $data['packageSelectedAt']   = $guest?->package_selected_at;
        $data['contractRequested']   = in_array((string)($guest?->contract_status ?? 'not_requested'), ['requested','signed_uploaded','pending_manager','approved','reopen_requested'], true);

        $allExtras = collect(config('service_packages.extra_services', []))->where('is_active', true);
        $data['serviceCategories'] = collect(config('service_packages.service_categories', []))
            ->map(fn ($cat) => array_merge($cat, [
                'services' => $allExtras->where('category', $cat['key'])->sortBy('sort_order')->values()->all(),
            ]))
            ->filter(fn ($cat) => count($cat['services']) > 0)
            ->values()->all();

        // Seçili pakette dahil olan ek hizmetler
        $selectedCode = (string) ($guest?->selected_package_code ?? '');
        $selectedPkg = collect(config('service_packages.packages', []))->firstWhere('code', $selectedCode);
        $data['includedExtras'] = is_array($selectedPkg['included_extras'] ?? null) ? $selectedPkg['included_extras'] : [];

        return view('guest.services', $data);
    }

    // ── Sözleşme ─────────────────────────────────────────────────────────────

    public function contract(Request $request)
    {
        $guest = $this->resolveGuest($request);
        $data  = $this->viewData->build($request, $guest);

        $data['contractPackages']           = $this->servicePackages();
        $data['contractExtraServices']      = $this->extraServiceOptions();
        $data['selectedPackageCode']        = (string) ($guest?->selected_package_code ?? '');
        $data['selectedExtraServicesText']  = collect(is_array($guest?->selected_extra_services) ? $guest->selected_extra_services : [])
            ->map(fn ($x) => trim((string) ($x['title'] ?? '')))->filter()->implode(', ');
        $data['selectedExtraServiceCodes']  = collect(is_array($guest?->selected_extra_services) ? $guest->selected_extra_services : [])
            ->map(fn ($x) => trim((string) ($x['code'] ?? '')))->filter()->values()->all();
        $data['contractStatus']             = (string) ($guest?->contract_status ?? 'not_requested');
        $data['contractRequestedAt']        = $guest?->contract_requested_at;
        $data['contractSignedAt']           = $guest?->contract_signed_at;
        $data['contractApprovedAt']         = $guest?->contract_approved_at;
        $data['contractSignedFilePath']     = (string) ($guest?->contract_signed_file_path ?? '');
        $data['contractTemplateCode']       = (string) ($guest?->contract_template_code ?? '');
        $data['contractGeneratedAt']        = $guest?->contract_generated_at;
        $data['contractSnapshotText']       = (string) ($guest?->contract_snapshot_text ?? '');
        $data['contractAnnexKvkkText']      = (string) ($guest?->contract_annex_kvkk_text ?? '');
        $data['contractAnnexCommitmentText']= (string) ($guest?->contract_annex_commitment_text ?? '');
        $data['contractAnnexPaymentText']   = (string) ($guest?->contract_annex_payment_text ?? '');

        $companyId     = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $templateId    = (int) ($guest?->contract_template_id ?? 0);
        $printTemplate = $templateId > 0
            ? ContractTemplate::find($templateId, ['print_header_html', 'print_footer_html'])
            : null;
        if (!$printTemplate) {
            $printTemplate = ContractTemplate::query()
                ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first(['print_header_html', 'print_footer_html']);
        }
        $printVars              = $guest ? $this->contractTemplateService->buildPreviewVariables($guest) : [];
        $data['printHeaderHtml'] = trim($this->contractTemplateService->renderText((string) ($printTemplate?->print_header_html ?? ''), $printVars));
        $data['printFooterHtml'] = trim($this->contractTemplateService->renderText((string) ($printTemplate?->print_footer_html ?? ''), $printVars));

        $contractSteps  = [
            ['code' => 'not_requested',   'label' => 'Henüz Başlamadı',     'icon' => '⬜'],
            ['code' => 'pending_manager', 'label' => 'Danışman Hazırlıyor', 'icon' => '⏳'],
            ['code' => 'requested',       'label' => 'Sözleşme Gönderildi', 'icon' => '📧'],
            ['code' => 'signed_uploaded', 'label' => 'İmzalı Yüklendi',     'icon' => '📤'],
            ['code' => 'approved',        'label' => 'Onaylandı',            'icon' => '✅'],
        ];
        $currentStatus  = (string) ($data['contractStatus'] ?? 'not_requested');
        $currentIdx     = collect($contractSteps)->search(fn ($s) => $s['code'] === $currentStatus);
        if ($currentIdx === false) { $currentIdx = 0; }

        $data['contractStepper'] = collect($contractSteps)->map(function ($step, $idx) use ($currentIdx) {
            return array_merge($step, [
                'status'      => match (true) {
                    $idx < $currentIdx   => 'done',
                    $idx === $currentIdx => 'active',
                    default              => 'pending',
                },
                'description' => match ($step['code']) {
                    'not_requested'   => 'Sözleşme sürecini başlatmak için talebi gönderin.',
                    'pending_manager' => 'Danışmanınız sözleşmeyi hazırlıyor. Genellikle 1-2 iş günü.',
                    'requested'       => 'Sözleşme hazır! İndirin, imzalayın ve yükleyin.',
                    'signed_uploaded' => 'İmzalı sözleşmeniz inceleniyor. Genellikle 1 iş günü.',
                    'approved'        => 'Tebrikler! Sözleşmeniz onaylandı. Öğrenci kaydınız oluşturuldu.',
                    default           => '',
                },
            ]);
        });

        return view('guest.contract', $data);
    }

    public function contractSignedThanks(Request $request)
    {
        $guest = $this->resolveGuest($request);
        return view('guest.contract-signed-thanks', $this->viewData->build($request, $guest));
    }

    public function promotedToStudent(Request $request)
    {
        $user      = $request->user();
        $email     = strtolower(trim((string) optional($user)->email));
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $guest = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('email', $email)
            ->where('converted_to_student', true)
            ->latest('id')
            ->first();

        return view('guest.promoted-to-student', [
            'user'       => $user,
            'guest'      => $guest,
            'studentId'  => (string) ($guest?->converted_student_id ?? ''),
            'firstName'  => (string) ($guest?->first_name ?? optional($user)->name ?? ''),
            'approvedAt' => $guest?->contract_approved_at,
        ]);
    }

    // ── Biletler & Profil ────────────────────────────────────────────────────

    public function tickets(Request $request)
    {
        $guest     = $this->resolveGuest($request);
        $data      = $this->viewData->build($request, $guest);
        $data['tickets'] = $guest
            ? GuestTicket::query()
                ->where('guest_application_id', (int) $guest->id)
                ->with(['replies' => fn ($q) => $q->latest()->limit(10)])
                ->latest()
                ->limit(20)
                ->get()
            : collect();

        $studentId         = (string) ($guest->converted_student_id ?? '');
        $data['dispatches'] = $studentId !== ''
            ? NotificationDispatch::query()
                ->where('student_id', $studentId)
                ->latest()
                ->limit(10)
                ->get(['channel', 'category', 'status', 'queued_at', 'sent_at', 'failed_at'])
            : collect();

        return view('guest.tickets', $data);
    }

    public function profile(Request $request)
    {
        $guest = $this->resolveGuest($request);
        return view('guest.profile', $this->viewData->build($request, $guest));
    }

    public function settings(Request $request)
    {
        $guest = $this->resolveGuest($request);
        return view('guest.settings', $this->viewData->build($request, $guest));
    }

    // ── Document Guide Helper ────────────────────────────────────────────────

    private function documentGuide(string $code): array
    {
        return match ($code) {
            'DOC-DIPL' => ['what' => 'Lise veya üniversite diplomasının noter onaylı Almanca tercümesi.', 'where' => 'Yeminli tercüman bürolarından veya noter kanalıyla.', 'tips' => ['Apostil gerekebilir — ülkenize göre kontrol edin.', 'Orijinal + tercüme birlikte taranmalı.'], 'example' => null],
            'DOC-TRNS' => ['what' => 'Transkript (not dökümü) + Almanca yeminli tercüme.', 'where' => 'Okulunuzun öğrenci işleri biriminden alınır.', 'tips' => ['Kaşeli ve imzalı olmalı.', 'Dijital transkript kabul edilmeyebilir.'], 'example' => null],
            'DOC-PASS' => ['what' => 'Pasaportunuzun kimlik bilgisi ve fotoğraf sayfası.', 'where' => 'Nüfus müdürlüğünden.', 'tips' => ['En az 6 ay geçerli olmalı.', 'Tüm bilgiler okunaklı olmalı.'], 'example' => null],
            'DOC-IDCR' => ['what' => 'TC kimlik kartınızın ön ve arka yüzü.', 'where' => 'Nüfus müdürlüğünden.', 'tips' => ['Yeni çipli kimlik tercih edilir.', 'Fotoğraf net olmalı.'], 'example' => null],
            default    => ['what' => 'Bu belge hakkında detay için danışmanınıza sorun.', 'where' => '', 'tips' => [], 'example' => null],
        };
    }
}
