<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'code',
        'parent_company_id',
        'is_active',
        'enabled_modules',
        'doc_request_monthly_limit',
        // SaaS subscription (Platform Owner Faz 2)
        'subscription_tier',
        'trial_ends_at',
        'subscription_renews_at',
        'billing_email',
        'mrr_eur',
        // Multi-brand (Faz 2) — marka ve domain; bkz. App\Support\Brand
        'slug',
        'primary_domain',
        'domain_aliases',
        'brand_name',
        'brand_logo_url',
        'brand_primary_color',
        'brand_overrides',
        'public_marketing',
        'is_public_portal',
        'denied_permission_codes',
    ];

    protected $casts = [
        'is_active'                 => 'boolean',
        'silence_checkin_overrides' => 'array',
        'enabled_modules'           => 'array',
        'doc_request_monthly_limit' => 'integer',
        // SaaS subscription
        'trial_ends_at'             => 'date',
        'subscription_renews_at'    => 'date',
        'mrr_eur'                   => 'decimal:2',
        // Multi-brand
        'domain_aliases'            => 'array',
        'brand_overrides'           => 'array',
        'public_marketing'          => 'boolean',
        'is_public_portal'          => 'boolean',
        'denied_permission_codes'   => 'array',
    ];

    protected static function booted(): void
    {
        // Marka değişince çözülmüş paketi ve host eşleşmesini tazele.
        static::saved(function (self $company): void {
            \App\Support\Brand::flushCache((int) $company->id);
            \Illuminate\Support\Facades\Cache::forget("company:{$company->id}:active");
            // slug/code/aktiflik değişti → başvuru linki çözümlemesi tazelensin
            \App\Support\ApplyCompanyResolver::flushCache($company);
            // üst firma değişti → görünürlük kümesi yeniden hesaplanmalı
            self::flushHierarchyCache();
        });

        static::deleted(function (): void {
            self::flushHierarchyCache();
        });
    }

    private const HIERARCHY_CACHE_KEY = 'companies:hierarchy_map';

    public function parentCompany(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_company_id');
    }

    public function childCompanies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_company_id');
    }

    /**
     * Bu şirketin ALTINDAKİ tüm şirketler (torunlar dahil, kendisi hariç).
     *
     * MentorDE partner firmalarının süreçlerini yürüttüğü için onların verisini
     * görmeli. İzolasyon yataydır: firma firmayı göremez, ama üst firma altını görür.
     *
     * @return list<int>
     */
    public static function descendantIds(int $companyId): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $childrenByParent = self::hierarchyMap();

        $out = [];
        $queue = [$companyId];
        // Döngü koruması: veri bozulursa (A→B→A) sonsuz döngüye girmesin.
        $seen = [$companyId => true];

        while ($queue !== []) {
            $current = array_shift($queue);

            foreach ($childrenByParent[$current] ?? [] as $childId) {
                if (isset($seen[$childId])) {
                    continue;
                }

                $seen[$childId] = true;
                $out[] = $childId;
                $queue[] = $childId;
            }
        }

        return $out;
    }

    /**
     * parent_company_id => [child ids]
     *
     * Şirket sayısı çok az; tek sorguyla tamamını alıp bellekte yürümek,
     * her istekte recursive CTE çalıştırmaktan ucuz ve sürücüden bağımsız.
     *
     * @return array<int,list<int>>
     */
    private static function hierarchyMap(): array
    {
        return \Illuminate\Support\Facades\Cache::remember(
            self::HIERARCHY_CACHE_KEY,
            600,
            static function (): array {
                $map = [];

                try {
                    // Schema::hasColumn ile korumak her SOĞUK çağrıda fazladan bir
                    // şema sorgusu demek; bu yol 5 sn'de bir çalışan poll
                    // endpoint'inden geçiyor. Migration henüz koşmadıysa sorgu
                    // zaten hata verir — try/catch bedava.
                    foreach (\Illuminate\Support\Facades\DB::table('companies')
                        ->whereNotNull('parent_company_id')
                        ->get(['id', 'parent_company_id']) as $row) {
                        $map[(int) $row->parent_company_id][] = (int) $row->id;
                    }
                } catch (\Throwable) {
                    return [];
                }

                return $map;
            }
        );
    }

    public static function flushHierarchyCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget(self::HIERARCHY_CACHE_KEY);
        \Illuminate\Support\Facades\Cache::forget(self::CEILING_CACHE_KEY);
    }

    private const CEILING_CACHE_KEY = 'companies:permission_ceilings';

    private const ADVISOR_CACHE_KEY = 'companies:with_advisors';

    /**
     * Süreci fiilen YÜRÜTEN şirket — danışmanın alınacağı yer.
     *
     * İş modeli: partner firma öğrenciyi devreder, operasyonu biz yürütürüz.
     * Partner firmanın kendi danışmanı YOKTUR; öğrenciye MentorDE'nin danışmanı
     * atanır. Bu yüzden danışman aranırken şirket ağacında YUKARI çıkılır:
     * danışmanı olan ilk şirket operasyon şirketidir.
     *
     * Kendi danışmanı olan bir firma (kendi operasyonunu yürüten partner)
     * kendisini döndürür — kimseye bağlanmaz.
     */
    public static function operatingCompanyId(int $companyId): ?int
    {
        if ($companyId <= 0) {
            return null;
        }

        $withAdvisors = self::companiesWithAdvisors();

        foreach (array_merge([$companyId], self::ancestorIds($companyId)) as $candidate) {
            if (isset($withAdvisors[$candidate])) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Aktif ve otomatik atamaya açık danışmanı olan şirketler.
     *
     * @return array<int,true>
     */
    private static function companiesWithAdvisors(): array
    {
        return \Illuminate\Support\Facades\Cache::remember(
            self::ADVISOR_CACHE_KEY,
            300,
            static function (): array {
                try {
                    return \Illuminate\Support\Facades\DB::table('users')
                        ->whereIn('role', [User::ROLE_SENIOR, User::ROLE_MENTOR])
                        ->where('is_active', true)
                        ->whereNotNull('company_id')
                        ->distinct()
                        ->pluck('company_id')
                        ->mapWithKeys(static fn ($id): array => [(int) $id => true])
                        ->all();
                } catch (\Throwable) {
                    return [];
                }
            }
        );
    }

    public static function flushAdvisorCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget(self::ADVISOR_CACHE_KEY);
    }

    /**
     * Bu şirketin ÜSTÜNDEKİ şirketler (kendisi hariç, köke kadar).
     *
     * @return list<int>
     */
    public static function ancestorIds(int $companyId): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $parents = self::ceilingData()['parents'] ?? [];

        $out = [];
        $seen = [$companyId => true];
        $current = $companyId;

        while (isset($parents[$current])) {
            $parent = (int) $parents[$current];

            // Döngü koruması: veri bozulursa (A→B→A) sonsuza sarmasın.
            if ($parent <= 0 || isset($seen[$parent])) {
                break;
            }

            $seen[$parent] = true;
            $out[] = $parent;
            $current = $parent;
        }

        return $out;
    }

    /**
     * Şirkete UYGULANAN yetki kısıtları: kendisininki + tüm üstlerininki.
     *
     * Kısıt ağaçtan aşağı birikir — MentorDE YourGermanUni'ye bir kısıt
     * koyduğunda onun altındaki firmalar da bağlanır.
     *
     * @return list<string>
     */
    public static function effectiveDeniedPermissions(int $companyId): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $denials = self::ceilingData()['denials'] ?? [];

        $codes = $denials[$companyId] ?? [];

        foreach (self::ancestorIds($companyId) as $ancestorId) {
            $codes = array_merge($codes, $denials[$ancestorId] ?? []);
        }

        return array_values(array_unique(array_filter($codes)));
    }

    /**
     * Tek sorguda hem ebeveyn zinciri hem kısıt listeleri.
     *
     * @return array{parents:array<int,int>,denials:array<int,list<string>>}
     */
    private static function ceilingData(): array
    {
        return \Illuminate\Support\Facades\Cache::remember(
            self::CEILING_CACHE_KEY,
            600,
            static function (): array {
                $parents = [];
                $denials = [];

                try {
                    $rows = \Illuminate\Support\Facades\DB::table('companies')
                        ->get(['id', 'parent_company_id', 'denied_permission_codes']);
                } catch (\Throwable) {
                    // Kolon henüz yok (migration öncesi) — kısıt yok say.
                    return ['parents' => [], 'denials' => []];
                }

                foreach ($rows as $row) {
                    $id = (int) $row->id;

                    if (!empty($row->parent_company_id)) {
                        $parents[$id] = (int) $row->parent_company_id;
                    }

                    $codes = $row->denied_permission_codes ?? null;
                    if (is_string($codes)) {
                        $codes = json_decode($codes, true);
                    }
                    if (is_array($codes) && $codes !== []) {
                        $denials[$id] = array_values(array_map('strval', $codes));
                    }
                }

                return ['parents' => $parents, 'denials' => $denials];
            }
        );
    }

    /**
     * SaaS subscription tier sabitleri — config/subscription_tiers.php ile senkron.
     */
    public const TIER_TRIAL   = 'trial';
    public const TIER_BASIC   = 'basic';
    public const TIER_GOLD    = 'gold';
    public const TIER_PREMIUM = 'premium';

    public const TIERS = [
        self::TIER_TRIAL,
        self::TIER_BASIC,
        self::TIER_GOLD,
        self::TIER_PREMIUM,
    ];

    /** Trial siresi dolmus mu (trial_ends_at gecmis)? */
    public function isTrialExpired(?\Carbon\CarbonInterface $now = null): bool
    {
        if ($this->subscription_tier !== self::TIER_TRIAL) return false;
        if (!$this->trial_ends_at) return false;
        return $this->trial_ends_at->lt($now ?? \Carbon\CarbonImmutable::now());
    }

    /** Trial mu (ücretsiz dönemde mi)? */
    public function isTrial(): bool
    {
        return $this->subscription_tier === self::TIER_TRIAL;
    }

    /** Trial bitişine kalan gün (negatifse expired). NULL = trial değil veya date yok. */
    public function trialDaysRemaining(?\Carbon\CarbonInterface $now = null): ?int
    {
        if (!$this->isTrial() || !$this->trial_ends_at) return null;
        $now = $now ?? \Carbon\CarbonImmutable::now()->startOfDay();
        return (int) round($now->diffInDays($this->trial_ends_at, false));
    }

    /**
     * D11: Bu ay icin uretilen doc_request token sayisi (quota gating icin).
     */
    public function docRequestMonthlyUsage(?\Carbon\CarbonInterface $now = null): int
    {
        $now = $now ?? \Carbon\CarbonImmutable::now();
        return (int) \App\Models\DocumentUploadToken::query()
            ->where('company_id', $this->id)
            ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->count();
    }

    /**
     * Bu company doc_request quota'sina takildi mi?
     * NULL limit (sinirsiz) ise her zaman false.
     */
    public function isDocRequestQuotaExhausted(?\Carbon\CarbonInterface $now = null): bool
    {
        $limit = $this->doc_request_monthly_limit;
        if ($limit === null || $limit <= 0) return false;
        return $this->docRequestMonthlyUsage($now) >= $limit;
    }
}

