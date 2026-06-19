<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Support\ModuleAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Platform Owner — Cross-company SaaS yonetim kontrol paneli.
 *
 * Bu controller Mentorde Platform sahibine ozeldir. 'platform.owner' middleware
 * tarafindan korunur. BelongsToCompany global scope'unu BYPASS eder — Platform
 * Owner tum company'leri gorur.
 *
 * Tabloları cross-company query etmek icin daima `withoutGlobalScope('company')`
 * veya `withoutGlobalScopes()` ile sorgu yap.
 */
class PlatformController extends Controller
{
    // ────────────────────────────────────────────────────────────────────────
    // DASHBOARD
    // ────────────────────────────────────────────────────────────────────────

    public function dashboard(): View
    {
        $now      = CarbonImmutable::now();
        $last30   = $now->subDays(30);
        $next7    = $now->addDays(7);

        $companies = Company::query()->get();

        // KPI metrikleri
        $totalCompanies   = $companies->count();
        $activeCompanies  = $companies->where('is_active', true)->count();
        $totalMrr         = (float) $companies->where('is_active', true)->sum('mrr_eur');
        $activeTrials     = $companies->where('subscription_tier', Company::TIER_TRIAL)
            ->where('is_active', true)->count();

        // Tier dağılımı
        $tierCounts = [
            Company::TIER_TRIAL   => $companies->where('subscription_tier', Company::TIER_TRIAL)->count(),
            Company::TIER_BASIC   => $companies->where('subscription_tier', Company::TIER_BASIC)->count(),
            Company::TIER_GOLD    => $companies->where('subscription_tier', Company::TIER_GOLD)->count(),
            Company::TIER_PREMIUM => $companies->where('subscription_tier', Company::TIER_PREMIUM)->count(),
        ];
        $tierMaxCount = max(1, max($tierCounts));

        // Modül kullanım heatmap — hangi modül kaç company'de aktif
        $moduleUsage = [];
        foreach (array_keys(ModuleAccess::MODULE_META) as $moduleKey) {
            $moduleUsage[$moduleKey] = 0;
        }
        foreach ($companies as $c) {
            $enabled = ModuleAccess::enabledModules((int) $c->id);
            foreach ($enabled as $m) {
                if (isset($moduleUsage[$m])) {
                    $moduleUsage[$m]++;
                }
            }
        }
        arsort($moduleUsage);

        // Son 30 gün eklenen company
        $recentCompanies = Company::query()
            ->where('created_at', '>=', $last30)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Önümüzdeki 7 gün trial bitenler
        $expiringTrials = Company::query()
            ->where('subscription_tier', Company::TIER_TRIAL)
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [$now, $next7])
            ->orderBy('trial_ends_at')
            ->get();

        // Çürük trial — süresi geçmiş ama hala 'trial' tier'da
        $expiredTrials = Company::query()
            ->where('subscription_tier', Company::TIER_TRIAL)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', $now)
            ->count();

