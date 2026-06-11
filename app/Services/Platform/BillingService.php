<?php

namespace App\Services\Platform;

use App\Models\Company;
use App\Models\PlatformInvoice;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Platform Owner — Billing service (Faz 7).
 *
 * Mentorde'nin musteri company'lere faturalama is mantigi:
 *   - generateInvoiceFor() : tier'a gore aylik fatura olustur (draft)
 *   - sendInvoice()        : PDF olustur + billing_email'e gonder, sent statusune al
 *   - markPaid()           : manuel veya Stripe webhook'tan paid'e isaretle
 *   - checkOverdue()       : 30 gunden eski 'sent' faturalari overdue yap
 *
 * %19 KDV Almanya standardi (config/billing.tax_rate_pct override edebilir).
 * Invoice number format: INV-YYYYMM-{seq} (ay icindeki sira numarasi).
 *
 * Bagimsiz tasarim: PDF olusturmazsa fatura yine 'sent' statusune gecer (HTML
 * mail fallback). Stripe sync opsiyonel — markPaid() Stripe webhook tarafindan
 * cagrilabilir ama servisi calismasi icin Stripe SDK'ya bagimli degil.
 */
class BillingService
{
    public const DEFAULT_TAX_RATE_PCT = 19.00; // DE KDV

    /**
     * Belirtilen donem icin company'e taslak fatura olustur.
     *
     * $period parametresi periodun BASLANGIC tarihini gosterir; period_end ayni
     * ayin son gunudur. Tier amount config/subscription_tiers.{tier}.mrr_eur'dan
     * okunur. Trial (mrr_eur=0) icin fatura uretilmez (null donerken caller
     * decide etmeli — burada normal 0'lik fatura olusturulur, calling code
     * trial'i filtreler).
     */
    public function generateInvoiceFor(Company $company, ?CarbonInterface $period = null): PlatformInvoice
    {
        $period      = $period ? Carbon::instance($period) : Carbon::now()->startOfMonth();
        $periodStart = $period->copy()->startOfMonth()->toDateString();
        $periodEnd   = $period->copy()->endOfMonth()->toDateString();

        $tier        = (string) ($company->subscription_tier ?? Company::TIER_TRIAL);
        $tierConfig  = config("subscription_tiers.{$tier}", []);
        $amount      = (float) ($company->mrr_eur ?? ($tierConfig['mrr_eur'] ?? 0));

        $taxRate     = (float) config('billing.tax_rate_pct', self::DEFAULT_TAX_RATE_PCT);
        $taxAmount   = round($amount * ($taxRate / 100), 2);
        $total       = round($amount + $taxAmount, 2);

        $invoiceNumber = $this->nextInvoiceNumber($period);

        return DB::transaction(function () use (
            $company, $invoiceNumber, $periodStart, $periodEnd, $tier,
            $amount, $taxRate, $taxAmount, $total
        ): PlatformInvoice {
            return PlatformInvoice::create([
                'company_id'      => $company->id,
                'invoice_number'  => $invoiceNumber,
                'period_start'    => $periodStart,
                'period_end'      => $periodEnd,
                'tier'            => $tier,
                'amount_eur'      => $amount,
                'tax_rate_pct'    => $taxRate,
                'tax_amount_eur'  => $taxAmount,
                'total_eur'       => $total,
                'status'          => PlatformInvoice::STATUS_DRAFT,
            ]);
        });
    }

