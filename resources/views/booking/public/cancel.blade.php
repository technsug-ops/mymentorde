<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu İptal — {{ $brandName ?? 'MentorDE' }}</title>
    @vite(['resources/css/premium.css'])
    <style>
        body { margin:0; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background:#f8fafc; color:#0f172a; }
        .bc-wrap { max-width:540px; margin:60px auto; padding:0 16px; }
        .bc-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:28px; box-shadow:0 1px 3px rgba(0,0,0,.05); }
        .bc-head { text-align:center; margin-bottom:20px; }
        .bc-head h1 { margin:0 0 4px; font-size:20px; }
        .bc-summary { background:#f1f5f9; border-radius:8px; padding:14px 16px; margin-bottom:20px; font-size:13px; line-height:1.7; }
        .bc-summary strong { color:#0f172a; }
        .bc-status { padding:4px 10px; border-radius:12px; font-size:11px; font-weight:700; display:inline-block; }
        .bc-status.ok { background:#dcfce7; color:#166534; }
        .bc-status.cancel { background:#fee2e2; color:#991b1b; }
        .bc-field label { display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:4px; }
        .bc-field textarea { width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; box-sizing:border-box; }
        .bc-btn { padding:11px 22px; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
        .bc-btn-danger { background:#dc2626; color:#fff; }
        .bc-btn-ghost { background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0; }
        .bc-msg-ok { background:#d1fae5; border:1px solid #6ee7b7; color:#065f46; padding:14px 16px; border-radius:8px; font-size:14px; margin-bottom:16px; }
        .bc-msg-err { background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:12px; }
    </style>
</head>
<body>

<div class="bc-wrap">
    <div class="bc-card">
        <div class="bc-head">
            <h1>📅 Randevu Detayı</h1>
        </div>

        @php
            $tz = $settings?->timezone ?: 'Europe/Berlin';
            $startsLocal = \Carbon\CarbonImmutable::parse($booking->starts_at)->setTimezone($tz);
            $isActive = $booking->isActive();
        @endphp

        @if (session('status'))
            <div class="bc-msg-ok">✅ {{ session('status') }}</div>
        @endif

        @if ($errors->has('cancel'))
            <div class="bc-msg-err">{{ $errors->first('cancel') }}</div>
        @endif

        <div class="bc-summary">
            <div style="margin-bottom:6px;"><strong>Kiminle:</strong> {{ $senior?->name ?? '—' }}</div>
            <div style="margin-bottom:6px;"><strong>Tarih/Saat:</strong> {{ $startsLocal->format('d.m.Y H:i') }} ({{ $tz }})</div>
            <div style="margin-bottom:6px;"><strong>Süre:</strong> {{ $settings?->slot_duration ?? 30 }} dakika</div>
            <div style="margin-bottom:6px;"><strong>Invitee:</strong> {{ $booking->invitee_name }} &lt;{{ $booking->invitee_email }}&gt;</div>
            <div><strong>Durum:</strong>
                @if ($isActive)
                    <span class="bc-status ok">Onaylı</span>
                @else
                    <span class="bc-status cancel">
                        @if ($booking->status === 'canceled_by_invitee') İptal (invitee)
                        @elseif ($booking->status === 'canceled_by_senior') İptal (senior)
                        @elseif ($booking->status === 'completed') Tamamlandı
                        @else {{ $booking->status }} @endif
                    </span>
                @endif
            </div>
            @if ($booking->notes)
                <div style="margin-top:8px;padding-top:8px;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px;">
                    <strong>Not:</strong> {{ $booking->notes }}
                </div>
            @endif
        </div>

        @if ($isActive)
            <form method="POST" action="{{ route('booking.public.cancel', ['token' => $booking->booking_token]) }}">
                @csrf
                <div class="bc-field" style="margin-bottom:14px;">
                    <label>İptal gerekçesi (opsiyonel)</label>
                    <textarea name="reason" rows="3" maxlength="500" placeholder="Neden iptal ediyorsun?"></textarea>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="submit" class="bc-btn bc-btn-danger" onclick="return confirm('Randevuyu iptal etmek istediğinden emin misin?');">
                        🚫 Randevuyu İptal Et
                    </button>
                </div>
            </form>
        @else
            <div style="text-align:center;color:#64748b;font-size:13px;margin-top:20px;">
                Bu randevu zaten aktif değil.
            </div>
        @endif
    </div>
</div>

</body>
</html>
