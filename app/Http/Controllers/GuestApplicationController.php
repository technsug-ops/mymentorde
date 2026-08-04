<?php

namespace App\Http\Controllers;

use App\Models\ConsentRecord;
use App\Models\Dealer;
use App\Models\GuestApplication;
use App\Models\LeadSourceOption;
use App\Models\MarketingCampaign;
use App\Models\MarketingAdminSetting;
use App\Models\StudentAssignment;
use App\Models\StudentType;
use App\Models\User;
use App\Support\ApplicationCountryCatalog;
use App\Support\ApplyCompanyResolver;
use App\Support\Brand;
use App\Support\TenantContext;
use App\Services\LeadSourceTrackingService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class GuestApplicationController extends Controller
{
    public function __construct(
        private readonly LeadSourceTrackingService $leadSourceTrackingService,
        private readonly NotificationService $notificationService,
    )
    {
    }

    public function create(Request $request)
    {
        // Program detay sayfasından geliyor olabilir — interested_program_id ile
        // hangi programa ilgi gösterdiğini takip et, application_meta'ya yazılacak
        $interestedProgramId = (int) $request->query('interested_program_id', 0) ?: null;
        $interestedProgramName = $request->query('interested_program') ?: null;
        if ($interestedProgramName !== null) {
            $interestedProgramName = mb_substr((string) $interestedProgramName, 0, 200);
        }

        $extra = [];
        if ($interestedProgramId) {
            $extra['interestedProgram'] = [
                'id'   => $interestedProgramId,
                'name' => $interestedProgramName,
            ];
        }

        return $this->renderApplyForm($extra);
    }

    public function createForPartner(string $code)
    {
        $dealer = \App\Models\Dealer::where('code', $code)
            ->where('is_active', true)
            ->where('is_archived', false)
            ->first();

        if (!$dealer) {
            abort(404, 'Bayi bulunamadı veya aktif değil.');
        }

        return $this->renderApplyForm([
            'partner' => $dealer,
            'prefill' => [
                'dealer_code'  => $dealer->code,
                'lead_source'  => 'partner_' . strtolower($dealer->code),
            ],
        ]);
    }

    /**
     * B2B partner firmanın kendi başvuru sayfası: /apply/{firma-slug}
     *
     * Bayi landing'inden (createForPartner) farkı: orada kayıt yine platformun
     * şirketine yazılır, sadece dealer_code işlenir. Burada kayıt FİRMANIN kendi
     * tenant'ına düşer — firma yöneticisi öğrencisini kendi panelinde görür.
     */
    public function createForCompany(Request $request, string $companySlug)
    {
        $company = ApplyCompanyResolver::bySlug($companySlug);

        if (!$company) {
            abort(404, 'Firma bulunamadı veya başvuru kabul etmiyor.');
        }

        ApplyCompanyResolver::remember($request, $company);

        // Öğrenci firmanın markasını görsün — nötr portalın markasını değil.
        Brand::apply($company);

        // Form içeriği de firmanın bağlamından gelsin: kampanyalar, bayi kodu
        // önerileri, öğrenci tipleri. Aksi halde partner öğrencisine platformun
        // kampanyaları gösterilirdi.
        return TenantContext::runFor((int) $company->id, fn () => $this->renderApplyForm([
            'applyCompany' => $company,
        ]));
    }

    private function renderApplyForm(array $extra = [])
    {
        $suggestions = $this->buildApplySuggestions(120);
        $leadSourceOptions = $this->getLeadSourceOptions();
        $studentTypes = StudentType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name_tr')
            ->get(['code', 'id_prefix', 'name_tr']);
        $activeCampaigns = MarketingCampaign::query()
            ->whereIn('status', ['active', 'running', 'scheduled'])
            ->orderByDesc('updated_at')
            ->limit(4)
            ->get(['name', 'description', 'channel', 'target_country']);

        return view('apply.create', array_merge([
            'dealerCodes' => $suggestions['dealer_codes'],
            'campaignValues' => $suggestions['campaign_values'],
            'branchValues' => $suggestions['branch_values'],
            'leadSourceOptions' => $leadSourceOptions,
            'studentTypes' => $studentTypes,
            'activeCampaigns' => $activeCampaigns,
            'applicationCountries' => ApplicationCountryCatalog::options(),
            'kvkkText' => $this->getApplyKvkkText(),
            'partner' => null,
            'applyCompany' => null,
            'prefill' => [],
            'interestedProgram' => null,
        ], $extra));
    }

    public function suggestions(Request $request)
    {
        $limit     = max(20, min(300, (int) $request->query('limit', 120)));
        $companyId = (int) (TenantContext::writeId() ?? 0);
        $cacheKey  = 'apply.suggestions.'.$companyId.'.'.$limit;
        $payload   = Cache::remember($cacheKey, 180, fn () => $this->buildApplySuggestions($limit));

        return response()->json($payload);
    }

    public function leadSourceOptions(): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->getLeadSourceOptions());
    }

    /**
     * Başvuruyu kaydet.
     *
     * Firma linkinden gelindiyse (/apply/{firma-slug}) TÜM kayıt zinciri o firmanın
     * bağlamında çalışır: başvuru, guest kullanıcısı, rıza kaydı ve otomatik danışman
     * ataması firmanın kendi personelinden seçilir. Aksi halde bugünkü davranış —
     * kayıt varsayılan şirkete (B2C havuzu) düşer.
     */
    public function store(Request $request)
    {
        $company = ApplyCompanyResolver::fromRequest($request);

        if ($company === null) {
            return $this->performStore($request);
        }

        return TenantContext::runFor((int) $company->id, fn () => $this->performStore($request));
    }

    private function performStore(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', config('validation.email'), 'max:190'],
            'phone' => ['nullable', 'string', 'max:60', 'regex:/^\+\d{1,4}\s\d{3,15}$/'],
            'gender' => ['nullable', 'in:male,female,not_specified'],
            'application_country' => ['nullable', 'string', 'max:120'],
            'communication_language' => ['nullable', 'in:tr,de,en'],
            'application_type' => ['required', 'string', 'max:64'],
            'target_term' => ['nullable', 'string', 'max:60'],
            'target_city' => ['nullable', 'string', 'max:100'],
            'language_level' => ['nullable', 'string', 'max:32'],
            'lead_source' => ['nullable', 'string', 'max:64'],
            'dealer_code' => ['nullable', 'string', 'max:64'],
            'campaign_code' => ['nullable', 'string', 'max:64'],
            'tracking_link_code' => ['nullable', 'string', 'max:64'],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_medium' => ['nullable', 'string', 'max:120'],
            'utm_campaign' => ['nullable', 'string', 'max:191'],
            'utm_term' => ['nullable', 'string', 'max:191'],
            'utm_content' => ['nullable', 'string', 'max:191'],
            'click_id' => ['nullable', 'string', 'max:191'],
            'landing_url' => ['nullable', 'url', 'max:2000'],
            'referrer_url' => ['nullable', 'url', 'max:2000'],
            'branch' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'kvkk_consent' => ['required', 'accepted'],
            'docs_ready' => ['nullable', 'boolean'],
            'interested_program_id' => ['nullable', 'integer'],
            'interested_program' => ['nullable', 'string', 'max:200'],
            'cf_turnstile_response' => ['nullable', 'string', new \App\Rules\TurnstileToken()],
        ]);

        // Aynı e-postayla aktif başvuru varsa yeni kayıt oluşturma
        $existingEmail = strtolower(trim((string) $data['email']));
        $duplicate = GuestApplication::query()
            ->where('email', $existingEmail)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->first(['id', 'tracking_token', 'email', 'guest_user_id']);
        if ($duplicate) {
            // Duplicate başvuru: kullanıcıya açık mesaj ver (HTML linkler içerir, view @raw ile render ediyor)
            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Bu e-posta adresiyle zaten bir başvuru bulunmaktadır. '
                        . 'Portala giriş yapmak için <a href="/login" style="color:#2563eb;font-weight:700;text-decoration:underline;">giriş sayfasını</a> kullanın. '
                        . 'Şifrenizi hatırlamıyorsanız <a href="/forgot-password" style="color:#2563eb;font-weight:700;text-decoration:underline;">Şifremi Unuttum</a> bağlantısını kullanabilirsiniz.',
                ]);
        }

        // ── Kurumlar arası e-posta çakışması ────────────────────────────────
        // `users.email` GLOBAL unique. Yukarıdaki duplicate kontrolü yalnızca
        // görünen şirkette arar; başka bir firmada aynı e-postayla hesap varsa
        // buraya kadar gelir ve INSERT veritabanı seviyesinde patlardı (500).
        // Kullanıcıya anlaşılır mesaj ver.
        $targetCompanyId = TenantContext::writeId();
        $emailOwner = User::query()
            ->withoutGlobalScope('company')
            ->where('email', $existingEmail)
            ->first(['id', 'company_id']);

        if ($emailOwner && $targetCompanyId && (int) $emailOwner->company_id !== (int) $targetCompanyId) {
            // ⚠ MESAJ BAŞKA KURUMDAN SÖZ ETMEZ.
            //
            // "Bu e-posta başka bir kurumda kayıtlı" demek, başvurana o kişinin
            // RAKİP bir firmanın müşterisi olduğunu söylerdi. Rastgele e-posta
            // deneyen biri partner firmaların müşteri listesini çıkarabilirdi.
            // Hesabın var olduğunu söylemek (giriş/şifre sıfırlama akışlarında
            // zaten olduğu gibi) kabul edilebilir; NEREDE olduğunu söylemek değil.
            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Bu e-posta adresiyle daha önce bir hesap oluşturulmuş. '
                        . '<a href="/login" style="color:#2563eb;font-weight:700;text-decoration:underline;">Giriş yapın</a> '
                        . 'ya da <a href="/forgot-password" style="color:#2563eb;font-weight:700;text-decoration:underline;">şifrenizi sıfırlayın</a>.',
                ]);
        }

        $token = $this->generateTrackingToken();
        $manualLeadSource = trim((string) ($data['lead_source'] ?? 'organic')) ?: 'organic';
        $utmSource = trim((string) ($data['utm_source'] ?? ''));
        $leadSource = ($utmSource !== '' && strtolower($manualLeadSource) === 'organic')
            ? strtolower($utmSource)
            : $manualLeadSource;

        $manualCampaignCode = trim((string) ($data['campaign_code'] ?? ''));
        $utmCampaign = trim((string) ($data['utm_campaign'] ?? ''));
        $campaignCode = $manualCampaignCode !== '' ? $manualCampaignCode : $utmCampaign;
        $assignedSeniorEmail = $this->pickAutoSeniorEmail((string) $data['application_type']);
        [$guestUser, $generatedPassword, $row] = DB::transaction(function () use ($data, $token, $assignedSeniorEmail, $leadSource, $campaignCode, $utmSource, $utmCampaign) {
            [$guestUser, $generatedPassword] = $this->ensureGuestPortalUser(
                trim((string) $data['first_name']),
                trim((string) $data['last_name']),
                strtolower(trim((string) $data['email']))
            );

            $row = GuestApplication::query()->create([
            'tracking_token' => $token,
            'guest_user_id' => $guestUser?->id,
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'email' => strtolower(trim((string) $data['email'])),
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'gender' => (string) ($data['gender'] ?? 'not_specified'),
            'application_country' => trim((string) ($data['application_country'] ?? 'de')) ?: 'de',
            'communication_language' => (string) ($data['communication_language'] ?? 'tr'),
            'application_type' => (string) $data['application_type'],
            'assigned_senior_email' => $assignedSeniorEmail ?: null,
            'assigned_at' => $assignedSeniorEmail ? now() : null,
            'assigned_by' => $assignedSeniorEmail ? 'system_auto' : null,
            'target_term' => trim((string) ($data['target_term'] ?? '')) ?: null,
            'target_city' => trim((string) ($data['target_city'] ?? '')) ?: null,
            'language_level' => trim((string) ($data['language_level'] ?? '')) ?: null,
            'lead_source' => $leadSource,
            'dealer_code' => trim((string) ($data['dealer_code'] ?? '')) ?: null,
            'campaign_code' => $campaignCode !== '' ? $campaignCode : null,
            'tracking_link_code' => trim((string) ($data['tracking_link_code'] ?? '')) ?: null,
            'utm_source' => $utmSource !== '' ? $utmSource : null,
            'utm_medium' => trim((string) ($data['utm_medium'] ?? '')) ?: null,
            'utm_campaign' => $utmCampaign !== '' ? $utmCampaign : null,
            'utm_term' => trim((string) ($data['utm_term'] ?? '')) ?: null,
            'utm_content' => trim((string) ($data['utm_content'] ?? '')) ?: null,
            'click_id' => trim((string) ($data['click_id'] ?? '')) ?: null,
            'landing_url' => trim((string) ($data['landing_url'] ?? '')) ?: null,
            'referrer_url' => trim((string) ($data['referrer_url'] ?? '')) ?: null,
            'branch' => trim((string) ($data['branch'] ?? '')) ?: null,
            'priority' => 'normal',
            'risk_level' => 'normal',
            'lead_status' => 'new',
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'kvkk_consent' => true,
            'docs_ready' => (bool) ($data['docs_ready'] ?? false),
            'converted_to_student' => false,
            'status_message' => $assignedSeniorEmail
                ? 'Basvuru alindi. Danisman atandi: '.$assignedSeniorEmail
                : 'Basvuru alindi. Danisman atamasi bekleniyor.',
            ]);

            // Program detay sayfasından geliyorsa: hangi programa ilgi gösterdiğini meta'ya yaz
            if (! empty($data['interested_program_id'])) {
                $meta = is_array($row->application_meta) ? $row->application_meta : [];
                $meta['interested_program'] = [
                    'id'   => (int) $data['interested_program_id'],
                    'name' => isset($data['interested_program']) ? (string) $data['interested_program'] : null,
                    'source' => 'program_detail_cta',
                ];
                $row->application_meta = $meta;
                $row->save();
            }

            return [$guestUser, $generatedPassword, $row];
        });

        // GDPR Madde 7 — Rıza kaydı: hangi versiyon KVKK metnini ne zaman hangi IP'den onayladı
        try {
            ConsentRecord::query()->create([
                'company_id'     => $row->company_id,
                'user_id'        => $guestUser?->id,
                'application_id' => $row->id,
                'consent_type'   => 'kvkk',
                'version'        => config('app.kvkk_version', '2026-01'),
                'ip_address'     => $request->ip(),
                'user_agent'     => mb_substr((string) $request->userAgent(), 0, 500),
                'accepted_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $this->leadSourceTrackingService->captureFromGuestApplication($row);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->queueOnRegisterNotifications($row, $assignedSeniorEmail, $generatedPassword);

        // Real-time ping → manager paneli anlik bildirim alir (Pusher).
        // Down olursa registration akisi etkilenmez; toast dusmez sadece.
        try {
            broadcast(new \App\Events\NewLeadCreated($row));
        } catch (\Throwable $e) {
            report($e);
        }

        // Geçici şifre cache'e yaz (5 dk) — tracking token ile çek
        if ($generatedPassword) {
            \Cache::put("apply_password_{$row->tracking_token}", [
                'password' => $generatedPassword,
                'email'    => $row->email,
                'senior'   => $assignedSeniorEmail ?: null,
            ], now()->addMinutes(5));
        }

        return redirect()->route('apply.success', ['token' => $row->tracking_token]);
    }

    public function success(Request $request)
    {
        $token = (string) $request->query('token', '');
        abort_if($token === '', 404);

        $row = GuestApplication::query()->where('tracking_token', $token)->firstOrFail();

        // Cache'den şifre bilgisini al (ilk gösterimde mevcut, sonra kaybolur)
        $cred = \Cache::pull("apply_password_{$token}");

        return view('apply.success', [
            'row'            => $row,
            'portalEmail'    => $cred['email'] ?? $row->email,
            'portalPassword' => $cred['password'] ?? null,
            'assignedSenior' => $cred['senior'] ?? null,
        ]);
    }


    private function generateTrackingToken(): string
    {
        do {
            $token = strtoupper(Str::random(12));
            $token = preg_replace('/[^A-Z0-9]/', 'X', $token) ?: strtoupper(Str::random(12));
        } while (GuestApplication::query()->where('tracking_token', $token)->exists());

        return $token;
    }

    private function buildApplySuggestions(int $limit = 120): array
    {
        $companyId         = (int) (TenantContext::writeId() ?? 0);
        $recentWindowStart = now()->subMonths(12);
        $maxRecentRows     = max(200, $limit * 6);

        $recentDealers = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('dealer_code')
            ->where('dealer_code', '!=', '')
            ->orderByDesc('created_at')
            ->limit($maxRecentRows)
            ->pluck('dealer_code');
        $popularDealers = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('created_at', '>=', $recentWindowStart)
            ->whereNotNull('dealer_code')
            ->where('dealer_code', '!=', '')
            ->selectRaw('dealer_code, COUNT(*) as total, MAX(created_at) as last_used_at')
            ->groupBy('dealer_code')
            ->orderByDesc('total')
            ->orderByDesc('last_used_at')
            ->limit($limit * 2)
            ->pluck('dealer_code');
        $activeDealers = Dealer::query()
            ->withoutGlobalScope('company')
            ->where('is_archived', false)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->limit($limit * 2)
            ->pluck('code');

        $recentCampaignCodes = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('campaign_code')
            ->where('campaign_code', '!=', '')
            ->orderByDesc('created_at')
            ->limit($maxRecentRows)
            ->pluck('campaign_code');
        $popularCampaignCodes = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('created_at', '>=', $recentWindowStart)
            ->whereNotNull('campaign_code')
            ->where('campaign_code', '!=', '')
            ->selectRaw('campaign_code, COUNT(*) as total, MAX(created_at) as last_used_at')
            ->groupBy('campaign_code')
            ->orderByDesc('total')
            ->orderByDesc('last_used_at')
            ->limit($limit * 2)
            ->pluck('campaign_code');
        $allCampaignNames = MarketingCampaign::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderByDesc('updated_at')
            ->limit($limit * 3)
            ->pluck('name');
        $campaignCodesFromUtm = MarketingCampaign::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('utm_params')
            ->orderByDesc('updated_at')
            ->limit($limit * 3)
            ->get(['utm_params'])
            ->flatMap(function (MarketingCampaign $campaign) {
                $utm = is_array($campaign->utm_params) ? $campaign->utm_params : [];
                return [
                    trim((string) ($utm['campaign_code'] ?? '')),
                    trim((string) ($utm['utm_campaign'] ?? '')),
                    trim((string) ($utm['code'] ?? '')),
                ];
            });

        $recentBranches = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->orderByDesc('created_at')
            ->limit($maxRecentRows)
            ->pluck('branch');
        $popularBranches = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('created_at', '>=', $recentWindowStart)
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->selectRaw('branch, COUNT(*) as total, MAX(created_at) as last_used_at')
            ->groupBy('branch')
            ->orderByDesc('total')
            ->orderByDesc('last_used_at')
            ->limit($limit * 2)
            ->pluck('branch');

        return [
            'dealer_codes' => $this->mergeUniqueSuggestions([$recentDealers, $popularDealers, $activeDealers], $limit),
            'campaign_values' => $this->mergeUniqueSuggestions([$recentCampaignCodes, $popularCampaignCodes, $campaignCodesFromUtm, $allCampaignNames], $limit),
            'branch_values' => $this->mergeUniqueSuggestions([$recentBranches, $popularBranches], $limit),
            'meta' => [
                'window_months' => 12,
                'strategy' => 'recent_then_popular_then_active',
            ],
        ];
    }

    /**
     * @param  array<int, Collection<int, mixed>>  $sets
     * @return array<int, string>
     */
    private function mergeUniqueSuggestions(array $sets, int $limit): array
    {
        $merged = collect();
        foreach ($sets as $set) {
            $values = collect($set)
                ->map(fn ($v) => trim((string) $v))
                ->filter(fn ($v) => $v !== '');
            $merged = $merged->merge($values);
        }

        return $merged
            ->unique(fn (string $v) => mb_strtolower($v))
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{code:string,label:string}>
     */
    private function getLeadSourceOptions(): array
    {
        $rows = LeadSourceOption::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['code', 'label'])
            ->map(fn (LeadSourceOption $r) => [
                'code' => (string) $r->code,
                'label' => (string) $r->label,
            ])
            ->values()
            ->all();

        if ($rows !== []) {
            return $rows;
        }

        return [
            ['code' => 'organic', 'label' => 'Organik / Diger'],
            ['code' => 'google', 'label' => 'Google'],
            ['code' => 'instagram', 'label' => 'Instagram'],
            ['code' => 'youtube', 'label' => 'YouTube'],
            ['code' => 'dealer', 'label' => 'Bayi'],
            ['code' => 'referral', 'label' => 'Referans'],
        ];
    }

    private function getApplyKvkkText(): string
    {
        $value = MarketingAdminSetting::query()
            ->where('setting_key', 'apply_form.kvkk_text')
            ->value('setting_value');

        $text = is_array($value) ? (string) ($value['text'] ?? '') : (string) $value;
        $text = trim($text);

        if ($text !== '') {
            return $text;
        }

        // Veri sorumlusu, başvurunun yazılacağı FİRMADIR. Sabit "MentorDE" yazmak
        // partner firmanın öğrencisine hukuken yanlış bilgi vermek olurdu:
        // verisini işleyen kurum MentorDE değil, kendi danışmanlık firması.
        $controller = trim((string) (config('brand.legal_name') ?: config('brand.name') ?: ''));
        $controller = $controller !== '' ? $controller : 'kurumumuz';

        return "KVKK AYDINLATMA METNI\n\n"
            ."Kisisel verileriniz {$controller} tarafindan basvuru surecinin yurutulmesi, iletisim kurulmasi "
            ."ve ilgili kurumlarla basvuru operasyonlarinin tamamlanmasi amaciyla islenir.\n\n"
            ."Bu metin Config ekranindan manager tarafindan guncellenebilir.";
    }

    private function pickAutoSeniorEmail(string $applicationType = ''): ?string
    {
        $type = strtolower(trim($applicationType));
        $seniors = User::query()
            ->whereIn('role', [User::ROLE_SENIOR, User::ROLE_MENTOR])
            ->where('is_active', true)
            ->where('auto_assign_enabled', true)
            ->orderBy('id')
            ->get(['email', 'max_capacity', 'senior_type']);

        if ($seniors->isEmpty()) {
            return null;
        }

        $matched = $type === ''
            ? $seniors
            : $seniors->filter(function (User $user) use ($type) {
                $seniorType = strtolower(trim((string) ($user->senior_type ?? '')));
                return $seniorType === '' || $seniorType === $type;
            })->values();

        $pool = $matched->isNotEmpty() ? $matched : $seniors;
        $emails = $pool->pluck('email')->filter()->values();
        if ($emails->isEmpty()) {
            return null;
        }

        $studentLoads = StudentAssignment::query()
            ->whereIn('senior_email', $emails)
            ->where('is_archived', false)
            ->selectRaw('senior_email, COUNT(*) as total')
            ->groupBy('senior_email')
            ->pluck('total', 'senior_email');

        $guestLoads = GuestApplication::query()
            ->whereIn('assigned_senior_email', $emails)
            ->where('converted_to_student', false)
            ->where('is_archived', false)
            ->selectRaw('assigned_senior_email, COUNT(*) as total')
            ->groupBy('assigned_senior_email')
            ->pluck('total', 'assigned_senior_email');

        $eligible = $pool->filter(function (User $senior) use ($studentLoads, $guestLoads) {
            $email = (string) ($senior->email ?? '');
            if ($email === '') {
                return false;
            }
            $load = (int) ($studentLoads[$email] ?? 0) + (int) ($guestLoads[$email] ?? 0);
            if (!$senior->max_capacity) {
                return true;
            }
            return $load < (int) $senior->max_capacity;
        })->values();

        if ($eligible->isEmpty()) {
            return null;
        }

        $selected = $eligible->sortBy(function (User $senior) use ($studentLoads, $guestLoads) {
            $email = (string) ($senior->email ?? '');
            return (int) ($studentLoads[$email] ?? 0) + (int) ($guestLoads[$email] ?? 0);
        })->first();

        return $selected ? (string) $selected->email : null;
    }

    /**
     * @return array{0:?User,1:?string}
     */
    private function ensureGuestPortalUser(string $firstName, string $lastName, string $email): array
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return [null, null];
        }

        // SECURITY: Mevcut kullanıcıya dokunma — re-apply'da şifre sıfırlanmamalı.
        // (Eski kod guest role için şifreyi random yeniliyordu; saldırgan duplicate
        //  başvuruyu arşivlenmiş hale getirip email'le yeni /apply açarak kurbanın
        //  şifresini çalabiliyordu. Şifremi unuttum flow'u /password/reset'te mevcut.)
        $existing = User::query()->where('email', $email)->first();
        if ($existing) {
            return [$existing, null];
        }

        $plainPassword = Str::random(12);
        $name = trim($firstName.' '.$lastName);
        if ($name === '') {
            $name = 'Guest User';
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'role' => User::ROLE_GUEST,
            'is_active' => true,
            'password' => $plainPassword, // 'hashed' cast DB'ye yazmadan önce hash'ler
            // Guest self-registration → mail doğrulama zorunlu.
            // Welcome maildeki signed URL'e tıklayana kadar EnsureEmailIsVerified
            // middleware /email/verify notice sayfasına yönlendirir.
            'email_verified_at' => null,
        ]);

        return [$user, $plainPassword];
    }

    private function queueOnRegisterNotifications(GuestApplication $row, ?string $seniorEmail, ?string $generatedPassword): void
    {
        $guestBody  = "Merhaba {$row->first_name},\n\n";
        $guestBody .= "MentorDE'ye hoş geldiniz! Başvurunuz başarıyla alındı.\n\n";
        $guestBody .= "Takip kodunuz: {$row->tracking_token}\n\n";

        if ($generatedPassword) {
            $user = \App\Models\User::query()->where('email', $row->email)->first();
            if ($user) {
                // Plaintext giriş bilgileri
                $guestBody .= "──────────────────────────────────\n";
                $guestBody .= "🔐 GİRİŞ BİLGİLERİNİZ\n";
                $guestBody .= "──────────────────────────────────\n";
                $guestBody .= "📧 Kullanıcı: {$row->email}\n";
                $guestBody .= "🔑 Şifre: {$generatedPassword}\n";
                $guestBody .= "──────────────────────────────────\n\n";

                // Signed verification link (7 gün geçerli)
                $verifyUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'welcome.verify',
                    now()->addDays(7),
                    ['id' => $user->id, 'hash' => sha1((string) $user->email)]
                );

                $guestBody .= "Hesabınızı aktive etmek için aşağıdaki bağlantıya tıklayın:\n";
                $guestBody .= $verifyUrl . "\n\n";
                $guestBody .= "Bağlantıya tıkladıktan sonra giriş ekranında yukarıdaki email ve şifre ile portala erişebilirsiniz.\n\n";
                $guestBody .= "(Bağlantı 7 gün geçerli. Şifrenizi güvenli tutun, ilk girişte değiştirmeniz önerilir.)\n";
            }
        } else {
            $guestBody .= "Portal girişi: ".url('/login')."\n";
        }
        $guestBody .= "\nMentorDE Ekibi";

        $this->notificationService->send([
            'channel'         => 'email',
            'category'        => 'guest_welcome',
            'recipient_email' => (string) $row->email,
            'recipient_phone' => (string) ($row->phone ?? ''),
            'recipient_name'  => trim((string) ($row->first_name.' '.$row->last_name)),
            'subject'         => 'MentorDE — Başvurunuz Alındı',
            'body'            => $guestBody,
            'variables'       => [
                'tracking_token' => (string) $row->tracking_token,
                'guest_id'       => (int) $row->id,
            ],
            'source_type'  => 'guest_application',
            'source_id'    => (string) $row->id,
            'triggered_by' => 'system',
        ]);

        if ($seniorEmail) {
            $seniorUserId = User::query()->where('email', $seniorEmail)->value('id');
            if ($seniorUserId) {
                $this->notificationService->send([
                    'channel'      => 'in_app',
                    'category'     => 'guest_new_assignment',
                    'user_id'      => (int) $seniorUserId,
                    'subject'      => 'Yeni Guest Atamasi',
                    'body'         => "Yeni guest atandi: {$row->first_name} {$row->last_name} | guest_id: {$row->id} | token: {$row->tracking_token}",
                    'variables'    => [
                        'guest_id'       => (int) $row->id,
                        'tracking_token' => (string) $row->tracking_token,
                        'lead_source'    => (string) ($row->lead_source ?? ''),
                    ],
                    'source_type'  => 'guest_application',
                    'source_id'    => (string) $row->id,
                    'triggered_by' => 'system',
                ]);
            }
        }

        $managerIds = User::query()
            ->where('role', User::ROLE_MANAGER)
            ->where('is_active', true)
            ->pluck('id')
            ->filter()
            ->values();

        foreach ($managerIds as $managerId) {
            $this->notificationService->send([
                'channel'      => 'in_app',
                'category'     => 'guest_new_lead',
                'user_id'      => (int) $managerId,
                'subject'      => 'Yeni Guest Basvurusu',
                'body'         => "Yeni guest kaydi alindi: {$row->first_name} {$row->last_name} | source: {$row->lead_source} | branch: ".($row->branch ?: '-'),
                'variables'    => [
                    'guest_id'       => (int) $row->id,
                    'tracking_token' => (string) $row->tracking_token,
                    'lead_source'    => (string) ($row->lead_source ?? ''),
                ],
                'source_type'  => 'guest_application',
                'source_id'    => (string) $row->id,
                'triggered_by' => 'system',
            ]);
        }
    }

}
