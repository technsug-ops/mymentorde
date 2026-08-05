<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GuestApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * KOTA VE KAPASİTE — platform sahibinin SaaS görünümü.
 *
 * ⚠ İKİ ŞEY BİLEREK YOK:
 *
 * 1. KİŞİSEL VERİ. DGmarkt yazılım servisi sağlar; müşterilerinin öğrencileri
 *    için veri sorumlusu değildir. Ad, e-posta, telefon burada listelenmez.
 *
 * 2. SATIŞ HUNİSİ. "Kaç aday nitelikli, kaçı teklif aşamasında" müşterinin
 *    kendi operasyonudur; servis sağlayıcıyı ilgilendirmez.
 *
 * Burada olan tek şey KAPASİTE: firma paketinin sınırına ne kadar yaklaştı,
 * üst pakete geçmesi gerekiyor mu. SaaS'ın gerçekten bakması gereken soru bu.
 */
class PlatformPortfolioController extends Controller
{
    /** Sınıra yaklaşma eşiği — bu oranın üstü "üst paket adayı". */
    private const WARN_THRESHOLD = 80;

    public function leads(Request $request): View
    {
        return view('platform.portfolio.leads', $this->quotaData());
    }

    public function students(Request $request): View
    {
        return view('platform.portfolio.students', $this->quotaData());
    }

    /** @return array<string,mixed> */
    private function quotaData(): array
    {
        $rows = $this->companyQuotas();

        return [
            'companies'   => $rows,
            'atLimit'     => $rows->where('over', true)->count(),
            'nearLimit'   => $rows->where('near', true)->where('over', false)->count(),
            'totalLeads'  => $rows->sum('leads'),
            'totalStuds'  => $rows->sum('students'),
            'threshold'   => self::WARN_THRESHOLD,
        ];
    }

    /**
     * Şirket başına kota kullanımı.
     *
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    private function companyQuotas()
    {
        $leadTotals = $this->leadTotalsByCompany();
        $studentTotals = $this->studentTotalsByCompany();
        $tierLabels = $this->tierLabels();

        return Company::query()
            ->orderBy('name')
            ->get(['id', 'name', 'brand_name', 'code', 'is_active', 'subscription_tier', 'parent_company_id'])
            ->map(function (Company $c) use ($leadTotals, $studentTotals, $tierLabels): array {
                $tier = (string) ($c->subscription_tier ?: 'trial');
                $limits = config("subscription_tiers.{$tier}.limits", []);

                $leads = (int) ($leadTotals[$c->id] ?? 0);
                $students = (int) ($studentTotals[$c->id] ?? 0);

                $leadMax = $limits['leads_max'] ?? null;
                $studentMax = $limits['students_max'] ?? null;

                $leadPct = $this->usagePct($leads, $leadMax);
                $studentPct = $this->usagePct($students, $studentMax);

                // En sıkışık kota firmayı temsil eder — biri dolduysa yükseltme gerekir.
                $worst = max($leadPct ?? 0, $studentPct ?? 0);

                return [
                    'id'          => (int) $c->id,
                    'name'        => (string) ($c->brand_name ?: $c->name),
                    'code'        => (string) $c->code,
                    'active'      => (bool) $c->is_active,
                    'parent'      => $c->parent_company_id ? (int) $c->parent_company_id : null,
                    'tier'        => $tier,
                    'tierLabel'   => (string) ($tierLabels[$tier] ?? $tier),
                    'leads'       => $leads,
                    'leadMax'     => $leadMax,
                    'leadPct'     => $leadPct,
                    'students'    => $students,
                    'studentMax'  => $studentMax,
                    'studentPct'  => $studentPct,
                    'worstPct'    => $worst,
                    'near'        => $worst >= self::WARN_THRESHOLD,
                    'over'        => $worst >= 100,
                    'unlimited'   => $leadMax === null && $studentMax === null,
                ];
            });
    }

    /** null limit = sınırsız → oran yok. */
    private function usagePct(int $used, ?int $max): ?int
    {
        if ($max === null || $max <= 0) {
            return null;
        }

        return (int) round(($used / $max) * 100);
    }

    /** @return array<int,int> */
    private function leadTotalsByCompany(): array
    {
        return Cache::remember('platform:portfolio:lead_totals', 120, fn (): array => GuestApplication::withoutGlobalScope('company')
            ->whereNull('deleted_at')
            ->where(function ($q): void {
                $q->whereNull('converted_to_student')->orWhere('converted_to_student', false);
            })
            ->selectRaw('company_id, count(*) as total')
            ->groupBy('company_id')
            ->pluck('total', 'company_id')
            ->map(fn ($v): int => (int) $v)
            ->all());
    }

    /** @return array<int,int> */
    private function studentTotalsByCompany(): array
    {
        return Cache::remember('platform:portfolio:student_totals', 120, fn (): array => User::withoutGlobalScope('company')
            ->whereNull('deleted_at')
            ->where('role', User::ROLE_STUDENT)
            ->selectRaw('company_id, count(*) as total')
            ->groupBy('company_id')
            ->pluck('total', 'company_id')
            ->map(fn ($v): int => (int) $v)
            ->all());
    }

    /** @return array<string,string> */
    private function tierLabels(): array
    {
        $out = [];

        foreach ((array) config('subscription_tiers', []) as $key => $cfg) {
            $out[(string) $key] = (string) ($cfg['label'] ?? $key);
        }

        return $out;
    }
}
