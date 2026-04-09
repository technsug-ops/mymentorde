<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fatura {{ $payment->invoice_number }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 13px; color: #1a1a2e; background: #fff; }

    .page { padding: 40px 48px; max-width: 794px; margin: 0 auto; }

    /* ── Header ── */
    .inv-header { display: table; width: 100%; margin-bottom: 36px; }
    .inv-brand   { display: table-cell; vertical-align: middle; }
    .inv-brand-name { font-size: 22px; font-weight: 900; color: #6d28d9; letter-spacing: -0.5px; }
    .inv-brand-sub  { font-size: 11px; color: #6b7280; margin-top: 2px; }
    .inv-meta    { display: table-cell; vertical-align: middle; text-align: right; }
    .inv-title   { font-size: 28px; font-weight: 900; color: #1a1a2e; letter-spacing: -1px; }
    .inv-number  { font-size: 13px; color: #6b7280; margin-top: 4px; }

    /* ── Divider ── */
    .divider { border: none; border-top: 2px solid #e5e7eb; margin: 20px 0; }
    .divider-brand { border-color: #6d28d9; border-width: 3px; }

    /* ── Parties ── */
    .inv-parties { display: table; width: 100%; margin-bottom: 28px; }
    .inv-party   { display: table-cell; width: 50%; vertical-align: top; }
    .inv-party-label { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px; }
    .inv-party-name  { font-size: 15px; font-weight: 700; color: #1a1a2e; }
    .inv-party-detail { font-size: 12px; color: #6b7280; margin-top: 3px; line-height: 1.5; }

    /* ── Dates box ── */
    .inv-dates { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 18px; margin-bottom: 28px; display: table; width: 100%; }
    .inv-date-item { display: table-cell; text-align: center; }
    .inv-date-lbl { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
    .inv-date-val { font-size: 14px; font-weight: 700; color: #1a1a2e; margin-top: 3px; }

    /* ── Items table ── */
    .inv-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .inv-table th {
        background: #6d28d9; color: #fff;
        padding: 10px 14px; text-align: left;
        font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .inv-table th:last-child { text-align: right; }
    .inv-table td { padding: 13px 14px; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
    .inv-table td:last-child { text-align: right; font-weight: 700; }
    .inv-table tr:last-child td { border-bottom: none; }

    /* ── Total ── */
    .inv-total-box { margin-left: auto; width: 260px; }
    .inv-total-row { display: table; width: 100%; padding: 7px 0; border-bottom: 1px solid #f3f4f6; }
    .inv-total-lbl { display: table-cell; font-size: 13px; color: #6b7280; }
    .inv-total-val { display: table-cell; font-size: 13px; font-weight: 600; text-align: right; }
    .inv-total-row.grand { border-bottom: none; border-top: 2px solid #1a1a2e; padding-top: 10px; margin-top: 4px; }
    .inv-total-row.grand .inv-total-lbl { font-size: 15px; font-weight: 800; color: #1a1a2e; }
    .inv-total-row.grand .inv-total-val { font-size: 18px; font-weight: 900; color: #6d28d9; }

    /* ── Status badge ── */
    .inv-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .inv-status.paid      { background: #dcfce7; color: #166534; }
    .inv-status.pending   { background: #fef3c7; color: #92400e; }
    .inv-status.overdue   { background: #fee2e2; color: #991b1b; }
    .inv-status.cancelled { background: #f3f4f6; color: #6b7280; }

    /* ── Payment info ── */
    .inv-paid-info { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; margin-top: 16px; font-size: 12px; color: #166534; }

    /* ── Footer ── */
    .inv-footer { margin-top: 48px; padding-top: 16px; border-top: 1px solid #e5e7eb; display: table; width: 100%; }
    .inv-footer-left  { display: table-cell; font-size: 11px; color: #9ca3af; vertical-align: middle; }
    .inv-footer-right { display: table-cell; text-align: right; font-size: 11px; color: #9ca3af; vertical-align: middle; }

    /* ── Notes ── */
    .inv-notes { background: #fffbeb; border-left: 3px solid #f59e0b; padding: 10px 14px; border-radius: 0 6px 6px 0; margin-top: 16px; font-size: 12px; color: #78350f; }
</style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="inv-header">
        <div class="inv-brand">
            <div class="inv-brand-name">{{ $brandName }}</div>
            <div class="inv-brand-sub">Eğitim Danışmanlık Hizmetleri</div>
        </div>
        <div class="inv-meta">
            <div class="inv-title">FATURA</div>
            <div class="inv-number">{{ $payment->invoice_number }}</div>
            <div style="margin-top:6px;">
                @php
                    $sc = match($payment->status) { 'paid'=>'paid','overdue'=>'overdue','cancelled'=>'cancelled',default=>'pending' };
                    $sl = match($payment->status) { 'paid'=>'ÖDENDİ','overdue'=>'VADESİ GEÇTİ','cancelled'=>'İPTAL',default=>'BEKLEMEDE' };
                @endphp
                <span class="inv-status {{ $sc }}">{{ $sl }}</span>
            </div>
        </div>
    </div>

    <hr class="divider divider-brand">

    {{-- Taraflar --}}
    <div class="inv-parties">
        <div class="inv-party">
            <div class="inv-party-label">Gönderen</div>
            <div class="inv-party-name">{{ $brandName }}</div>
            <div class="inv-party-detail">
                {{ config('brand.tagline', 'Eğitim Danışmanlık') }}<br>
                {{ config('brand.email', 'info@example.com') }}
            </div>
        </div>
        <div class="inv-party" style="text-align:right;">
            <div class="inv-party-label">Alıcı</div>
            <div class="inv-party-name">{{ $student?->name ?? $payment->student_id }}</div>
            <div class="inv-party-detail">
                Öğrenci ID: {{ $payment->student_id }}<br>
                @if($student?->email){{ $student->email }}@endif
            </div>
        </div>
    </div>

    {{-- Tarihler --}}
    <div class="inv-dates">
        <div class="inv-date-item">
            <div class="inv-date-lbl">Düzenleme Tarihi</div>
            <div class="inv-date-val">{{ $payment->created_at->format('d.m.Y') }}</div>
        </div>
        <div class="inv-date-item">
            <div class="inv-date-lbl">Vade Tarihi</div>
            <div class="inv-date-val">{{ $payment->due_date->format('d.m.Y') }}</div>
        </div>
        @if($payment->paid_at)
        <div class="inv-date-item">
            <div class="inv-date-lbl">Ödeme Tarihi</div>
            <div class="inv-date-val" style="color:#166534;">{{ $payment->paid_at->format('d.m.Y') }}</div>
        </div>
        @endif
    </div>

    {{-- Kalem tablosu --}}
    <table class="inv-table">
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th>Açıklama</th>
                <th style="width:80px;text-align:center;">Para Birimi</th>
                <th style="width:120px;">Tutar</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>{{ $payment->description }}</td>
                <td style="text-align:center;">{{ $payment->currency }}</td>
                <td>{{ number_format($payment->amount_eur, 2, ',', '.') }} {{ $payment->currency }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Toplam --}}
    <div class="inv-total-box">
        <div class="inv-total-row">
            <span class="inv-total-lbl">Ara Toplam</span>
            <span class="inv-total-val">{{ number_format($payment->amount_eur, 2, ',', '.') }} {{ $payment->currency }}</span>
        </div>
        <div class="inv-total-row grand">
            <span class="inv-total-lbl">TOPLAM</span>
            <span class="inv-total-val">{{ number_format($payment->amount_eur, 2, ',', '.') }} {{ $payment->currency }}</span>
        </div>
    </div>

    {{-- Ödeme bilgisi --}}
    @if($payment->status === 'paid')
    <div class="inv-paid-info">
        ✓ Bu fatura ödenmiştir.
        @if($payment->payment_method)
            Ödeme yöntemi: {{ match($payment->payment_method) {
                'bank_transfer'=>'Banka Transferi','credit_card'=>'Kredi Kartı','cash'=>'Nakit',default=>'Diğer'
            } }}.
        @endif
    </div>
    @endif

    {{-- Notlar --}}
    @if($payment->notes)
    <div class="inv-notes">
        <strong>Not:</strong> {{ $payment->notes }}
    </div>
    @endif

    {{-- Footer --}}
    <div class="inv-footer">
        <div class="inv-footer-left">
            {{ $brandName }} · Eğitim Danışmanlık Hizmetleri<br>
            Bu belge {{ now()->format('d.m.Y H:i') }} tarihinde oluşturulmuştur.
        </div>
        <div class="inv-footer-right">
            {{ $payment->invoice_number }}<br>
            Sayfa 1/1
        </div>
    </div>

</div>
</body>
</html>
