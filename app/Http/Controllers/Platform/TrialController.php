<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\TrialExtension;
use App\Models\User;
use App\Services\Platform\TrialService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Platform Owner — Trial Yonetimi.
 *
 * Dashboard'da:
 *   - 4 KPI: Aktif trial / Bu ay conversion / 7 gun icinde bitecek / Expired
 *   - 12 ay conversion trend (inline SVG)
 *   - Expiring soon tablosu (3/7/14 gun grupli — kirmizi uyari)
 *   - Tum aktif trial'lar tablosu (action: Uzat / Convert / Nurture)
 *
 * POST endpoint'leri:
 *   - extend(company): manuel uzatma (days + reason)
 *   - convert(company): trial -> basic/gold/premium
 *   - nurtureSend(): manuel olarak bekleyen nurture mail'i tetikle
 */
class TrialController extends Controller
{
    public function __construct(private readonly TrialService $trial)
    {
    }

    // ────────────────────────────────────────────────────────────────────────
    // INDEX — Dashboard
    // ────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $stats          = $this->trial->getConversionStats();
        $activeTrials   = $this->trial->getActiveTrials();
        $expiringSoon   = $this->trial->getExpiringSoon(14);
        $activeCount    = $this->trial->activeTrialCount();
        $expiredCount   = $this->trial->expiredTrialCount();
        $nurtureCount   = count($this->trial->getNurtureCandidates());

        // Expiring soon — 3/7/14 gun grup
        $today      = CarbonImmutable::now()->startOfDay();
        $expiring3  = collect();
        $expiring7  = collect();
        $expiring14 = collect();

        foreach ($expiringSoon as $c) {
            if (!$c->trial_ends_at) {
                continue;
            }
            $end  = CarbonImmutable::parse($c->trial_ends_at)->startOfDay();
            $diff = (int) $today->diffInDays($end, false);
            $diff = $diff < 0 ? 0 : $diff;

            if ($diff <= 3) {
                $expiring3->push($c);
            } elseif ($diff <= 7) {
                $expiring7->push($c);
            } else {
                $expiring14->push($c);
            }
        }

        // Aktif trial'lar icin: her satira days_left + status hesapla
        $rows = $activeTrials->map(function (Company $c) use ($today): array {
            $end      = $c->trial_ends_at ? CarbonImmutable::parse($c->trial_ends_at)->startOfDay() : null;
            $daysLeft = $end ? (int) $today->diffInDays($end, false) : null;

            $status = match (true) {
                $end === null              => 'unlimited',
                $daysLeft < 0              => 'expired',
                $daysLeft <= 3             => 'critical',
                $daysLeft <= 7             => 'warning',
                default                    => 'ok',
            };

            return [
                'company'    => $c,
                'days_left'  => $daysLeft,
                'status'     => $status,
            ];
        });

        // Son uzatmalar (audit)
        $recentExtensions = TrialExtension::query()
            ->with(['company', 'grantedBy'])
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('platform.trial.index', [
            'stats'             => $stats,
            'rows'              => $rows,
            'expiring3'         => $expiring3,
            'expiring7'         => $expiring7,
            'expiring14'        => $expiring14,
            'activeCount'       => $activeCount,
            'expiredCount'      => $expiredCount,
            'nurtureCount'      => $nurtureCount,
            'recentExtensions'  => $recentExtensions,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // EXTEND
    // ────────────────────────────────────────────────────────────────────────

    public function extend(Request $request, int $companyId): RedirectResponse
    {
        $company = Company::query()->findOrFail($companyId);

        $validator = Validator::make($request->all(), [
            'extend_days' => ['required', 'integer', 'min:1', 'max:365'],
            'reason'      => ['nullable', 'string', 'max:500'],
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $days   = (int) $request->input('extend_days');
        $reason = (string) $request->input('reason', '');
        $byUser = (int) ($request->user()?->id ?? 0);

        $ext = $this->trial->extendTrial($company, $days, $reason, $byUser);

        return redirect()
            ->route('platform.trial')
            ->with('success', "{$company->name} trial'i {$days} gun uzatildi (yeni bitis: {$ext->new_trial_ends_at->toDateString()}).");
    }

    // ────────────────────────────────────────────────────────────────────────
    // CONVERT — Trial -> Paid
    // ────────────────────────────────────────────────────────────────────────

    public function convert(Request $request, int $companyId): RedirectResponse
    {
        $company = Company::query()->findOrFail($companyId);

        $validator = Validator::make($request->all(), [
            'tier' => ['required', 'string', 'in:basic,gold,premium'],
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $tier = (string) $request->input('tier');

        try {
            $this->trial->convertToPaid($company, $tier);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Gecersiz tier: ' . $e->getMessage());
        }

        $label = config("subscription_tiers.{$tier}.label", $tier);

        return redirect()
            ->route('platform.trial')
            ->with('success', "{$company->name} {$label} tier'ina yukseltildi.");
    }

    // ────────────────────────────────────────────────────────────────────────
    // NURTURE SEND — Bekleyen nurture mail'leri manuel gonder
    // ────────────────────────────────────────────────────────────────────────

    public function nurtureSend(Request $request): RedirectResponse
    {
        $companyId = (int) $request->input('company_id', 0);
        $stage     = trim((string) $request->input('stage', ''));

        // Tek bir company icin gonder (action butonundan)
        if ($companyId > 0 && $stage !== '') {
            $company = Company::query()->find($companyId);
            if (!$company) {
                return back()->with('error', 'Sirket bulunamadi.');
            }
            $ok = $this->trial->sendNurtureMail($company, $stage);
            return back()->with(
                $ok ? 'success' : 'error',
                $ok
                    ? "{$company->name} icin {$stage} nurture mail gonderildi."
                    : "Mail gonderilemedi (manager email yok veya hata). Log'a bakin."
            );
        }

        // Toplu: tum bekleyen aday'lara gonder
        $candidates = $this->trial->getNurtureCandidates();
        $sent       = 0;
        $failed     = 0;

        foreach ($candidates as $row) {
            if ($this->trial->sendNurtureMail($row['company'], $row['stage'])) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return back()->with(
            $sent > 0 ? 'success' : 'error',
            "Nurture mail toplu: {$sent} gonderildi, {$failed} basarisiz."
        );
    }
}
