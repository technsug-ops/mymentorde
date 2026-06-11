<?php

namespace App\Http\Controllers\Senior;

use App\Http\Controllers\Controller;
use App\Models\PayoutSetting;
use App\Models\SeniorEarning;
use App\Models\SeniorPayout;
use App\Services\Booking\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Marketplace Phase 6 — Senior Kazançlar Dashboard.
 *
 * KPI: bu ay kazanılan, ödeme bekleyen, toplam ödenen
 * Earnings tablosu (booking-by-booking)
 * Payout history + invoice indirme
 * On-demand ödeme talebi
 */
class SeniorEarningsController extends Controller
{
    public function index(Request $request): View
    {
        $userId    = (int) auth()->id();
        $companyId = (int) auth()->user()->company_id;
        $settings  = PayoutSetting::forCompany($companyId);

        $thisMonthStart = now()->startOfMonth();
        $thisMonthEnd   = now()->endOfMonth();

        // KPI
        $thisMonthCents = (int) SeniorEarning::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('senior_user_id', $userId)
            ->whereBetween('recorded_at', [$thisMonthStart, $thisMonthEnd])
            ->sum('senior_payout_cents');

        $pendingCents = (int) SeniorEarning::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('senior_user_id', $userId)
            ->whereIn('status', ['available', 'recorded'])
            ->whereNull('payout_id')
            ->sum('senior_payout_cents');

        $totalPaidCents = (int) SeniorPayout::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('senior_user_id', $userId)
            ->where('status', 'paid')
            ->sum('amount_cents');

        // Earnings (booking-by-booking) — son 25
        $earnings = SeniorEarning::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('senior_user_id', $userId)
            ->orderByDesc('recorded_at')
            ->paginate(25, ['*'], 'earnings_page')
            ->withQueryString();

        // Payout history
        $payouts = SeniorPayout::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('senior_user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'payouts_page')
            ->withQueryString();

        $minCents = (int) round(((float) $settings->payout_minimum_eur) * 100);
        $canRequestOnDemand = $settings->allow_on_demand && $pendingCents >= $minCents;

        // Bu ay yapılan on-demand talep sayısı
        $onDemandThisMonth = (int) SeniorPayout::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $userId)
            ->where('method', 'stripe_on_demand')
            ->where('requested_at', '>=', $thisMonthStart)
            ->count();

        return view('senior.earnings.index', [
            'thisMonthCents'      => $thisMonthCents,
            'pendingCents'        => $pendingCents,
            'totalPaidCents'      => $totalPaidCents,
            'earnings'            => $earnings,
            'payouts'             => $payouts,
            'settings'            => $settings,
            'canRequestOnDemand'  => $canRequestOnDemand,
            'minCents'            => $minCents,
            'onDemandThisMonth'   => $onDemandThisMonth,
            'onDemandLimit'       => PayoutService::ON_DEMAND_MONTHLY_LIMIT,
        ]);
    }

    public function requestOnDemand(Request $request, PayoutService $service): RedirectResponse
    {
        $userId = (int) auth()->id();
        $payout = $service->requestOnDemand($userId);

        if (!$payout) {
            return redirect()->route('senior.earnings')
                ->with('error', 'On-demand ödeme talebi reddedildi. Eşik altında bakiye, aylık limit aşıldı veya bu özellik şirkette kapalı.');
        }

        return redirect()->route('senior.earnings')
            ->with('success', sprintf(
                'On-demand ödeme talebi alındı: €%s. Stripe transferi en kısa sürede işlenecek.',
                number_format($payout->amount_cents / 100, 2, ',', '.')
            ));
    }

