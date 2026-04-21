@extends('student.layouts.app')

@section('title', 'Vize Başvurusu')
@section('page_title', 'Vize Başvurusu')

@push('head')
<style>
/* ══════ Hero (Option B) ══════ */
.visa-hero { color:#fff; border-radius:14px; margin-bottom:16px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.1); position:relative;
    background:#7f1d1d url('https://images.unsplash.com/photo-1569974498991-d3c12a504f95?w=1400&q=80') center/cover; }
.visa-hero::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg, rgba(127,29,29,.93) 0%, rgba(220,38,38,.82) 100%); }
.visa-hero-body { position:relative; display:flex; align-items:center; gap:20px; padding:22px 26px; }
.visa-hero-main { flex:1; min-width:0; display:flex; flex-direction:column; gap:7px; }
.visa-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.85; }
.visa-hero-marker { display:inline-block; width:5px; height:14px; background:rgba(255,255,255,.75); border-radius:3px; }
.visa-hero-title { font-size:24px; font-weight:800; line-height:1.1; margin:0; letter-spacing:-.3px; }
.visa-hero-sub { font-size:12.5px; opacity:.88; line-height:1.5; max-width:560px; }
.visa-hero-stats { display:flex; gap:7px; flex-wrap:wrap; margin-top:8px; padding-top:12px; border-top:1px solid rgba(255,255,255,.2); }
.visa-hero-stat { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:18px; background:rgba(255,255,255,.18); font-size:11.5px; font-weight:600; line-height:1; border:1px solid rgba(255,255,255,.12); }
.visa-hero-icon { font-size:50px; line-height:1; flex-shrink:0; opacity:.88; filter:drop-shadow(0 4px 12px rgba(0,0,0,.25)); }
@media (max-width:640px){ .visa-hero-body { gap:14px; padding:18px; align-items:flex-start; } .visa-hero-title { font-size:20px; } .visa-hero-sub { font-size:12px; } .visa-hero-icon { font-size:36px; } }

/* ── visa-* scoped ── */
.visa-status-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 16px; padding: 24px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 20px;
}
@media(max-width:640px){ .visa-status-card { flex-direction: column; align-items: flex-start; } }

.visa-status-icon {
    width: 64px; height: 64px; border-radius: 16px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 28px;
    background: rgba(124,58,237,.1); border: 2px solid rgba(124,58,237,.2);
}
.visa-status-icon.approved { background: rgba(22,163,74,.1); border-color: rgba(22,163,74,.2); }
.visa-status-icon.rejected { background: rgba(220,38,38,.1); border-color: rgba(220,38,38,.2); }
.visa-status-icon.in_review { background: rgba(217,119,6,.1); border-color: rgba(217,119,6,.2); }

.visa-status-info { flex: 1; }
.visa-status-title { font-size: 18px; font-weight: 800; color: var(--u-text); margin-bottom: 4px; }
.visa-status-sub   { font-size: 13px; color: var(--u-muted); }

