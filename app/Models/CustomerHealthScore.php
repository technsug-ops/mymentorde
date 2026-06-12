<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Customer Health Score — Platform Owner gozetimi icin.
 *
 * Her company icin tek satir. Score'lar 0-100 TINYINT, risk_flags JSON array.
 * Hesaplama: CustomerHealthService::computeForCompany().
 */
class CustomerHealthScore extends Model
{
    public const TREND_RISING    = 'rising';
    public const TREND_STABLE    = 'stable';
    public const TREND_DECLINING = 'declining';

    public const FLAG_NO_LOGIN_30D    = 'no_login_30d';
    public const FLAG_PAYMENT_FAILED  = 'payment_failed';
    public const FLAG_TRIAL_ENDING    = 'trial_ending';
    public const FLAG_FEATURE_DECLINE = 'feature_decline';

    public const FLAGS = [
        self::FLAG_NO_LOGIN_30D,
        self::FLAG_PAYMENT_FAILED,
        self::FLAG_TRIAL_ENDING,
        self::FLAG_FEATURE_DECLINE,
    ];

    protected $fillable = [
        'company_id',
        'overall_score',
        'login_score',
        'payment_score',
        'usage_score',
        'support_score',
        'trend',
        'risk_flags',
        'computed_at',
        'last_risk_alert_sent_at',
    ];

    protected $casts = [
        'overall_score'           => 'integer',
        'login_score'             => 'integer',
        'payment_score'           => 'integer',
        'usage_score'             => 'integer',
        'support_score'           => 'integer',
        'risk_flags'              => 'array',
        'computed_at'             => 'datetime',
        'last_risk_alert_sent_at' => 'datetime',
    ];

    // ────────────────────────────────────────────────────────────────────────
    // Relations
    // ────────────────────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Scopes
    // ────────────────────────────────────────────────────────────────────────

    /** Risk altinda: overall_score < 50. */
    public function scopeAtRisk(Builder $query): Builder
    {
        return $query->where('overall_score', '<', 50);
    }

    /** Trend dustugu icin dikkat gereken company'ler. */
    public function scopeDeclining(Builder $query): Builder
    {
        return $query->where('trend', self::TREND_DECLINING);
    }

    /** Kritik durumda (skor < 20). */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('overall_score', '<', 20);
    }

    /** Uyari seviyesi (50-79). */
    public function scopeWarning(Builder $query): Builder
    {
        return $query->whereBetween('overall_score', [50, 79]);
    }

    /** Saglikli (80+). */
    public function scopeHealthy(Builder $query): Builder
    {
        return $query->where('overall_score', '>=', 80);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    /** Skor degerine gore renk kategorisi: green | yellow | red. */
    public function scoreColor(): string
    {
        return self::scoreColorFor($this->overall_score);
    }

    public static function scoreColorFor(int $score): string
    {
        return match (true) {
            $score >= 80 => 'green',
            $score >= 50 => 'yellow',
            $score >= 20 => 'orange',
            default      => 'red',
        };
    }

    /** Platform layout uyumlu badge sinifi. */
    public function scoreBadgeClass(): string
    {
        return match ($this->scoreColor()) {
            'green'  => 'plat-badge plat-badge-active',
            'yellow' => 'plat-badge plat-badge-gold',
            'orange' => 'plat-badge plat-badge-trial',
            'red'    => 'plat-badge plat-badge-inactive',
            default  => 'plat-badge',
        };
    }

    /** Renge gore hex (chart/bar fill). */
    public function scoreHex(): string
    {
        return match ($this->scoreColor()) {
            'green'  => '#4ade80',
            'yellow' => '#fbbf24',
            'orange' => '#fb923c',
            'red'    => '#f87171',
            default  => '#a09bb5',
        };
    }

    /** Kategori etiketi (Healthy/Warning/At Risk/Critical). */
    public function scoreLabel(): string
    {
        return match (true) {
            $this->overall_score >= 80 => 'Sağlıklı',
            $this->overall_score >= 50 => 'Uyarı',
            $this->overall_score >= 20 => 'Riskli',
            default                    => 'Kritik',
        };
    }

    /** Trend Turkce etiketi. */
    public function trendLabel(): string
    {
        return match ($this->trend) {
            self::TREND_RISING    => 'Yükseliyor',
            self::TREND_DECLINING => 'Düşüyor',
            default               => 'Sabit',
        };
    }

    /** Trend ikon adi (x-icon). */
    public function trendIcon(): string
    {
        return match ($this->trend) {
            self::TREND_RISING    => 'trending-up',
            self::TREND_DECLINING => 'arrow-down',
            default               => 'minus',
        };
    }

    /** Risk flag i icin Turkce etiket + remediation onerisi. */
    public static function flagMeta(string $flag): array
    {
        return match ($flag) {
            self::FLAG_NO_LOGIN_30D => [
                'label' => 'Son 30 günde login yok',
                'icon'  => 'user-x',
                'fix'   => 'Müşteriye onboarding hatırlatma maili gönder, manager ile temas kur.',
            ],
            self::FLAG_PAYMENT_FAILED => [
                'label' => 'Ödeme başarısız / vadesi geçmiş',
                'icon'  => 'credit-card',
                'fix'   => 'Faturalama sayfasından overdue faturayı incele, manuel hatırlatma gönder.',
            ],
            self::FLAG_TRIAL_ENDING => [
                'label' => 'Trial 7 gün içinde bitiyor',
                'icon'  => 'hourglass',
                'fix'   => 'Yükseltme teklifi gönder, demo/satış görüşmesi planla.',
            ],
            self::FLAG_FEATURE_DECLINE => [
                'label' => 'Feature kullanımı düştü',
                'icon'  => 'arrow-down',
                'fix'   => 'Hangi modülün düştüğünü incele, eğitim/walkthrough gönder.',
            ],
            default => [
                'label' => $flag,
                'icon'  => 'circle-alert',
                'fix'   => 'Detaylı incele.',
            ],
        };
    }
}