    public function downloadInvoice(int $payoutId): Response
    {
        $userId = (int) auth()->id();
        $payout = SeniorPayout::query()
            ->withoutGlobalScopes()
            ->where('id', $payoutId)
            ->where('senior_user_id', $userId)
            ->with(['senior', 'earnings'])
            ->firstOrFail();

        $brandName = (string) config('brand.name', 'MentorDE');
        $html = $this->buildInvoiceHtml($payout, $brandName);

        // PDF kullanılabilirse PDF döndür, değilse HTML
        if (class_exists(\Dompdf\Dompdf::class)) {
            try {
                $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans']);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                return response($dompdf->output(), 200, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => sprintf(
                        'attachment; filename="payout-%d-%s.pdf"',
                        $payout->id,
                        $payout->period_end
                    ),
                ]);
            } catch (\Throwable $e) {
                // Fallback HTML
            }
        }

        return response($html, 200, [
            'Content-Type'        => 'text/html; charset=utf-8',
            'Content-Disposition' => sprintf(
                'attachment; filename="payout-%d-%s.html"',
                $payout->id,
                $payout->period_end
            ),
        ]);
    }

    private function buildInvoiceHtml(SeniorPayout $payout, string $brandName): string
    {
        $senior      = $payout->senior;
        $start       = \Illuminate\Support\Carbon::parse($payout->period_start)->format('d.m.Y');
        $end         = \Illuminate\Support\Carbon::parse($payout->period_end)->format('d.m.Y');
        $paidAt      = $payout->paid_at?->format('d.m.Y H:i') ?? '—';
        $amount      = number_format($payout->amount_cents / 100, 2, ',', '.');
        $currency    = $payout->currency === 'EUR' ? '€' : ($payout->currency . ' ');

        $earningsRows = '';
        $netSum = 0; $taxSum = 0; $commSum = 0;
        foreach ($payout->earnings as $e) {
            $netSum  += (int) $e->amount_net_cents;
            $taxSum  += (int) $e->tax_amount_cents;
            $commSum += (int) $e->commission_cents;
            $earningsRows .= sprintf(
                '<tr><td>%s</td><td>#%d</td><td style="text-align:right;">€%s</td><td style="text-align:right;">%%%s</td><td style="text-align:right;">€%s</td></tr>',
                $e->recorded_at?->format('d.m.Y') ?? '—',
                $e->id,
                number_format($e->amount_net_cents / 100, 2, ',', '.'),
                number_format((float) $e->commission_pct_applied, 2),
                number_format($e->senior_payout_cents / 100, 2, ',', '.')
            );
        }

        $netSumF  = number_format($netSum / 100, 2, ',', '.');
        $taxSumF  = number_format($taxSum / 100, 2, ',', '.');
        $commSumF = number_format($commSum / 100, 2, ',', '.');

        return <<<HTML
<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><title>Payout Makbuzu #{$payout->id}</title>
<style>
body { font-family: 'DejaVu Sans', sans-serif; font-size:12px; color:#0f172a; }
.h { display:flex; justify-content:space-between; border-bottom:2px solid #1e40af; padding-bottom:14px; margin-bottom:18px; }
.h h1 { margin:0; font-size:22px; color:#1e40af; }
.h .meta { text-align:right; font-size:11px; color:#64748b; }
.section { margin-bottom:18px; }
.section h3 { font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:#64748b; border-bottom:1px solid #e2e8f0; padding-bottom:6px; margin-bottom:8px; }
table { width:100%; border-collapse:collapse; font-size:11px; }
th { background:#f1f5f9; padding:7px 10px; text-align:left; border-bottom:1px solid #e2e8f0; }
td { padding:7px 10px; border-bottom:1px solid #e2e8f0; }
.total-card { background:#f0fdf4; border:1px solid #16a34a; border-radius:8px; padding:14px 18px; margin-top:18px; }
.total-row { display:flex; justify-content:space-between; padding:4px 0; font-size:12px; }
.total-row.big { font-size:18px; font-weight:bold; color:#15803d; border-top:2px solid #16a34a; padding-top:10px; margin-top:6px; }
.foot { font-size:10px; color:#94a3b8; text-align:center; margin-top:30px; }
</style>
</head><body>

<div class="h">
    <div>
        <h1>{$brandName}</h1>
        <div style="font-size:12px;color:#64748b;">Senior Payout Makbuzu</div>
    </div>
    <div class="meta">
        <strong>Payout ID:</strong> #{$payout->id}<br>
        <strong>Periyot:</strong> {$start} — {$end}<br>
        <strong>Ödeme Tarihi:</strong> {$paidAt}
    </div>
</div>

<div class="section">
    <h3>Senior Bilgileri</h3>
    <div><strong>{$senior?->name}</strong></div>
    <div style="color:#64748b;">{$senior?->email}</div>
</div>

<div class="section">
    <h3>Kazanç Detayı</h3>
    <table>
        <thead>
            <tr><th>Tarih</th><th>Kayıt</th><th style="text-align:right;">Net</th><th style="text-align:right;">Komisyon</th><th style="text-align:right;">Senior Payı</th></tr>
        </thead>
        <tbody>
            {$earningsRows}
        </tbody>
    </table>
</div>

<div class="total-card">
    <div class="total-row"><span>Net toplam</span><span>€{$netSumF}</span></div>
    <div class="total-row"><span>Vergi toplam</span><span>€{$taxSumF}</span></div>
    <div class="total-row"><span>Platform komisyonu</span><span>€{$commSumF}</span></div>
    <div class="total-row big"><span>Senior Net Ödeme</span><span>{$currency}{$amount}</span></div>
</div>

<div class="foot">
    Bu makbuz elektronik olarak {$brandName} sistemi tarafından üretilmiştir.<br>
    Stripe Transfer ID: {$payout->stripe_transfer_id}
</div>

</body></html>
HTML;
    }
}
