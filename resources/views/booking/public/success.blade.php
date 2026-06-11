<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Alındı — {{ $brandName ?? 'MentorDE' }}</title>
    @include('partials.favicon')
    @vite(['resources/css/premium.css'])
    <style>
        body { margin:0; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background:#f8fafc; color:#0f172a; }
        .bs-wrap { max-width:560px; margin:60px auto; padding:0 16px; }
        .bs-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:32px; box-shadow:0 1px 3px rgba(0,0,0,.05); }
        .bs-head { text-align:center; margin-bottom:22px; }
        .bs-head .bs-emoji { font-size:42px; margin-bottom:8px; }
        .bs-head h1 { margin:0 0 6px; font-size:22px; color:#166534; }
        .bs-head .bs-sub { color:#475569; font-size:13.5px; }
        .bs-summary { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:16px 18px; margin-bottom:20px; font-size:14px; line-height:1.75; }
        .bs-summary strong { color:#0f172a; }
        .bs-status { padding:4px 10px; border-radius:12px; font-size:11px; font-weight:700; display:inline-block; }
        .bs-status.ok { background:#dcfce7; color:#166534; }
        .bs-status.pending { background:#fef3c7; color:#92400e; }
        .bs-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:18px; }
        .bs-btn { padding:11px 22px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; text-align:center; }
        .bs-btn-primary { background:#166534; color:#fff; }
        .bs-btn-ghost { background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0; }
        .bs-note { font-size:12px; color:#64748b; line-height:1.6; margin-top:14px; text-align:center; }
    </style>
</head>
<body>

<div class="bs-wrap">
    <div class="bs-card">
        <div class="bs-head">
            <div class="bs-emoji">{{ $booking->isPaid() ? 'OK' : '...' }}</div>
            <h1>{{ $booking->isPaid() ? 'Ödeme Alındı — Randevun Onaylandı' : 'Ödeme Alınıyor…' }}</h1>
            <div class="bs-sub">
                @if($booking->isPaid())
                    Randevunun onayı e-posta adresine gönderildi.
                @else
                    Stripe ödemen işlemde. Onay e-postası birkaç saniye içinde gelecek.
                @endif
            </div>
        </div>

        @php
            $tz = $settings?->timezone ?: 'Europe/Berlin';
            $startsLocal = \Carbon\CarbonImmutable::parse($booking->starts_at)->setTimezone($tz);
        @endphp

        <div class="bs-summary">
            <div><strong>Tarih:</strong> {{ $startsLocal->format('d.m.Y H:i') }} ({{ $tz }})</div>
            <div><strong>Süre:</strong> {{ $settings?->slot_duration ?? '—' }} dakika</div>
            @if($senior)
                <div><strong>Danışman:</strong> {{ $senior->name }}</div>
            @endif
            <div><strong>Tutar:</strong> {{ number_format($booking->amountGrossEur(), 2, ',', '.') }} {{ $booking->currency ?: 'EUR' }}</div>
            <div style="margin-top:6px;">
                <strong>Durum:</strong>
                @if($booking->isPaid())
                    <span class="bs-status ok">Ödendi · Onaylandı</span>
                @elseif($booking->isPendingPayment())
                    <span class="bs-status pending">Ödeme bekleniyor</span>
                @else
                    <span class="bs-status pending">{{ $booking->payment_status }}</span>
                @endif
            </div>
        </div>

        <div class="bs-actions">
            <a class="bs-btn bs-btn-primary" href="{{ url('/') }}">Ana Sayfaya Dön</a>
            <a class="bs-btn bs-btn-ghost" href="{{ route('booking.public.cancel.show', ['token' => $booking->booking_token]) }}">
                Randevu Detayı
            </a>
        </div>

        <div class="bs-note">
            Bu sayfayı kapatabilirsiniz. İptal etmek istersen, e-postandaki bağlantıyı kullanabilir veya randevu saatinden en az
            {{ optional($settings)->cancellation_window_hours ?? 24 }} saat önce iptal edebilirsin.
        </div>
    </div>
</div>

</body>
</html>