.visa-steps {
    display: flex; gap: 0; margin-bottom: 24px; overflow-x: auto;
    border: 1px solid var(--u-line); border-radius: 12px; overflow: hidden;
}
.visa-step {
    flex: 1; min-width: 90px; padding: 14px 10px; text-align: center;
    border-right: 1px solid var(--u-line); position: relative;
    background: var(--u-card);
}
.visa-step:last-child { border-right: none; }
.visa-step.done    { background: rgba(22,163,74,.06); }
.visa-step.active  { background: rgba(124,58,237,.06); }
.visa-step-icon { font-size: 18px; margin-bottom: 4px; }
.visa-step-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--u-muted); }
.visa-step.done .visa-step-label   { color: #16a34a; }
.visa-step.active .visa-step-label { color: #7c3aed; }

.visa-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
@media(max-width:600px){ .visa-grid { grid-template-columns: 1fr; } }

.visa-info-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; padding: 18px;
}
.visa-info-card h4 {
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: var(--u-muted); margin: 0 0 12px;
}
.visa-field { margin-bottom: 10px; }
.visa-field:last-child { margin-bottom: 0; }
.visa-field-label { font-size: 11px; color: var(--u-muted); margin-bottom: 2px; }
.visa-field-value { font-size: 14px; font-weight: 600; color: var(--u-text); }

.visa-docs-list { list-style: none; padding: 0; margin: 0; }
.visa-docs-list li {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 0; border-bottom: 1px solid var(--u-line); font-size: 14px;
}
.visa-docs-list li:last-child { border-bottom: none; }
.visa-docs-list li span.chk { color: #16a34a; font-size: 16px; }

.visa-empty {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 16px; padding: 48px 24px; text-align: center;
}
.visa-empty-icon { font-size: 48px; margin-bottom: 12px; }
.visa-empty-title { font-size: 18px; font-weight: 700; color: var(--u-text); margin-bottom: 8px; }
.visa-empty-sub { font-size: 14px; color: var(--u-muted); max-width: 380px; margin: 0 auto 20px; line-height: 1.6; }

.visa-guide-card {
    background: linear-gradient(135deg, rgba(124,58,237,.06) 0%, rgba(37,99,235,.06) 100%);
    border: 1px solid rgba(124,58,237,.15); border-radius: 16px; padding: 20px; margin-top: 20px;
}
.visa-guide-card h4 { font-size: 14px; font-weight: 700; color: var(--u-text); margin: 0 0 12px; }
.visa-guide-steps { list-style: none; padding: 0; margin: 0; }
.visa-guide-steps li {
    display: flex; gap: 12px; align-items: flex-start;
    padding: 8px 0; border-bottom: 1px solid rgba(124,58,237,.1); font-size: 13px; color: var(--u-text);
}
.visa-guide-steps li:last-child { border-bottom: none; }
.visa-guide-steps .step-num {
    width: 24px; height: 24px; border-radius: 50%; flex-shrink: 0;
    background: #7c3aed; color: #fff; font-size: 11px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}
</style>
@endpush

@section('content')

@php
    $visaStatusLabel = [
        'not_started' => 'Başlanmadı',
        'preparing'   => 'Hazırlıkta',
        'submitted'   => 'Gönderildi',
        'in_review'   => 'İncelemede',
        'approved'    => 'Onaylandı',
        'rejected'    => 'Reddedildi',
        'expired'     => 'Süresi Doldu',
    ];
    $visaStatusNow = $visaStatusLabel[$visa?->status ?? 'not_started'] ?? 'Belirsiz';
    $visaApptDate  = $visa?->appointment_date;
    $visaDaysLeft  = $visaApptDate ? (int) now()->diffInDays($visaApptDate, false) : null;
@endphp

{{-- ══════ Hero ══════ --}}
<div class="visa-hero">
    <div class="visa-hero-body">
        <div class="visa-hero-main">
            <div class="visa-hero-label"><span class="visa-hero-marker"></span>Vize Takibi</div>
            <h1 class="visa-hero-title">Vize Başvurusu</h1>
            <div class="visa-hero-sub">Konsolosluk süreci, randevu tarihi ve gerekli belgeler. Danışmanın her aşamada yanında.</div>
            <div class="visa-hero-stats">
                <span class="visa-hero-stat">🛂 {{ $visaStatusNow }}</span>
                @if($visaApptDate)<span class="visa-hero-stat">📅 Randevu: {{ $visaApptDate->format('d.m.Y') }}</span>@endif
                @if($visaDaysLeft !== null && $visaDaysLeft >= 0)<span class="visa-hero-stat">⏳ {{ $visaDaysLeft }} gün kaldı</span>@endif
            </div>
        </div>
        <div class="visa-hero-icon">🛂</div>
    </div>
</div>

@include('partials.student-process-tabs', ['active' => 'visa'])

@php
$steps = [
    ['key' => 'not_started', 'label' => 'Başlangıç',   'icon' => '📋'],
    ['key' => 'preparing',   'label' => 'Hazırlık',     'icon' => '📝'],
    ['key' => 'submitted',   'label' => 'Gönderildi',   'icon' => '📤'],
    ['key' => 'in_review',   'label' => 'İnceleme',     'icon' => '🔍'],
    ['key' => 'approved',    'label' => 'Onaylandı',    'icon' => '✅'],
];
$currentStatus = $visa?->status ?? 'not_started';
$stepOrder = array_column($steps, 'key');
$currentIdx = array_search($currentStatus, $stepOrder);
if ($currentStatus === 'rejected') $currentIdx = 3;
if ($currentStatus === 'expired')  $currentIdx = 5;
@endphp

@if($visa)
{{-- Durum Kartı --}}
<div class="visa-status-card">
    @php
    $iconClass = match($visa->status) {
        'approved' => 'approved',
        'rejected' => 'rejected',
        'in_review' => 'in_review',
        default    => ''
    };
    $iconEmoji = match($visa->status) {
        'approved' => '✅',
        'rejected' => '❌',
        'in_review' => '🔍',
        'submitted' => '📤',
        'preparing' => '📝',
        default    => '📋'
    };
    @endphp
    <div class="visa-status-icon {{ $iconClass }}">{{ $iconEmoji }}</div>
    <div class="visa-status-info">
        <div class="visa-status-title">{{ $visa->visaTypeLabel() }}</div>
        <div class="visa-status-sub">
            Durum: <span class="badge {{ $visa->statusBadge() }}">{{ $visa->statusLabel() }}</span>
            @if($visa->consulate_city)
                &nbsp;·&nbsp; Konsolosluk: {{ $visa->consulate_city }}
            @endif
        </div>
    </div>
    @if($visa->appointment_date)
    <div style="text-align:right;flex-shrink:0;">
        <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:2px;">Randevu Tarihi</div>
        <div style="font-size:var(--tx-base);font-weight:700;color:var(--u-text);">{{ $visa->appointment_date->format('d.m.Y') }}</div>
        @php $daysLeft = now()->diffInDays($visa->appointment_date, false); @endphp
        @if($daysLeft >= 0)
        <div style="font-size:var(--tx-xs);color:#d97706;">{{ $daysLeft }} gün kaldı</div>
        @endif
    </div>
    @endif
</div>

{{-- Adım Çizelgesi --}}
@if($visa->status !== 'rejected' && $visa->status !== 'expired')
<div class="visa-steps">
    @foreach($steps as $i => $step)
    @php
    $isDone   = $i < $currentIdx;
    $isActive = $i === $currentIdx;
    @endphp
    <div class="visa-step {{ $isDone ? 'done' : ($isActive ? 'active' : '') }}">
        <div class="visa-step-icon">
            {{ $isDone ? '✅' : $step['icon'] }}
        </div>
        <div class="visa-step-label">{{ $step['label'] }}</div>
    </div>
    @endforeach
</div>
@endif

{{-- Detay Grid --}}
<div class="visa-grid">
    <div class="visa-info-card">
        <h4>Vize Bilgileri</h4>
        <div class="visa-field">
            <div class="visa-field-label">Vize Türü</div>
            <div class="visa-field-value">{{ $visa->visaTypeLabel() }}</div>
        </div>
        @if($visa->application_date)
        <div class="visa-field">
            <div class="visa-field-label">Başvuru Tarihi</div>
            <div class="visa-field-value">{{ $visa->application_date->format('d.m.Y') }}</div>
        </div>
        @endif
        @if($visa->decision_date)
        <div class="visa-field">
            <div class="visa-field-label">Karar Tarihi</div>
            <div class="visa-field-value">{{ $visa->decision_date->format('d.m.Y') }}</div>
        </div>
        @endif
        @if($visa->visa_number)
        <div class="visa-field">
            <div class="visa-field-label">Vize Numarası</div>
            <div class="visa-field-value">{{ $visa->visa_number }}</div>
        </div>
        @endif
        @if($visa->valid_until)
        <div class="visa-field">
            <div class="visa-field-label">Geçerlilik Tarihi</div>
            <div class="visa-field-value">
                {{ $visa->valid_from?->format('d.m.Y') }} – {{ $visa->valid_until->format('d.m.Y') }}
            </div>
        </div>
        @endif
    </div>

    <div class="visa-info-card">
        <h4>Sunulan Belgeler</h4>
        @if($visa->submitted_documents && count($visa->submitted_documents) > 0)
        <ul class="visa-docs-list">
            @foreach($visa->submitted_documents as $docKey)
            <li>
                <span class="chk">✓</span>
                {{ $documentLabels[$docKey] ?? $docKey }}
            </li>
            @endforeach
        </ul>
        @else
        <p style="font-size:var(--tx-sm);color:var(--u-muted);margin:0;">Belge bilgisi girilmemiş.</p>
        @endif
    </div>
</div>

@if($visa->notes)
<div class="visa-info-card" style="margin-bottom:16px;">
    <h4>Danışman Notları</h4>
    <p style="font-size:var(--tx-sm);color:var(--u-text);margin:0;line-height:1.6;">{{ $visa->notes }}</p>
</div>
@endif

@if($visa->rejection_reason)
<div style="background:rgba(220,38,38,.06);border:1px solid rgba(220,38,38,.2);border-radius:14px;padding:16px;margin-bottom:16px;">
    <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#dc2626;margin-bottom:8px;">Red Gerekçesi</div>
    <p style="font-size:var(--tx-sm);color:var(--u-text);margin:0;line-height:1.6;">{{ $visa->rejection_reason }}</p>
</div>
@endif

@else
{{-- Vize kaydı yok --}}
<div class="visa-empty">
    <div class="visa-empty-icon">🛂</div>
    <div class="visa-empty-title">Vize Bilgisi Henüz Girilmedi</div>
    <div class="visa-empty-sub">
        Danışmanınız vize başvuru durumunuzu sisteme girdikten sonra burada görüntülenecek.
        Sorularınız için mesaj gönderin.
    </div>
    <a href="{{ route('student.messages') }}" class="btn">Danışmana Mesaj Gönder</a>
</div>
@endif

{{-- Vize Rehberi --}}
<div class="visa-guide-card">
    <h4>🇩🇪 Almanya Öğrenci Vizesi — Genel Süreç</h4>
    <ul class="visa-guide-steps">
        <li>
            <div class="step-num">1</div>
            <div><strong>Üniversite Kabul Mektubu</strong> — Başvurabileceğiniz Zulassung belgesini alın.</div>
        </li>
        <li>
            <div class="step-num">2</div>
            <div><strong>Konsolosluk Randevusu</strong> — İstanbul, Ankara veya İzmir'deki Alman konsolosluğundan randevu alın (genellikle 2-8 hafta öncesinden).</div>
        </li>
        <li>
            <div class="step-num">3</div>
            <div><strong>Belge Hazırlığı</strong> — Pasaport, finansal kanıt, sağlık sigortası, kira sözleşmesi ve diğer belgeleri tamamlayın.</div>
        </li>
        <li>
            <div class="step-num">4</div>
            <div><strong>Randevu Günü</strong> — Tüm orijinal belgeleriniz ve fotokopilerle konsolosluğa gidin.</div>
        </li>
        <li>
            <div class="step-num">5</div>
            <div><strong>Vize Kararı</strong> — Karar genellikle 4-12 hafta içinde verilir. Onay sonrası Almanya'ya giriş yapabilirsiniz.</div>
        </li>
    </ul>
</div>

@endsection
