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
            // Firma PANEL kullanıcıları — hesap sahibi olarak bizimle sözleşmeli
            // taraf. Öğrenci ve aday hesapları BİLEREK yok: onlar müşterinin
            // müşterisi, kişisel verileri bu konsolda gösterilmez.
            'staffAccounts'       => User::query()->withoutGlobalScopes()
                ->where('company_id', $companyModel->id)
                ->whereIn('role', array_values(array_diff(User::ADMIN_PANEL_ROLES, [User::ROLE_PLATFORM_OWNER])))
                ->orderBy('role')
                ->get(['id', 'name', 'email', 'role', 'is_active', 'password_must_change']),
            // Firmanın kendi mail taşıyıcısı (şifreler görünmez, model gizler)
            'mailSetting'         => \App\Models\CompanyMailSetting::query()
                ->where('company_id', $companyModel->id)
                ->first(),
            // Üst firma seçici için — kendisi ve alt firmaları hariç (döngü olmasın)
            'allCompanies'        => Company::query()
                ->whereNotIn('id', array_merge(
                    [(int) $companyModel->id],
                    Company::descendantIds((int) $companyModel->id)
                ))
                ->orderBy('name')
                ->get(['id', 'name', 'brand_name']),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // TIER UPDATE — modulleri tier'dan otomatik dagit
    // ────────────────────────────────────────────────────────────────────────

    /**
     * White-label marka bilgilerini güncelle.
     *
     * Marka `App\Support\Brand` tarafından üç katmandan çözülür:
     *   config/brand.php (.env) → companies.brand_* (burası) → marketing_admin_settings
     *
     * DİKKAT: .env katmanı YALNIZCA ana şirkete uygulanır. Partner firma marka adını
     * boş bırakırsa kendi ŞİRKET ADINA düşer, platformun markasına değil — aksi halde
     * firma kendi adresinde MentorDE logosunu görürdü.
     */
    public function updateBranding(Request $request, int $company): RedirectResponse
    {
        $companyModel = Company::query()->where('id', $company)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'brand_name'          => ['nullable', 'string', 'max:120'],
            'brand_logo_url'      => ['nullable', 'string', 'max:500'],
            'brand_primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            // Giden mailin gönderen adresi. Alan adı mail sağlayıcısında
            // DOĞRULANMIŞ olmalı; doğrulanmamış adres bu şirketin tüm
            // mailini sessizce kırar (bkz. Brand::applyMailIdentity).
            'mail_from_address'   => ['nullable', 'email', 'max:190'],
            'primary_domain'      => [
                'nullable', 'string', 'max:190',
                \Illuminate\Validation\Rule::unique('companies', 'primary_domain')->ignore($companyModel->id),
            ],
            // Üst firma — personeli bu şirketin verisini de görür (bkz. User::SUPERVISING_ROLES)
            'parent_company_id' => ['nullable', 'integer', 'exists:companies,id'],
            // Başvuru linkinin adresi: /apply/{slug}
            'slug' => [
                'nullable', 'string', 'max:58', 'regex:/^[a-z0-9][a-z0-9_-]{1,57}$/',
                \Illuminate\Validation\Rule::notIn(['success', 'partner', 'onay', 'suggestions', 'lead-sources']),
                \Illuminate\Validation\Rule::unique('companies', 'slug')->ignore($companyModel->id),
            ],
        ], [
            'brand_primary_color.regex' => 'Renk #rrggbb formatında olmalı (örn. #0d9488).',
            'primary_domain.unique'     => 'Bu domain başka bir şirkete tanımlı.',
            'slug.regex'                => 'Link adresi küçük harf, rakam, tire ve alt çizgiden oluşmalı (örn. abc-egitim).',
            'slug.unique'               => 'Bu link adresi başka bir şirkete tanımlı.',
            'slug.not_in'               => 'Bu link adresi sistem tarafından kullanılıyor, başka bir ad seçin.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Hiyerarşi döngüsü: A'nın üstü B, B'nin üstü A olursa görünürlük
        // hesabı kendi kuyruğunda döner ve iki firma birbirinin verisini görür.
        $parentId = (int) $request->input('parent_company_id', 0) ?: null;

        if ($parentId !== null) {
            $invalid = $parentId === (int) $companyModel->id
                || in_array($parentId, Company::descendantIds((int) $companyModel->id), true);

            if ($invalid) {
                return back()
                    ->withInput()
                    ->withErrors(['parent_company_id' => 'Bir şirket kendisinin ya da kendi alt firmasının altına alınamaz.']);
            }
        }

        $companyModel->parent_company_id = $parentId;

        // "https://a.firma.com/" gibi girdileri normalize et
        $domain = strtolower(trim((string) $request->input('primary_domain', '')));
        $domain = trim((string) preg_replace('#^https?://#', '', $domain), '/');

        $companyModel->brand_name          = trim((string) $request->input('brand_name', '')) ?: null;
        $companyModel->brand_logo_url      = trim((string) $request->input('brand_logo_url', '')) ?: null;
        $companyModel->brand_primary_color = trim((string) $request->input('brand_primary_color', '')) ?: null;
        $companyModel->primary_domain      = $domain !== '' ? $domain : null;
        $companyModel->slug                = strtolower(trim((string) $request->input('slug', ''))) ?: null;

        // Gönderen adresi brand_overrides içinde tutuluyor (config/brand.php'nin
        // şeklini taklit eder). Alan hiç gönderilmediyse mevcut değere dokunma —
        // kısmi güncelleme adresi sessizce silmemeli.
        if ($request->has('mail_from_address')) {
            $overrides = $companyModel->brand_overrides;

            if (is_string($overrides)) {
                $overrides = json_decode($overrides, true);
            }

            $overrides = is_array($overrides) ? $overrides : [];
            $mailFrom  = strtolower(trim((string) $request->input('mail_from_address', '')));

            if ($mailFrom !== '') {
                $overrides['mail_from_address'] = $mailFrom;
            } else {
                unset($overrides['mail_from_address']);
            }

            $companyModel->brand_overrides = $overrides ?: null;
        }

        // Form her zaman gizli 0 + checkbox 1 gönderir. Alan hiç gelmediyse (kısmi
        // güncelleme, API) mevcut değer korunur — boolean() sessizce false yapardı.
        if ($request->has('public_marketing')) {
            $companyModel->public_marketing = $request->boolean('public_marketing');
        }

        if ($request->has('is_public_portal')) {
            $companyModel->is_public_portal = $request->boolean('is_public_portal');
        }

        // Panel modu: partner firmalar sade takip penceresi görür.
        if ($request->has('panel_mode')) {
            $companyModel->panel_mode = $request->input('panel_mode') === Company::PANEL_PARTNER
                ? Company::PANEL_PARTNER
                : Company::PANEL_FULL;
        }

        $companyModel->save(); // Company::saved observer marka cache'ini temizler

        \App\Models\PlatformAuditLog::record(
            'platform.company.branding_updated',
            [
                'target_type' => 'company',
                'target_id'   => $companyModel->id,
                'company'     => $companyModel->name,
                'brand_name'  => $companyModel->brand_name,
                'domain'      => $companyModel->primary_domain,
            ]
        );

        return back()->with('status', 'Marka bilgileri güncellendi.');
    }

    /**
     * Şirketi askıya al / geri aç.
     *
     * Pasif şirket: başvuru linki 404 verir, host'undan marka çözülmez, MRR
     * toplamına girmez (bkz. dashboard `where('is_active', true)`).
     *
     * ANA ŞİRKET ASKIYA ALINAMAZ: varsayılan şirket çözümlemesi ona bağlı;
     * kapatmak tüm platformu kendi ayağından vurmak olurdu.
     */
    public function updateStatus(Request $request, int $company): RedirectResponse
    {
        $companyModel = Company::query()->where('id', $company)->firstOrFail();

        $request->validate(['is_active' => ['required', 'boolean']]);

        $makeActive = $request->boolean('is_active');

        if (!$makeActive && \App\Support\Brand::isPrimary($companyModel)) {
            return back()->withErrors([
                'is_active' => 'Ana şirket askıya alınamaz — varsayılan şirket çözümlemesi buna bağlı.',
            ]);
        }

        $companyModel->is_active = $makeActive;
        $companyModel->save();

        \App\Models\PlatformAuditLog::record(
            $makeActive ? 'platform.company.activated' : 'platform.company.suspended',
            [
                'target_type' => 'company',
                'target_id'   => $companyModel->id,
                'company'     => $companyModel->name,
            ]
        );

        $staffCount = User::query()->withoutGlobalScopes()
            ->where('company_id', $companyModel->id)
            ->where('is_active', true)
            ->count();

        $message = $makeActive
            ? $companyModel->name . ' yeniden aktif.'
            : $companyModel->name . ' askıya alındı — başvuru linki kapandı, MRR toplamından çıktı.';

        if (!$makeActive && $staffCount > 0) {
            $message .= ' Dikkat: bu şirkette ' . $staffCount . ' aktif kullanıcı var, panele girebilirler ama veri göremezler.';
        }

        return back()->with('status', $message);
    }

    /**
     * Alt firmanın yetki tavanını ayarla.
     *
     * Rol yetkiyi VERİR, tavan DARALTIR. Buraya işaretlenen her yetki o
     * firmanın (ve altındaki firmaların) kullanıcılarından alınır.
     */
    public function updatePermissionCeiling(Request $request, int $company): RedirectResponse
    {
        $companyModel = Company::query()->where('id', $company)->firstOrFail();

        if (\App\Support\Brand::isPrimary($companyModel)) {
            return back()->withErrors([
                'denied_permission_codes' => 'Ana şirkete yetki kısıtı konulamaz — kendi platformunuzu kilitlersiniz.',
            ]);
        }

        $denied = \App\Support\PermissionCeiling::sanitize($request->input('denied_permission_codes', []));

        $companyModel->denied_permission_codes = $denied !== [] ? $denied : null;
        $companyModel->save();

        // Tavan önbelleği şirket ağacıyla birlikte tutuluyor.
        Company::flushHierarchyCache();

        \App\Models\PlatformAuditLog::record('platform.company.permission_ceiling_updated', [
            'target_type' => 'company',
            'target_id'   => $companyModel->id,
            'company'     => $companyModel->name,
            'denied'      => $denied,
        ]);

        return back()->with('status', $denied === []
            ? $companyModel->name . ' için kısıt kaldırıldı — rolünün verdiği tüm yetkiler geçerli.'
            : $companyModel->name . ' için ' . count($denied) . ' yetki kısıtlandı. Alt firmaları da bağlar.');
    }

    /**
     * Firma yöneticisinin şifresini sıfırla.
     *
     * NEDEN GEREKLİ: firma geçici şifresini kaybederse tek çıkış yolu
     * /forgot-password idi ve o da ilgili posta kutusuna erişim gerektiriyordu.
     * Yeni bir partner devreye alınırken bu tıkanma noktası oluyordu.
     *
     * ── IMPERSONATION'DAN FARKI ─────────────────────────────────────────
     * Impersonation bilerek KAPALI: platform sahibi müşterinin verisine
     * SESSİZCE giremez. Şifre sıfırlama sessiz değildir — eski şifre çalışmaz
     * olur ve firma bunu hemen fark eder. Hesap kurtarma, servis sağlayıcının
     * meşru işidir; veriyi gizlice okumak değildir.
     *
     * Yeni şifre TEK SEFER gösterilir ve ilk girişte değiştirilmek zorundadır.
     */
    /**
     * Firmaya panel kullanıcısı aç.
     *
     * ── NEDEN GEREKLİ ────────────────────────────────────────────────────
     * Platform konsolu mevcut hesapları listeliyor ve şifrelerini
     * sıfırlayabiliyordu ama YENİ hesap açamıyordu. Firma kurulurken tek
     * yönetici oluşuyor; o hesap silinirse ya da (YourGermanUni gibi) firma
     * kullanıcısız kalırsa şirkete girmenin hiçbir yolu kalmıyordu —
     * başvuru linki de personelsiz firmada 404 veriyor.
     *
     * ── YALNIZCA YÖNETİCİ ROLÜ ───────────────────────────────────────────
     * Burası bir "ilk hesabı aç" kapısı. Danışman, finans, operasyon gibi
     * roller firmanın kendi personel ekranından açılır. Özellikle danışman:
     * bir firmaya danışman eklemek operasyonu oraya taşır
     * (bkz. Company::operatingCompanyId) — bu karar platform konsolundan
     * yanlışlıkla verilmemeli.
     */
    public function storeStaff(Request $request, int $company): RedirectResponse
    {
        $companyModel = Company::query()->where('id', $company)->firstOrFail();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:120'],
            // GLOBAL tekil: aynı adres başka bir firmada olsa bile alınamaz.
            'email' => ['required', 'email', 'max:190', \Illuminate\Validation\Rule::unique('users', 'email')],
        ], [
            'email.unique' => 'Bu e-posta adresi başka bir hesapta kullanılıyor.',
        ]);

        if (! $companyModel->canAddStaffUser()) {
            return back()->withInput()->withErrors([
                'staff' => sprintf(
                    'Bu firmanın paket sınırı dolu (%d kullanıcı). Üst pakete geçirin.',
                    (int) $companyModel->userLimit()
                ),
            ]);
        }

        $tempPassword = Str::password(14, true, true, false, false);

        $target = User::create([
            'name'                 => trim($data['name']),
            'email'                => strtolower(trim($data['email'])),
            'password'             => Hash::make($tempPassword),
            'role'                 => User::ROLE_MANAGER,
            'company_id'           => $companyModel->id,
            'is_active'            => true,
            'email_verified_at'    => now(),
            'password_must_change' => true,
        ]);

        \App\Models\PlatformAuditLog::record('platform.company.staff_created', [
            'target_type' => 'user',
            'target_id'   => $target->id,
            'company'     => $companyModel->name,
            'role'        => User::ROLE_MANAGER,
            // Şifre audit'e YAZILMAZ.
        ]);

        return back()->with('status',
            $target->email . ' oluşturuldu. Geçici şifre: ' . $tempPassword
            . ' — bu şifre yalnızca ŞİMDİ gösteriliyor, kaydedin. '
            . 'Kullanıcı ilk girişte değiştirmek zorunda.'
        );
    }

    public function resetStaffPassword(Request $request, int $company, int $user): RedirectResponse
    {
        $companyModel = Company::query()->where('id', $company)->firstOrFail();

        $target = User::query()->withoutGlobalScopes()
            ->where('id', $user)
            ->where('company_id', $companyModel->id)
            ->first();

        if (!$target) {
            return back()->withErrors(['password' => 'Kullanıcı bu şirkette bulunamadı.']);
        }

        // Yalnızca PANEL kullanıcıları. Öğrenci ve aday hesaplarının şifresini
        // platform sahibi sıfırlayamaz — onlar müşterinin müşterisidir ve
        // hesap ilişkisi bizimle değil firmayladır.
        $resettable = array_values(array_diff(User::ADMIN_PANEL_ROLES, [User::ROLE_PLATFORM_OWNER]));

        if (!in_array((string) $target->role, $resettable, true)) {
            return back()->withErrors([
                'password' => 'Yalnızca firma panel kullanıcılarının şifresi sıfırlanabilir.',
            ]);
        }

        $newPassword = Str::password(14, true, true, false, false);

        $target->forceFill([
            'password' => Hash::make($newPassword),
            'password_must_change' => true,
        ])->save();

        \App\Models\PlatformAuditLog::record('platform.company.staff_password_reset', [
            'target_type' => 'user',
            'target_id'   => $target->id,
            'company'     => $companyModel->name,
            'role'        => (string) $target->role,
            // Şifre audit'e YAZILMAZ.
        ]);

        return back()->with('status',
            $target->email . ' için yeni geçici şifre: ' . $newPassword
            . ' — bu şifre yalnızca ŞİMDİ gösteriliyor, kaydedin. '
            . 'Kullanıcı ilk girişte değiştirmek zorunda.'
        );
    }

    /**
     * Firma panel hesabının e-postasını değiştir.
     *
     * E-posta aynı zamanda GİRİŞ KİMLİĞİ; değiştirmek hesabı devretmek gibidir.
     * Bu yüzden yalnızca panel hesapları için ve denetim kaydıyla.
     *
     * SENKRON SORUNU YOK: e-posta tek bir yerde (`users.email`) tutuluyor,
     * kopyası çıkarılmıyor. Firma kendi panelinden değiştirdiğinde burada da
     * anında değişmiş görünür; ters yönde de öyle.
     */
    public function updateStaffEmail(Request $request, int $company, int $user): RedirectResponse
    {
        $companyModel = Company::query()->where('id', $company)->firstOrFail();

        $target = User::query()->withoutGlobalScopes()
            ->where('id', $user)
            ->where('company_id', $companyModel->id)
            ->first();

        if (!$target) {
            return back()->withErrors(['email' => 'Kullanıcı bu şirkette bulunamadı.']);
        }

        $resettable = array_values(array_diff(User::ADMIN_PANEL_ROLES, [User::ROLE_PLATFORM_OWNER]));

        if (!in_array((string) $target->role, $resettable, true)) {
            return back()->withErrors([
                'email' => 'Yalnızca firma panel kullanıcılarının e-postası değiştirilebilir.',
            ]);
        }

        $validator = Validator::make($request->all(), [
            // users.email GLOBAL unique — kapsam dışı kontrol şart, aksi halde
            // başka şirketteki adresle çakışır ve INSERT/UPDATE patlar.
            'email' => [
                'required', 'email', 'max:190',
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($target->id),
            ],
        ], [
            'email.unique' => 'Bu e-posta adresi başka bir hesapta kullanılıyor.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $previous = (string) $target->email;
        $newEmail = strtolower(trim((string) $request->input('email')));

        if ($previous === $newEmail) {
            return back()->with('status', 'E-posta zaten bu adres.');
        }

        $target->forceFill(['email' => $newEmail])->save();

        \App\Models\PlatformAuditLog::record('platform.company.staff_email_changed', [
            'target_type' => 'user',
            'target_id'   => $target->id,
            'company'     => $companyModel->name,
            'role'        => (string) $target->role,
            // Adresler denetim kaydına yazılmaz — hesap sahibi kişisel verisi.
        ]);

        return back()->with('status',
            'Giriş e-postası güncellendi. Kullanıcı artık ' . $newEmail . ' ile giriş yapmalı; '
            . 'eski adres çalışmaz. Bilgilendirmeyi siz yapın.'
        );
    }

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
        $primaryCode = strtolower(trim((string) config('app.primary_company_code', 'mentorde')));

        return view('platform.companies.create', [
            'tierLabels' => $this->tierLabels(),
            'tiers'      => config('subscription_tiers'),
            // Üst firma seçici — ağacın hangi dalına takılacağını platform sahibi seçer.
            'allCompanies'  => Company::query()->orderBy('name')->get(['id', 'name', 'brand_name', 'code']),
            'defaultParent' => (int) (Company::query()
                ->whereRaw('lower(code) = ?', [$primaryCode])
                ->value('id') ?: 0),
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
            // Ağaçtaki yeri — platform sahibi seçer, boş = bağımsız tenant
            'parent_company_id' => ['nullable', 'integer', 'exists:companies,id'],
            // ── White-label marka (opsiyonel) ──
            // Boş bırakılırsa şirket platformun varsayılan markasını görür.
            // primary_domain doluysa o adresten gelen ziyaretçi bu markayı görür
            // (bkz. App\Support\Brand + SetCompanyContext host çözümlemesi).
            'brand_name'          => ['nullable', 'string', 'max:120'],
            'brand_logo_url'      => ['nullable', 'string', 'max:500'],
            'brand_primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'primary_domain'      => ['nullable', 'string', 'max:190', 'unique:companies,primary_domain'],
        ], [
            'brand_primary_color.regex' => 'Renk #rrggbb formatında olmalı (örn. #0d9488).',
            'primary_domain.unique'     => 'Bu domain başka bir şirkete tanımlı.',
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
            $domain = strtolower(trim((string) $request->input('primary_domain', '')));
            // Kullanıcı "https://a.firma.com/" yazarsa da doğru kaydet
            $domain = (string) preg_replace('#^https?://#', '', $domain);
            $domain = trim($domain, '/');

            // Başvuru linki adresi (/apply/{slug}) — okunabilir olsun diye ad'dan
            // tire ile üretilir; code alt çizgi kullandığı için ondan ayrı tutulur.
            $slug = Str::slug((string) $request->input('name'), '-') ?: $code;
            $slugBase = $slug;
            $n = 1;
            while (Company::query()->where('slug', $slug)->exists()) {
                $slug = $slugBase . '-' . (++$n);
            }

            // Üst firmayı platform sahibi SEÇER. Form ana şirketi önceden seçili
            // getirir (yaygın durum), ama bir partner firmanın altına da takılabilir —
            // örn. FF firmasıyla anlaşıp onun altına kendi bayi ağacını kurmak.
            // Boş gönderildiyse bilinçli tercihtir: bağımsız tenant.
            $parentId = (int) $request->input('parent_company_id', 0);

            $company = Company::query()->create([
                'name'              => $request->input('name'),
                'code'              => $code,
                'slug'              => $slug,
                'parent_company_id' => $parentId > 0 ? $parentId : null,
                'is_active'         => true,
                'enabled_modules'   => $modules,
                'subscription_tier' => $tier,
                'trial_ends_at'     => $trialEnds ?: null,
                // White-label marka — boşsa platformun varsayılanı kullanılır
                'brand_name'          => trim((string) $request->input('brand_name', '')) ?: null,
                'brand_logo_url'      => trim((string) $request->input('brand_logo_url', '')) ?: null,
                'brand_primary_color' => trim((string) $request->input('brand_primary_color', '')) ?: null,
                'primary_domain'      => $domain !== '' ? $domain : null,
                // B2B firmalar reklam görmemeli — yeni şirkette varsayılan KAPALI.
                // B2C tarafı (ana şirket) bu kolonu true ile taşıyor, etkilenmez.
                'public_marketing'    => $request->boolean('public_marketing'),
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
                // Geçici şifre ilk girişte değiştirilmek ZORUNDA. Mekanizma
                // (EnsurePasswordChanged + /password/change-required) zaten vardı
                // ama platform panelinden açılan yönetici bayrağı almıyordu:
                // firmaya e-postayla giden geçici şifre süresiz geçerli kalıyordu.
                'password_must_change' => true,
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
