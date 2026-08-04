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

