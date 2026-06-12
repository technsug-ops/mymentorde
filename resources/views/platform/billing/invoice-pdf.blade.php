<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 32mm 18mm 22mm 18mm; }
        body { font-family: "DejaVu Sans", sans-serif; color: #1f2937; font-size: 11pt; line-height: 1.5; margin: 0; }

        .pdf-header { border-bottom: 3px solid #7e58bf; padding-bottom: 16px; margin-bottom: 24px; }
        .pdf-header-row { width: 100%; }
        .pdf-header-row td { vertical-align: top; }
        .pdf-brand { font-size: 22pt; font-weight: 800; color: #7e58bf; letter-spacing: -0.5px; }
        .pdf-brand-sub { font-size: 9pt; color: #6b7280; margin-top: 2px; letter-spacing: 1px; text-transform: uppercase; }

        .pdf-meta { text-align: right; }
        .pdf-meta-title { font-size: 18pt; font-weight: 800; color: #111827; margin-bottom: 6px; }
        .pdf-meta-row { font-size: 10pt; margin-bottom: 3px; }
        .pdf-meta-label { color: #6b7280; }
        .pdf-meta-value { color: #111827; font-weight: 700; }

        .pdf-sec { margin-bottom: 20px; }
        .pdf-sec-title { font-size: 9pt; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .pdf-sec-box { padding: 12px 14px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; }
        .pdf-sec-box strong { font-size: 12pt; color: #111827; }

        .pdf-table { width: 100%; border-collapse: collapse; margin: 18px 0; }
        .pdf-table th { text-align: left; padding: 10px 12px; font-size: 9pt; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; background: #f3f4f6; border-bottom: 2px solid #7e58bf; }
        .pdf-table th.r, .pdf-table td.r { text-align: right; }
        .pdf-table td { padding: 12px; font-size: 11pt; border-bottom: 1px solid #e5e7eb; }
        .pdf-table .desc { color: #111827; font-weight: 600; }
        .pdf-table .desc-sub { font-size: 9pt; color: #6b7280; margin-top: 2px; }

        .pdf-totals { margin-left: auto; width: 50%; margin-top: 10px; }
        .pdf-totals td { padding: 6px 12px; font-size: 11pt; }
        .pdf-totals .label { color: #6b7280; text-align: right; }
        .pdf-totals .value { text-align: right; color: #111827; font-weight: 600; }
        .pdf-totals .total .label, .pdf-totals .total .value { font-size: 14pt; font-weight: 800; color: #7e58bf; border-top: 2px solid #7e58bf; padding-top: 10px; }

        .pdf-status { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 9pt; font-weight: 700; text-transform: uppercase; }
        .pdf-status-paid    { background: #d1fae5; color: #065f46; }
        .pdf-status-sent    { background: #dbeafe; color: #1e40af; }
        .pdf-status-overdue { background: #fee2e2; color: #991b1b; }
        .pdf-status-draft   { background: #f3f4f6; color: #4b5563; }

        .pdf-footer { margin-top: 36px; padding-top: 18px; border-top: 1px solid #e5e7eb; font-size: 9pt; color: #6b7280; }
        .pdf-footer p { margin: 4px 0; }
        .pdf-footer strong { color: #111827; }
    </style>
</head>
<body>

{{-- HEADER --}}
<div class="pdf-header">
    <table class="pdf-header-row">
        <tr>
            <td style="width:60%;">
                <div class="pdf-brand">DGmarkt</div>
                <div class="pdf-brand-sub">Mentoring Platform</div>
                <div style="margin-top:14px;font-size:9pt;color:#6b7280;line-height:1.6;">
                    DGmarkt GmbH<br>
                    Berlin, Deutschland<br>
                    billing@mentorde.com
                </div>
            </td>
            <td class="pdf-meta" style="width:40%;">
                <div class="pdf-meta-title">FATURA</div>
                <div class="pdf-meta-row">
                    <span class="pdf-meta-label">No:</span>
                    <span class="pdf-meta-value">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="pdf-meta-row">
                    <span class="pdf-meta-label">Tarih:</span>
                    <span class="pdf-meta-value">{{ $invoice->created_at->format('d.m.Y') }}</span>
                </div>
                <div class="pdf-meta-row">
                    <span class="pdf-meta-label">Vade:</span>
                    <span class="pdf-meta-value">{{ $invoice->created_at->copy()->addDays(14)->format('d.m.Y') }}</span>
                </div>
                <div class="pdf-meta-row" style="margin-top:6px;">
                    <span class="pdf-status pdf-status-{{ $invoice->status === 'paid' ? 'paid' : ($invoice->status === 'overdue' ? 'overdue' : ($invoice->status === 'sent' ? 'sent' : 'draft')) }}">
                        {{ $invoice->statusLabel() }}
                    </span>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- MUSTERI INFO --}}
<div class="pdf-sec">
    <div class="pdf-sec-title">Fatura Edilen</div>
    <div class="pdf-sec-box">
        @if($company)
            <strong>{{ $company->name }}</strong><br>
            <span style="color:#6b7280;font-size:10pt;">Müşteri Kodu: {{ $company->code }}</span>
            @if($company->billing_email)
                <br><span style="font-size:10pt;">{{ $company->billing_email }}</span>
            @endif
        @else
            <strong>Şirket bilgisi mevcut değil</strong>
        @endif
    </div>
</div>

{{-- KALEM --}}
<table class="pdf-table">
    <thead>
        <tr>
            <th style="width:60%;">Açıklama</th>
            <th class="r" style="width:13%;">Adet</th>
            <th class="r" style="width:27%;">Tutar</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <div class="desc">
                    DGmarkt Abonelik — {{ config("subscription_tiers.{$invoice->tier}.label", ucfirst($invoice->tier)) }}
                </div>
                <div class="desc-sub">
                    Dönem: {{ optional($invoice->period_start)->format('d.m.Y') }} — {{ optional($invoice->period_end)->format('d.m.Y') }}
                </div>
            </td>
            <td class="r">1</td>
            <td class="r">€{{ number_format((float) $invoice->amount_eur, 2, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

{{-- TOTALS --}}
<table class="pdf-totals">
    <tr>
        <td class="label">Ara Toplam:</td>
        <td class="value">€{{ number_format((float) $invoice->amount_eur, 2, ',', '.') }}</td>
    </tr>
    <tr>
        <td class="label">KDV (%{{ number_format((float) $invoice->tax_rate_pct, 0) }}):</td>
        <td class="value">€{{ number_format((float) $invoice->tax_amount_eur, 2, ',', '.') }}</td>
    </tr>
    <tr class="total">
        <td class="label">TOPLAM:</td>
        <td class="value">€{{ number_format((float) $invoice->total_eur, 2, ',', '.') }}</td>
    </tr>
</table>

{{-- FOOTER --}}
<div class="pdf-footer">
    <p><strong>Ödeme Koşulları:</strong> Faturanın tarihinden itibaren 14 gün içinde ödenmelidir.</p>
    <p><strong>Banka Bilgileri:</strong> DGmarkt GmbH — IBAN: DE00 0000 0000 0000 0000 00 — BIC: XXXXDEXXX</p>
    <p><strong>Açıklama:</strong> Lütfen ödeme açıklamasında fatura numarasını ({{ $invoice->invoice_number }}) belirtin.</p>
    <p style="margin-top:10px;">
        Sorular için: <strong>billing@mentorde.com</strong><br>
        Web: <strong>https://mentorde.com</strong>
    </p>
</div>

</body>
</html>