    /**
     * PDF olustur + billing_email'e mail gonder + status='sent'.
     *
     * Donus: true = mail kuyrugu/SMTP'ye atildi, false = adres yok veya hata.
     * Hata durumunda Log::warning ile loglanir, exception throw etmez.
     */
    public function sendInvoice(PlatformInvoice $invoice): bool
    {
        $invoice->loadMissing('company');
        $company = $invoice->company;
        if (!$company) {
            Log::warning('platform.billing.send: company missing', ['invoice_id' => $invoice->id]);
            return false;
        }

        $email = trim((string) ($company->billing_email ?? ''));
        if ($email === '') {
            Log::warning('platform.billing.send: billing_email empty', [
                'invoice_id' => $invoice->id,
                'company_id' => $company->id,
            ]);
            return false;
        }

        // PDF olustur (mumkunse) ve storage'a kaydet
        $pdfPath = $this->renderPdf($invoice);
        if ($pdfPath) {
            $invoice->pdf_path = $pdfPath;
        }

        // Mail govdesi — HTML basit, attach PDF varsa ekle
        $subject = "Mentorde Faturası — {$invoice->invoice_number}";
        $body    = $this->buildEmailBody($invoice, $company);

        try {
            Mail::send([], [], function ($message) use ($email, $subject, $body, $invoice, $pdfPath): void {
                $message->to($email)
                    ->subject($subject)
                    ->html($body);

                if ($pdfPath && Storage::disk('local')->exists($pdfPath)) {
                    $message->attach(Storage::disk('local')->path($pdfPath), [
                        'as'   => $invoice->invoice_number . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }
            });
        } catch (Throwable $e) {
            Log::warning('platform.billing.send: mail failed', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }

        $invoice->status  = PlatformInvoice::STATUS_SENT;
        $invoice->sent_at = now();
        $invoice->save();
        return true;
    }

    /**
     * Manuel veya Stripe webhook'tan paid'e isaretle.
     */
    public function markPaid(PlatformInvoice $invoice, ?string $stripeInvoiceId = null): void
    {
        $invoice->status  = PlatformInvoice::STATUS_PAID;
        $invoice->paid_at = now();
        if ($stripeInvoiceId) {
            $invoice->stripe_invoice_id = $stripeInvoiceId;
        }
        $invoice->save();
    }

    /**
     * 30 gunden eski 'sent' faturalari overdue isaretle.
     * Returns: kac fatura overdue yapildi.
     */
    public function checkOverdue(int $graceDays = 30): int
    {
        $cutoff = now()->subDays($graceDays);
        return PlatformInvoice::query()
            ->where('status', PlatformInvoice::STATUS_SENT)
            ->where('sent_at', '<', $cutoff)
            ->update([
                'status'     => PlatformInvoice::STATUS_OVERDUE,
                'updated_at' => now(),
            ]);
    }

    /**
     * Bir company icin belirli donemde fatura zaten var mi?
     */
    public function invoiceExistsFor(Company $company, CarbonInterface $period): bool
    {
        return PlatformInvoice::query()
            ->where('company_id', $company->id)
            ->whereDate('period_start', $period->copy()->startOfMonth()->toDateString())
            ->exists();
    }

    // ────────────────────────────────────────────────────────────────────────
    // INTERNAL
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Invoice number: INV-YYYYMM-{seq}, seq = bu aydaki sirayi 4 hane.
     */
    private function nextInvoiceNumber(CarbonInterface $period): string
    {
        $prefix = 'INV-' . $period->format('Ym') . '-';
        $count  = PlatformInvoice::query()
            ->where('invoice_number', 'like', $prefix . '%')
            ->count();
        $seq = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
        $candidate = $prefix . $seq;

        // Cok nadir race condition'a karsi guard — unique zaten korumada
        $i = $count + 1;
        while (PlatformInvoice::query()->where('invoice_number', $candidate)->exists()) {
            $i++;
            $candidate = $prefix . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
        }
        return $candidate;
    }

    /**
     * PDF olustur, storage/app/invoices/{year}/{invoice_number}.pdf yoluna kaydet.
     * Donus: relative path veya null (hata).
     */
    private function renderPdf(PlatformInvoice $invoice): ?string
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            return null;
        }

        try {
            $html = view('platform.billing.invoice-pdf', [
                'invoice' => $invoice,
                'company' => $invoice->company,
            ])->render();

            $dompdf = new \Dompdf\Dompdf([
                'isRemoteEnabled' => false,
                'defaultFont'     => 'DejaVu Sans',
            ]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $year = $invoice->period_start ? $invoice->period_start->format('Y') : date('Y');
            $rel  = "invoices/{$year}/{$invoice->invoice_number}.pdf";

            Storage::disk('local')->put($rel, $dompdf->output());
            return $rel;
        } catch (Throwable $e) {
            Log::warning('platform.billing.pdf: render failed', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function buildEmailBody(PlatformInvoice $invoice, Company $company): string
    {
        $period = $invoice->period_start
            ? $invoice->period_start->format('m/Y')
            : '';
        $totalFmt = number_format((float) $invoice->total_eur, 2, ',', '.');
        $tierLabel = config("subscription_tiers.{$invoice->tier}.label", $invoice->tier);

        return <<<HTML
<div style="font-family:Arial,sans-serif;color:#222;line-height:1.55;">
    <p>Sayın {$company->name} yetkilisi,</p>

    <p>
        {$period} dönemine ait <strong>Mentorde</strong> aboneliğiniz için fatura ektedir.
    </p>

    <table style="border-collapse:collapse;margin:12px 0;">
        <tr><td style="padding:4px 8px;color:#666;">Fatura No</td><td style="padding:4px 8px;"><strong>{$invoice->invoice_number}</strong></td></tr>
        <tr><td style="padding:4px 8px;color:#666;">Plan</td><td style="padding:4px 8px;">{$tierLabel}</td></tr>
        <tr><td style="padding:4px 8px;color:#666;">Toplam</td><td style="padding:4px 8px;"><strong>€{$totalFmt}</strong></td></tr>
    </table>

    <p style="font-size:13px;color:#555;">
        Ödeme süresi: bu tarihten itibaren 14 gün. Sorularınız için lütfen
        <a href="mailto:billing@mentorde.com">billing@mentorde.com</a> adresine yazın.
    </p>

    <p>Teşekkürler,<br>Mentorde Ekibi</p>
</div>
HTML;
    }
}
