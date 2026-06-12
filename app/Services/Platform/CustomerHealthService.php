<?php

namespace App\Services\Platform;

use App\Models\Company;
use App\Models\CustomerHealthScore;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Customer Health Service — Platform Owner gozetimi.
 *
 * Her company icin 4 alt-skor (login/payment/usage/support) + overall + trend +
 * risk_flags hesaplar. customer_health_scores tablosuna upsert eder.
 *
 * Bagimsiz tasarim — eksik tablolar (ai_labs_settings yoksa, vs) try/catch ile
 * sessizce 0 doner. Boylece bir modul kapali bile olsa health compute kirilmaz.
 */
class CustomerHealthService
{
    // Agirliklar — overall = login*0.30 + payment*0.30 + usage*0.30 + support*0.10
    public const WEIGHT_LOGIN   = 0.30;
    public const WEIGHT_PAYMENT = 0.30;
    public const WEIGHT_USAGE   = 0.30;
    public const WEIGHT_SUPPORT = 0.10;

    // Trend esik degerleri (puan farki)
    public const TREND_DELTA = 5;

    // ────────────────────────────────────────────────────────────────────────
    // Public API
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Bir company icin sub-score'lari hesapla ve kaydet.
     *
     * @return array{overall:int,login:int,payment:int,usage:int,support:int,trend:string,risk_flags:array<int,string>}
     */
    public function computeForCompany(int $companyId): array
    {
        $company = Company::query()->find($companyId);
        if (!$company) {
            throw new \InvalidArgumentException("Company {$companyId} bulunamadi");
        }

        $now = CarbonImmutable::now();

        // Sub-score'lar
        $loginScore   = $this->computeLoginScore($company, $now);
        $paymentScore = $this->computePaymentScore($company, $now);
        $usageScore   = $this->computeUsageScore($company, $now);
        $supportScore = $this->computeSupportScore($company, $now);

        $overall = (int) round(
            $loginScore   * self::WEIGHT_LOGIN
            + $paymentScore * self::WEIGHT_PAYMENT
            + $usageScore   * self::WEIGHT_USAGE
            + $supportScore * self::WEIGHT_SUPPORT
        );
        $overall = max(0, min(100, $overall));

        // Risk flags
        $riskFlags = $this->computeRiskFlags($company, $now, [
            'login'   => $loginScore,
            'usage'   => $usageScore,
            'payment' => $paymentScore,
        ]);

        // Trend (onceki skora gore)
        $existing = CustomerHealthScore::query()->where('company_id', $companyId)->first();
        $trend    = $this->computeTrend($existing?->overall_score, $overall);

        // Upsert
        $payload = [
            'overall_score' => $overall,
            'login_score'   => $loginScore,
            'payment_score' => $paymentScore,
            'usage_score'   => $usageScore,
            'support_score' => $supportScore,
            'trend'         => $trend,
            'risk_flags'    => $riskFlags,
            'computed_at'   => $now,
        ];

        if ($existing) {
            $existing->fill($payload)->save();
        } else {
            CustomerHealthScore::create(array_merge(['company_id' => $companyId], $payload));
        }

        return array_merge($payload, ['overall' => $overall]);
    }

