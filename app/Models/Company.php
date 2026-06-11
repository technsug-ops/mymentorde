<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active',
        'enabled_modules',
        'doc_request_monthly_limit',
        // SaaS subscription (Platform Owner Faz 2)
        'subscription_tier',
        'trial_ends_at',
        'subscription_renews_at',
        'billing_email',
        'mrr_eur',
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
    ];

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

