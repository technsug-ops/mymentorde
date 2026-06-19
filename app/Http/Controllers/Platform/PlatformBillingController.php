<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PlatformInvoice;
use App\Models\PlatformPaymentMethod;
use App\Models\PromoCode;
use App\Services\Platform\BillingService;
use App\Services\Platform\PromoCodeService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Platform Owner — Faturalama.
 *
 * Mentorde'nin musteri company'lere kestigi faturalari listeler, detay/PDF
 * gosterir, manuel uretim/gonderim/odenmis isaretleme tetikler.
 *
 * Cross-company sorgu: Company global scope yok ama tum sorgular
 * PlatformInvoice uzerinden — herhangi bir company isolation kuralina takilmaz.
 */
class PlatformBillingController extends Controller
{
    public function __construct(
        private readonly BillingService $billing,
        private readonly PromoCodeService $promoService,
    ) {
    }

    // ────────────────────────────────────────────────────────────────────────
    // INDEX — Fatura listesi + KPI + filtreler
    // ────────────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $statusFilter  = trim((string) $request->query('status', ''));
        $companyFilter = (int) $request->query('company_id', 0);
        $fromDate      = trim((string) $request->query('from', ''));
        $toDate        = trim((string) $request->query('to', ''));

        $query = PlatformInvoice::query()->with('company');

        if ($statusFilter !== '' && in_array($statusFilter, PlatformInvoice::STATUSES, true)) {
            $query->where('status', $statusFilter);
        }
        if ($companyFilter > 0) {
            $query->where('company_id', $companyFilter);
        }
        if ($fromDate !== '') {
            $query->whereDate('period_start', '>=', $fromDate);
        }
        if ($toDate !== '') {
            $query->whereDate('period_end', '<=', $toDate);
        }

        $invoices = $query->orderByDesc('period_start')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // ── KPI'lar (bu ay icin) ───────────────────────────────────────────
        $now           = CarbonImmutable::now();
        $monthStart    = $now->startOfMonth()->toDateString();
        $monthEnd      = $now->endOfMonth()->toDateString();

        $monthQuery = PlatformInvoice::query()
            ->whereDate('period_start', '>=', $monthStart)
            ->whereDate('period_start', '<=', $monthEnd);

        $monthTotalCount  = (clone $monthQuery)->count();
        $monthTotalAmount = (float) (clone $monthQuery)->sum('total_eur');

        $paidQuery        = (clone $monthQuery)->where('status', PlatformInvoice::STATUS_PAID);
        $monthPaidCount   = (clone $paidQuery)->count();
        $monthPaidAmount  = (float) (clone $paidQuery)->sum('total_eur');

        $pendingQuery        = (clone $monthQuery)->whereIn('status', [PlatformInvoice::STATUS_SENT, PlatformInvoice::STATUS_DRAFT]);
        $monthPendingCount   = (clone $pendingQuery)->count();
        $monthPendingAmount  = (float) (clone $pendingQuery)->sum('total_eur');

        $overdueQuery        = PlatformInvoice::query()->where('status', PlatformInvoice::STATUS_OVERDUE);
        $overdueCount        = (clone $overdueQuery)->count();
        $overdueAmount       = (float) (clone $overdueQuery)->sum('total_eur');

        $draftCount = (clone $monthQuery)->where('status', PlatformInvoice::STATUS_DRAFT)->count();

        // Company dropdown listesi (filtre icin)
        $companies = Company::query()
            ->orderBy('name')
            ->select('id', 'name')
            ->get();