    /**
     * Tum aktif company'leri recompute et.
     *
     * @return int Basariyla hesaplanan company sayisi.
     */
    public function recomputeAll(): int
    {
        $ok = 0;
        Company::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->chunk(50, function ($companies) use (&$ok): void {
                foreach ($companies as $company) {
                    try {
                        $this->computeForCompany((int) $company->id);
                        $ok++;
                    } catch (Throwable $e) {
                        Log::warning('[CustomerHealth] compute failed', [
                            'company_id' => $company->id,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $ok;
    }

    public function getHealthForCompany(int $companyId): ?CustomerHealthScore
    {
        return CustomerHealthScore::query()->where('company_id', $companyId)->first();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Sub-score computation
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Login score: son 30g'de aktivite gosteren user / toplam aktif user.
     * users.last_activity_at proxy olarak login takibinde kullanilir.
     */
    protected function computeLoginScore(Company $company, CarbonImmutable $now): int
    {
        try {
            $totalActive = (int) DB::table('users')
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->count();

            if ($totalActive === 0) {
                return 0;
            }

            $col = Schema::hasColumn('users', 'last_activity_at') ? 'last_activity_at' : 'updated_at';

            $recentlyActive = (int) DB::table('users')
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->where($col, '>=', $now->subDays(30))
                ->count();

            $pct = (int) round(($recentlyActive / $totalActive) * 100);
            return max(0, min(100, $pct));
        } catch (Throwable $e) {
            return 50; // bilinmiyorsa orta
        }
    }

    /**
     * Payment score: 100 baz, her overdue/failed fatura icin ceza.
     * - overdue:   -25/fatura
     * - bu ay draft + son ay paid yok: -20 (gelir akisi kesilmis)
     */
    protected function computePaymentScore(Company $company, CarbonImmutable $now): int
    {
        try {
            if (!Schema::hasTable('platform_invoices')) {
                return 100; // platform_invoices yoksa kontrol yapilamaz - varsayilan saglikli
            }

            $score = 100;

            $overdueCount = (int) DB::table('platform_invoices')
                ->where('company_id', $company->id)
                ->where('status', 'overdue')
                ->count();
            $score -= $overdueCount * 25;

            // Son 60g'de hic 'paid' fatura yok ama 'sent' var → 20 puan ceza
            $hasRecentPaid = DB::table('platform_invoices')
                ->where('company_id', $company->id)
                ->where('status', 'paid')
                ->where('paid_at', '>=', $now->subDays(60))
                ->exists();
            $hasRecentSent = DB::table('platform_invoices')
                ->where('company_id', $company->id)
                ->where('status', 'sent')
                ->where('sent_at', '>=', $now->subDays(60))
                ->exists();
            if (!$hasRecentPaid && $hasRecentSent) {
                $score -= 20;
            }

            return max(0, min(100, $score));
        } catch (Throwable $e) {
            return 70;
        }
    }

    /**
     * Usage score: son 30g'de feature kullanim sinyali — basit baseline scaling.
     *
     * Sinyaller (her biri 0-30 puan):
     *   - public_bookings count
     *   - ai_conversations (senior + staff) count
     *   - doc_request veya guest_tickets (gosterge eksikse 0)
     *
     * baseline kucuk kullancakta enlem birakir — toplam 90 max + 10 baz = 100.
     */
    protected function computeUsageScore(Company $company, CarbonImmutable $now): int
    {
        $score = 10; // baz

        // 1) Booking aktivitesi
        $score += $this->capScore(
            $this->safeCount('public_bookings', [
                ['company_id', '=', $company->id],
                ['created_at', '>=', $now->subDays(30)],
            ]),
            5, // 5 booking -> tam puan
            30
        );

        // 2) AI konusmalari (senior + staff)
        $aiCount = 0;
        $aiCount += $this->safeCount('senior_ai_conversations', [
            ['company_id', '=', $company->id],
            ['created_at', '>=', $now->subDays(30)],
        ]);
        $aiCount += $this->safeCount('staff_ai_conversations', [
            ['company_id', '=', $company->id],
            ['created_at', '>=', $now->subDays(30)],
        ]);
        $score += $this->capScore($aiCount, 20, 30);

        // 3) Doc request / guest ticket aktivitesi (en azindan baglilik)
        $docCount = $this->safeCount('document_upload_tokens', [
            ['company_id', '=', $company->id],
            ['created_at', '>=', $now->subDays(30)],
        ]);
        $score += $this->capScore($docCount, 3, 30);

        return max(0, min(100, $score));
    }

    /**
     * Support score: acik ticket sayisina ters orantili.
     * - 0 acik ticket = 100
     * - 1-3 acik     = 80
     * - 4-7 acik     = 60
     * - 8-15 acik    = 40
     * - 16+          = 20
     */
    protected function computeSupportScore(Company $company, CarbonImmutable $now): int
    {
        try {
            if (!Schema::hasTable('guest_tickets')) {
                return 100;
            }

            $openCount = (int) DB::table('guest_tickets')
                ->where('company_id', $company->id)
                ->whereIn('status', ['open', 'in_progress', 'waiting_customer', 'pending'])
                ->whereNull('deleted_at')
                ->count();

            return match (true) {
                $openCount === 0  => 100,
                $openCount <= 3   => 80,
                $openCount <= 7   => 60,
                $openCount <= 15  => 40,
                default           => 20,
            };
        } catch (Throwable $e) {
            return 80;
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Risk flags + trend
    // ────────────────────────────────────────────────────────────────────────

    /**
     * 4 risk flag'i kontrol et: no_login_30d / payment_failed / trial_ending / feature_decline.
     *
     * @param array{login:int,usage:int,payment:int} $scores
     * @return array<int,string>
     */
    protected function computeRiskFlags(Company $company, CarbonImmutable $now, array $scores): array
    {
        $flags = [];

        // 1) no_login_30d — login skoru 20'nin altinda
        if ($scores['login'] < 20) {
            $flags[] = CustomerHealthScore::FLAG_NO_LOGIN_30D;
        }

        // 2) payment_failed — payment skoru 50'nin altinda VEYA overdue invoice mevcut
        try {
            if ($scores['payment'] < 50 || (Schema::hasTable('platform_invoices') && DB::table('platform_invoices')
                ->where('company_id', $company->id)
                ->where('status', 'overdue')
                ->exists())) {
                $flags[] = CustomerHealthScore::FLAG_PAYMENT_FAILED;
            }
        } catch (Throwable $e) {
            // ignore
        }

        // 3) trial_ending — trial tier'inda + 7 gun veya daha az kalmis
        if ($company->subscription_tier === Company::TIER_TRIAL && $company->trial_ends_at) {
            $daysLeft = $now->diffInDays($company->trial_ends_at, false);
            if ($daysLeft >= 0 && $daysLeft <= 7) {
                $flags[] = CustomerHealthScore::FLAG_TRIAL_ENDING;
            }
        }

        // 4) feature_decline — usage skoru 30'un altinda
        if ($scores['usage'] < 30) {
            $flags[] = CustomerHealthScore::FLAG_FEATURE_DECLINE;
        }

        return array_values(array_unique($flags));
    }

    protected function computeTrend(?int $previous, int $current): string
    {
        if ($previous === null) {
            return CustomerHealthScore::TREND_STABLE;
        }
        $delta = $current - $previous;
        if ($delta >= self::TREND_DELTA) {
            return CustomerHealthScore::TREND_RISING;
        }
        if ($delta <= -self::TREND_DELTA) {
            return CustomerHealthScore::TREND_DECLINING;
        }
        return CustomerHealthScore::TREND_STABLE;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Tablo yoksa veya hata olursa 0 doner — defensive count.
     *
     * @param array<int,array{0:string,1:string,2:mixed}> $wheres
     */
    protected function safeCount(string $table, array $wheres): int
    {
        try {
            if (!Schema::hasTable($table)) {
                return 0;
            }
            $q = DB::table($table);
            foreach ($wheres as [$col, $op, $val]) {
                if (!Schema::hasColumn($table, $col)) {
                    return 0;
                }
                $q->where($col, $op, $val);
            }
            return (int) $q->count();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Sayim degerini orantili olarak max'a olcekler.
     * Ornek: capScore(3, 5, 30) → 3/5 * 30 = 18 puan.
     */
    protected function capScore(int $value, int $target, int $max): int
    {
        if ($target <= 0 || $max <= 0) {
            return 0;
        }
        $ratio = min(1.0, $value / $target);
        return (int) round($ratio * $max);
    }
}