        return view('platform.dashboard', [
            'totalCompanies'   => $totalCompanies,
            'activeCompanies'  => $activeCompanies,
            'totalMrr'         => $totalMrr,
            'activeTrials'     => $activeTrials,
            'tierCounts'       => $tierCounts,
            'tierMaxCount'     => $tierMaxCount,
            'moduleUsage'      => $moduleUsage,
            'moduleMeta'       => ModuleAccess::MODULE_META,
            'recentCompanies'  => $recentCompanies,
            'expiringTrials'   => $expiringTrials,
            'expiredTrials'    => $expiredTrials,
            'tierLabels'       => $this->tierLabels(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // COMPANIES LIST
    // ────────────────────────────────────────────────────────────────────────

    public function companies(Request $request): View
    {
        $tierFilter   = trim((string) $request->query('tier', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $search       = trim((string) $request->query('q', ''));
        $sort         = $request->query('sort', 'name');
        $dir          = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['name', 'subscription_tier', 'mrr_eur', 'is_active', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'name';

        $query = Company::query();
        if ($tierFilter !== '' && in_array($tierFilter, Company::TIERS, true)) {
            $query->where('subscription_tier', $tierFilter);
        }
        if ($statusFilter === 'active')   $query->where('is_active', true);
        if ($statusFilter === 'inactive') $query->where('is_active', false);
        if ($statusFilter === 'trial')    $query->where('subscription_tier', Company::TIER_TRIAL);
        if ($statusFilter === 'expired') {
            $query->where('subscription_tier', Company::TIER_TRIAL)
                ->whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '<', now());
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
                    ->orWhere('billing_email', 'like', '%' . $search . '%');
            });
        }
        $companies = $query->orderBy($sort, $dir)->paginate(30)->withQueryString();

        // Her company icin user count cache et
        $companyIds = $companies->pluck('id')->all();
        $studentCounts = User::query()->withoutGlobalScopes()
            ->whereIn('company_id', $companyIds)
            ->where('role', User::ROLE_STUDENT)
            ->groupBy('company_id')
            ->select('company_id', DB::raw('count(*) as total'))
            ->pluck('total', 'company_id')
            ->all();

        return view('platform.companies.index', [
            'companies'     => $companies,
            'studentCounts' => $studentCounts,
            'tierLabels'    => $this->tierLabels(),
            'filters'       => [
                'tier'   => $tierFilter,
                'status' => $statusFilter,
                'q'      => $search,
                'sort'   => $sort,
                'dir'    => $dir,
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // COMPANY DETAY
    // ────────────────────────────────────────────────────────────────────────

    public function showCompany(int $company): View
    {
        $companyModel = Company::query()->where('id', $company)->firstOrFail();

        // Kullanıcı sayıları role bazlı
        $userCounts = User::query()->withoutGlobalScopes()
            ->where('company_id', $companyModel->id)
            ->groupBy('role')
            ->select('role', DB::raw('count(*) as total'))
            ->pluck('total', 'role')
            ->all();

        // Modul listesi — su anda aktif olanlar
        $enabledModules = ModuleAccess::enabledModules((int) $companyModel->id);

        // Son 30 gün aktivite — login sayisi (last_login_at varsa)
        $last30 = now()->subDays(30);
        $recentLogins = 0;
        if (Schema::hasColumn('users', 'last_login_at')) {
            $recentLogins = User::query()->withoutGlobalScopes()
                ->where('company_id', $companyModel->id)
                ->where('last_login_at', '>=', $last30)
                ->count();
        }

        // Son 30 gun eklenen guest application sayisi (mevcutsa)
        $recentApplications = 0;
        if (Schema::hasTable('guest_applications')) {
            $recentApplications = DB::table('guest_applications')
                ->where('company_id', $companyModel->id)
                ->where('created_at', '>=', $last30)
                ->count();
        }

        // Tier limit kullanim oranlari
        $tierConfig = config("subscription_tiers.{$companyModel->subscription_tier}") ?? [];
        $studentLimit = $tierConfig['limits']['students_max'] ?? null;
        $studentTotal = $userCounts[User::ROLE_STUDENT] ?? 0;
        $studentUsagePct = ($studentLimit && $studentLimit > 0)
            ? min(100, round(($studentTotal / $studentLimit) * 100))
            : null;

        return view('platform.companies.show', [
            'company'             => $companyModel,
            'userCounts'          => $userCounts,
            'enabledModules'      => $enabledModules,
            'moduleMeta'          => ModuleAccess::MODULE_META,
            'moduleGroups'        => ModuleAccess::moduleGroups(),
            'recentLogins'        => $recentLogins,
            'recentApplications'  => $recentApplications,
            'tierLabels'          => $this->tierLabels(),
            'tierConfig'          => $tierConfig,
            'studentLimit'        => $studentLimit,
            'studentTotal'        => $studentTotal,
            'studentUsagePct'     => $studentUsagePct,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // TIER UPDATE — modulleri tier'dan otomatik dagit
    // ────────────────────────────────────────────────────────────────────────

    public function updateTier(Request $request, int $company): RedirectResponse
    {
        $companyModel = Company::query()->where('id', $company)->firstOrFail();
        $previousTier = (string) $companyModel->subscription_tier;

        $validator = Validator::make($request->all(), [
            'subscription_tier'      => ['required', 'string', 'in:' . implode(',', Company::TIERS)],
            'trial_ends_at'          => ['nullable', 'date'],
            'subscription_renews_at' => ['nullable', 'date'],
            'billing_email'          => ['nullable', 'email', 'max:190'],
            'mrr_eur'                => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'auto_sync_modules'      => ['nullable'],
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $tier = $request->input('subscription_tier');
        $tierConfig = config("subscription_tiers.{$tier}") ?? [];

        $companyModel->subscription_tier      = $tier;
        $companyModel->trial_ends_at          = $request->input('trial_ends_at') ?: null;
        $companyModel->subscription_renews_at = $request->input('subscription_renews_at') ?: null;
        $companyModel->billing_email          = $request->input('billing_email') ?: null;

        // mrr_eur explicit verilmediyse tier'in default'unu kullan
        $mrrInput = $request->input('mrr_eur');
        $companyModel->mrr_eur = ($mrrInput !== null && $mrrInput !== '')
            ? (float) $mrrInput
            : (float) ($tierConfig['mrr_eur'] ?? 0);

        // auto_sync_modules: tier'dan modulleri otomatik dagit
        if ($request->boolean('auto_sync_modules', true)) {
            $tierModules = $tierConfig['modules'] ?? [];
            if ($tierModules === '*') {
                // Premium = tum modulleri ac
                $companyModel->enabled_modules = ModuleAccess::allModules();
            } elseif (is_array($tierModules) && !empty($tierModules)) {
                $companyModel->enabled_modules = array_values(array_unique($tierModules));
            } else {
                $companyModel->enabled_modules = ['core'];
            }
        }
        $companyModel->save();
        ModuleAccess::flushCache((int) $companyModel->id);

        \App\Models\PlatformAuditLog::record(
            'platform.company.tier_changed',
            [
                'target_type' => 'company',
                'target_id'   => $companyModel->id,
                'company'     => $companyModel->name,
                'new_tier'    => $tier,
                'mrr_eur'     => (float) $companyModel->mrr_eur,
            ],
            \App\Models\PlatformAuditLog::SEVERITY_WARNING
        );

        // Real-time ping: tenant manager'i + tum platform_owner'lar gorur.
        if ($previousTier !== (string) $tier) {
            try {
                broadcast(new \App\Events\TierUpgraded($companyModel, (string) $tier, $previousTier ?: null));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('tier.broadcast_failed', [
                    'company_id' => $companyModel->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', "Tier güncellendi: {$companyModel->name} → " . ($this->tierLabels()[$tier] ?? $tier));
    }

    // ────────────────────────────────────────────────────────────────────────
    // MODULE UPDATE — tier override (manuel toggle)
    // ────────────────────────────────────────────────────────────────────────

    public function updateModules(Request $request, int $company): RedirectResponse
    {
        $companyModel = Company::query()->where('id', $company)->firstOrFail();

        $allModules = array_keys(ModuleAccess::MODULE_META);
        $submitted  = (array) $request->input('modules', []);
        $clean = array_values(array_unique(array_filter(array_map(
            fn ($m) => strtolower(trim((string) $m)),
            $submitted
        ), fn ($m) => in_array($m, $allModules, true))));
        if (!in_array('core', $clean, true)) {
            $clean[] = 'core';
        }

        // doc_request aylik quota (opsiyonel)
        $quotaInput = $request->input('doc_request_monthly_limit');
        $quotaValue = null;
        if ($quotaInput !== null && trim((string) $quotaInput) !== '') {
            $q = (int) $quotaInput;
            $quotaValue = ($q > 0) ? min($q, 10000) : null;
        }

        $companyModel->forceFill([
            'enabled_modules'           => $clean,
            'doc_request_monthly_limit' => $quotaValue,
        ])->save();
        ModuleAccess::flushCache((int) $companyModel->id);

        \App\Models\PlatformAuditLog::record(
            'platform.company.modules_updated',
            [
                'target_type'                => 'company',
                'target_id'                  => $companyModel->id,
                'company'                    => $companyModel->name,
                'enabled_modules'            => $clean,
                'doc_request_monthly_limit'  => $quotaValue,
            ],
            \App\Models\PlatformAuditLog::SEVERITY_INFO
        );

        return back()->with('success', "{$companyModel->name} modülleri güncellendi (" . count($clean) . " aktif)");
    }

    // ────────────────────────────────────────────────────────────────────────
    // YENI COMPANY OLUSTUR
    // ────────────────────────────────────────────────────────────────────────

    public function createCompany(): View
    {
        return view('platform.companies.create', [
            'tierLabels' => $this->tierLabels(),
            'tiers'      => config('subscription_tiers'),
        ]);
    }

    public function storeCompany(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name'              => ['required', 'string', 'max:190'],
            'code'              => ['nullable', 'string', 'max:40', 'unique:companies,code'],
            'billing_email'     => ['nullable', 'email', 'max:190'],
            'subscription_tier' => ['required', 'string', 'in:' . implode(',', Company::TIERS)],
            'trial_ends_at'     => ['nullable', 'date'],
            'admin_name'        => ['required', 'string', 'max:120'],
            'admin_email'       => ['required', 'email', 'max:190', 'unique:users,email'],
            'admin_password'    => ['required', 'string', 'min:8', 'max:120'],
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $tier = $request->input('subscription_tier');
        $tierConfig = config("subscription_tiers.{$tier}") ?? [];
        $code = $request->input('code') ?: Str::slug($request->input('name'), '_');
        // Code unique olsun
        $base = $code; $i = 1;
        while (Company::query()->where('code', $code)->exists()) {
            $code = $base . '_' . (++$i);
        }

        // Trial default 14 gun
        $trialEnds = $request->input('trial_ends_at');
        if ($tier === Company::TIER_TRIAL && empty($trialEnds)) {
            $trialEnds = now()->addDays(14)->toDateString();
        }

        // Modulleri tier'dan al
        $tierModules = $tierConfig['modules'] ?? [];
        if ($tierModules === '*') {
            $modules = ModuleAccess::allModules();
        } elseif (is_array($tierModules) && !empty($tierModules)) {
            $modules = array_values(array_unique($tierModules));
        } else {
            $modules = ['core'];
        }

        $company = null;
        DB::transaction(function () use ($request, $tier, $tierConfig, $code, $trialEnds, $modules, &$company): void {
            $company = Company::query()->create([
                'name'              => $request->input('name'),
                'code'              => $code,
                'is_active'         => true,
                'enabled_modules'   => $modules,
                'subscription_tier' => $tier,
                'trial_ends_at'     => $trialEnds ?: null,
                'billing_email'     => $request->input('billing_email') ?: null,
                'mrr_eur'           => (float) ($tierConfig['mrr_eur'] ?? 0),
            ]);

            User::query()->create([
                'company_id'        => $company->id,
                'name'              => $request->input('admin_name'),
                'email'             => strtolower(trim($request->input('admin_email'))),
                'password'          => Hash::make($request->input('admin_password')),
                'role'              => User::ROLE_MANAGER,
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);
        });

        if ($company) {
            ModuleAccess::flushCache((int) $company->id);

            \App\Models\PlatformAuditLog::record(
                'platform.company.created',
                [
                    'target_type'  => 'company',
                    'target_id'    => $company->id,
                    'company'      => $company->name,
                    'code'         => $company->code,
                    'tier'         => $company->subscription_tier,
                    // admin_email audit'e yazilmaz — tenant kisisel verisi (izolasyon).
                ],
                \App\Models\PlatformAuditLog::SEVERITY_WARNING
            );
        }

        return redirect()
            ->route('platform.companies.show', ['company' => $company?->id])
            ->with('success', "{$request->input('name')} başarıyla oluşturuldu. Manager hesabı: {$request->input('admin_email')}");
    }

    // ────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ────────────────────────────────────────────────────────────────────────

    private function tierLabels(): array
    {
        $tiers = config('subscription_tiers', []);
        $out = [];
        foreach ($tiers as $key => $cfg) {
            $out[$key] = $cfg['label'] ?? ucfirst($key);
        }
        return $out;
    }

    /**
     * Impersonate olarak Customer Manager paneline gecici erisim.
     * Audit log + session marker + flash mesaj.
     */
    public function impersonate(Request $request, int $company): RedirectResponse
    {
        $user = $request->user();
        if (!$user || (string) $user->role !== \App\Models\User::ROLE_PLATFORM_OWNER) {
            abort(403);
        }
        // Veri guvenligi / DSGVO: impersonation varsayilan KAPALI. Platform Owner
        // musteri sirketlerinin verisine dogrudan giremez (config/platform.php).
        if (! config('platform.impersonation_enabled', false)) {
            abort(403, 'Impersonation devre disi: Platform Owner musteri sirketlerine giremez (veri guvenligi).');
        }
        $target = \App\Models\Company::query()->find($company);
        if (!$target) {
            return back()->withErrors(['impersonate' => 'Sirket bulunamadi.']);
        }

        // Session marker
        session([
            'impersonating_company_id'   => $target->id,
            'impersonating_company_name' => $target->name,
            'impersonating_started_at'   => now()->toIso8601String(),
            'impersonating_original_user_id' => $user->id,
        ]);

        // Audit log (graceful) — hem yeni PlatformAuditLog tablosuna hem
        // eski Log::info'a yaz (geciste her ikisi de tutulsun).
        try {
            \App\Models\PlatformAuditLog::record(
                'platform.impersonate.start',
                [
                    'target_type'         => 'company',
                    'target_id'           => $target->id,
                    'target_company_name' => $target->name,
                ],
                \App\Models\PlatformAuditLog::SEVERITY_WARNING
            );
            \Illuminate\Support\Facades\Log::info('platform.impersonate.start', [
                'platform_owner_id'   => $user->id,
                'platform_owner_email'=> $user->email,
                'target_company_id'   => $target->id,
                'target_company_name' => $target->name,
                'ip'                  => $request->ip(),
            ]);
        } catch (\Throwable $e) {
        }

        return redirect('/manager/dashboard')
            ->with('status', "Impersonate baslatildi: {$target->name}");
    }

    /**
     * Impersonate sonlandir.
     */
    public function stopImpersonating(Request $request): RedirectResponse
    {
        $user = $request->user();
        $companyName = session('impersonating_company_name', '?');

        // Audit log
        try {
            $duration = session('impersonating_started_at')
                ? now()->diffInSeconds(\Carbon\Carbon::parse(session('impersonating_started_at')))
                : null;

            \App\Models\PlatformAuditLog::record(
                'platform.impersonate.stop',
                [
                    'target_type'         => 'company',
                    'target_id'           => session('impersonating_company_id'),
                    'target_company_name' => $companyName,
                    'duration_seconds'    => $duration,
                ],
                \App\Models\PlatformAuditLog::SEVERITY_INFO
            );

            \Illuminate\Support\Facades\Log::info('platform.impersonate.stop', [
                'platform_owner_id'   => $user?->id,
                'platform_owner_email'=> $user?->email,
                'target_company_name' => $companyName,
                'duration_seconds'    => $duration,
            ]);
        } catch (\Throwable $e) {
        }

        session()->forget([
            'impersonating_company_id',
            'impersonating_company_name',
            'impersonating_started_at',
            'impersonating_original_user_id',
        ]);

        return redirect('/platform/companies')
            ->with('status', "Impersonate sonlandirildi: {$companyName}");
    }
}