        return view('platform.billing.index', [
            'invoices'           => $invoices,
            'filters'            => [
                'status'     => $statusFilter,
                'company_id' => $companyFilter,
                'from'       => $fromDate,
                'to'         => $toDate,
            ],
            'companies'          => $companies,
            'statuses'           => PlatformInvoice::STATUSES,

            // KPI
            'monthTotalCount'    => $monthTotalCount,
            'monthTotalAmount'   => $monthTotalAmount,
            'monthPaidCount'     => $monthPaidCount,
            'monthPaidAmount'    => $monthPaidAmount,
            'monthPendingCount'  => $monthPendingCount,
            'monthPendingAmount' => $monthPendingAmount,
            'overdueCount'       => $overdueCount,
            'overdueAmount'      => $overdueAmount,
            'draftCount'         => $draftCount,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // SHOW — Fatura detay
    // ────────────────────────────────────────────────────────────────────────

    public function show(PlatformInvoice $invoice): View
    {
        $invoice->load('company');
        $paymentMethods = PlatformPaymentMethod::query()
            ->where('company_id', $invoice->company_id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return view('platform.billing.show', [
            'invoice'        => $invoice,
            'company'        => $invoice->company,
            'paymentMethods' => $paymentMethods,
            'tierLabel'      => config("subscription_tiers.{$invoice->tier}.label", $invoice->tier),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // DOWNLOAD PDF
    // ────────────────────────────────────────────────────────────────────────

    public function downloadPdf(PlatformInvoice $invoice): Response|StreamedResponse
    {
        $invoice->load('company');

        // Kaydedilmis PDF varsa onu dondur
        if ($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)) {
            return Storage::disk('local')->download(
                $invoice->pdf_path,
                $invoice->invoice_number . '.pdf'
            );
        }

        // Yoksa anlik uret
        $html = view('platform.billing.invoice-pdf', [
            'invoice' => $invoice,
            'company' => $invoice->company,
        ])->render();

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf([
                'isRemoteEnabled' => false,
                'defaultFont'     => 'DejaVu Sans',
            ]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $invoice->invoice_number . '.pdf"',
            ]);
        }

        // Dompdf yoksa HTML fallback
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // GENERATE — Manuel fatura uret
    // ────────────────────────────────────────────────────────────────────────

    public function generate(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'period'     => ['nullable', 'date_format:Y-m'],
            'promo_code' => ['nullable', 'string', 'max:50'],
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $company = Company::query()->findOrFail((int) $request->input('company_id'));

        $period = $request->input('period')
            ? CarbonImmutable::createFromFormat('Y-m', $request->input('period'))->startOfMonth()
            : CarbonImmutable::now()->startOfMonth();

        if ($this->billing->invoiceExistsFor($company, $period)) {
            return back()->with('error', "{$company->name} için {$period->format('m/Y')} dönemine zaten fatura kesilmiş.");
        }

        $invoice = $this->billing->generateInvoiceFor($company, $period);

        // ── Promo kod (opsiyonel) — varsa fatura tutarini guncelle, redemption olustur
        $promoCodeRaw = trim((string) $request->input('promo_code', ''));
        $promoMessage = '';
        if ($promoCodeRaw !== '') {
            $promo = PromoCode::query()
                ->where('code', strtoupper($promoCodeRaw))
                ->first();

            if (!$promo) {
                $promoMessage = " — Promo kod bulunamadı: {$promoCodeRaw}";
            } elseif (!$promo->canBeUsedBy($company)) {
                $promoMessage = " — Promo kod uygulanamadı ({$promo->code}): geçersiz/süresi dolmuş/tier uyumsuz/zaten kullanılmış.";
            } else {
                $amount = (float) $invoice->amount_eur;
                $discount = $this->promoService->calculateDiscount($promo, $amount);
                if ($discount > 0) {
                    $newAmount = round($amount - $discount, 2);
                    $taxRate   = (float) $invoice->tax_rate_pct;
                    $newTax    = round($newAmount * ($taxRate / 100), 2);
                    $newTotal  = round($newAmount + $newTax, 2);

                    $invoice->amount_eur     = $newAmount;
                    $invoice->tax_amount_eur = $newTax;
                    $invoice->total_eur      = $newTotal;
                    $existingNotes = trim((string) $invoice->notes);
                    $promoNote = "Promo kod: {$promo->code} (€" . number_format($discount, 2, ',', '.') . ' indirim)';
                    $invoice->notes = $existingNotes === '' ? $promoNote : $existingNotes . "\n" . $promoNote;
                    $invoice->save();

                    $promo->apply($company, $discount, [$invoice->id]);
                    $promoMessage = " — Promo {$promo->code} uygulandı: €" . number_format($discount, 2, ',', '.') . ' indirim';
                }
            }
        }

        \App\Models\PlatformAuditLog::record(
            'platform.billing.invoice_generated',
            [
                'target_type'    => 'invoice',
                'target_id'      => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'company_id'     => $company->id,
                'company'        => $company->name,
                'period'         => $period->format('Y-m'),
                'total_eur'      => (float) $invoice->total_eur,
                'promo_applied'  => $promoMessage !== '' ? trim($promoMessage, ' —') : null,
            ],
            \App\Models\PlatformAuditLog::SEVERITY_INFO
        );

        return redirect()
            ->route('platform.billing.show', $invoice)
            ->with('success', "Fatura oluşturuldu: {$invoice->invoice_number} ({$company->name}){$promoMessage}");
    }

    // ────────────────────────────────────────────────────────────────────────
    // SEND — Manuel mail gonderimi
    // ────────────────────────────────────────────────────────────────────────

    public function send(PlatformInvoice $invoice): RedirectResponse
    {
        $invoice->load('company');
        $ok = $this->billing->sendInvoice($invoice);

        if (!$ok) {
            \App\Models\PlatformAuditLog::record(
                'platform.billing.invoice_send_failed',
                [
                    'target_type'    => 'invoice',
                    'target_id'      => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'company_id'     => $invoice->company_id,
                    'reason'         => 'billing_email_or_smtp',
                ],
                \App\Models\PlatformAuditLog::SEVERITY_WARNING
            );
            return back()->with('error', 'Fatura gönderilemedi. Billing email eksik veya mail hatası — log\'a bakın.');
        }

        \App\Models\PlatformAuditLog::record(
            'platform.billing.invoice_sent',
            [
                'target_type'    => 'invoice',
                'target_id'      => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'company_id'     => $invoice->company_id,
                'company'        => $invoice->company?->name,
                'recipient'      => $invoice->company?->billing_email,
                'total_eur'      => (float) $invoice->total_eur,
            ],
            \App\Models\PlatformAuditLog::SEVERITY_INFO
        );

        return back()->with('success', "Fatura gönderildi: {$invoice->invoice_number} → {$invoice->company?->billing_email}");
    }

    // ────────────────────────────────────────────────────────────────────────
    // MARK PAID — Manuel odenmis isaretle
    // ────────────────────────────────────────────────────────────────────────

    public function markPaid(Request $request, PlatformInvoice $invoice): RedirectResponse
    {
        $stripeId = trim((string) $request->input('stripe_invoice_id', ''));
        $this->billing->markPaid($invoice, $stripeId !== '' ? $stripeId : null);

        \App\Models\PlatformAuditLog::record(
            'platform.billing.invoice_paid',
            [
                'target_type'        => 'invoice',
                'target_id'          => $invoice->id,
                'invoice_number'     => $invoice->invoice_number,
                'company_id'         => $invoice->company_id,
                'total_eur'          => (float) $invoice->total_eur,
                'stripe_invoice_id'  => $stripeId !== '' ? $stripeId : null,
            ],
            \App\Models\PlatformAuditLog::SEVERITY_WARNING
        );

        return back()->with('success', "Fatura ödendi olarak işaretlendi: {$invoice->invoice_number}");
    }

    // ────────────────────────────────────────────────────────────────────────
    // CANCEL — Yanlis kesilen faturayi iptal et (void)
    // ────────────────────────────────────────────────────────────────────────
    //
    // Gonderilmis/gecikmis fatura musteriye gitmis olabilir; silmek yerine
    // 'cancelled' statusune ceker (audit izi korunur). Odenmis fatura finansal
    // kayittir, iptal edilemez. Taslak icin sil (destroy) kullanilmali.

    public function cancel(PlatformInvoice $invoice): RedirectResponse
    {
        if ($invoice->status === PlatformInvoice::STATUS_PAID) {
            return back()->with('error', "Ödenmiş fatura iptal edilemez: {$invoice->invoice_number} (finansal kayıt).");
        }
        if ($invoice->status === PlatformInvoice::STATUS_CANCELLED) {
            return back()->with('error', "Fatura zaten iptal edilmiş: {$invoice->invoice_number}.");
        }

        $oldStatus = $invoice->status;
        $this->reversePromoRedemptions($invoice);
        $invoice->status = PlatformInvoice::STATUS_CANCELLED;
        $invoice->save();

        \App\Models\PlatformAuditLog::record(
            'platform.billing.invoice_cancelled',
            [
                'target_type'    => 'invoice',
                'target_id'      => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'company_id'     => $invoice->company_id,
                'old_status'     => $oldStatus,
                'total_eur'      => (float) $invoice->total_eur,
            ],
            \App\Models\PlatformAuditLog::SEVERITY_WARNING
        );

        return back()->with('success', "Fatura iptal edildi: {$invoice->invoice_number}");
    }

    // ────────────────────────────────────────────────────────────────────────
    // DESTROY — Taslak/iptal faturayi tamamen sil
    // ────────────────────────────────────────────────────────────────────────
    //
    // Sadece taslak (draft) ve iptal (cancelled) faturalar silinebilir.
    // Gonderilmis/odenmis fatura silinmez — once cancel edilmeli.
    // Promo uygulanmissa redemption geri alinir, PDF dosyasi silinir.

    public function destroy(PlatformInvoice $invoice): RedirectResponse
    {
        if (! in_array($invoice->status, [PlatformInvoice::STATUS_DRAFT, PlatformInvoice::STATUS_CANCELLED], true)) {
            return back()->with('error', "Sadece taslak veya iptal edilmiş fatura silinebilir: {$invoice->invoice_number} ({$invoice->statusLabel()}). Önce iptal edin.");
        }

        $number    = $invoice->invoice_number;
        $companyId  = $invoice->company_id;
        $totalEur   = (float) $invoice->total_eur;
        $oldStatus  = $invoice->status;

        $this->reversePromoRedemptions($invoice);

        if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
            Storage::delete($invoice->pdf_path);
        }

        $invoice->delete();

        \App\Models\PlatformAuditLog::record(
            'platform.billing.invoice_deleted',
            [
                'target_type'    => 'invoice',
                'target_id'      => null,
                'invoice_number' => $number,
                'company_id'     => $companyId,
                'old_status'     => $oldStatus,
                'total_eur'      => $totalEur,
            ],
            \App\Models\PlatformAuditLog::SEVERITY_WARNING
        );

        return redirect()
            ->route('platform.billing.index')
            ->with('success', "Fatura silindi: {$number}");
    }

    /**
     * Bu faturaya bagli promo redemption(lar)i geri al:
     *   - PromoCode current_uses-- (0'in altina dusmez)
     *   - redemption kaydini sil
     * Boylece iptal/silinen fatura promo kullanim hakkini geri verir.
     */
    private function reversePromoRedemptions(PlatformInvoice $invoice): void
    {
        $redemptions = \App\Models\PromoCodeRedemption::query()
            ->where('company_id', $invoice->company_id)
            ->get()
            ->filter(fn ($r) => in_array($invoice->id, (array) $r->invoice_ids, true));

        foreach ($redemptions as $redemption) {
            $promo = PromoCode::query()->find($redemption->promo_code_id);
            if ($promo && $promo->current_uses > 0) {
                $promo->decrement('current_uses');
            }
            $redemption->delete();
        }
    }
}
