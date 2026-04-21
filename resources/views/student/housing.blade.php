@extends('student.layouts.app')

@section('title', 'Konut & Barınma')
@section('page_title', 'Konut & Barınma')

@push('head')
<style>
/* ══════ Hero (Option B) ══════ */
.hsg-hero { color:#fff; border-radius:14px; margin-bottom:16px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.1); position:relative;
    background:#7c2d12 url('https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1400&q=80') center/cover; }
.hsg-hero::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg, rgba(124,45,18,.93) 0%, rgba(234,88,12,.82) 100%); }
.hsg-hero-body { position:relative; display:flex; align-items:center; gap:20px; padding:22px 26px; }
.hsg-hero-main { flex:1; min-width:0; display:flex; flex-direction:column; gap:7px; }
.hsg-hero-label { display:inline-flex; align-items:center; gap:7px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; opacity:.85; }
.hsg-hero-marker { display:inline-block; width:5px; height:14px; background:rgba(255,255,255,.75); border-radius:3px; }
.hsg-hero-title { font-size:24px; font-weight:800; line-height:1.1; margin:0; letter-spacing:-.3px; }
.hsg-hero-sub { font-size:12.5px; opacity:.88; line-height:1.5; max-width:560px; }
.hsg-hero-stats { display:flex; gap:7px; flex-wrap:wrap; margin-top:8px; padding-top:12px; border-top:1px solid rgba(255,255,255,.2); }
.hsg-hero-stat { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:18px; background:rgba(255,255,255,.18); font-size:11.5px; font-weight:600; line-height:1; border:1px solid rgba(255,255,255,.12); }
.hsg-hero-icon { font-size:50px; line-height:1; flex-shrink:0; opacity:.88; filter:drop-shadow(0 4px 12px rgba(0,0,0,.25)); }
@media (max-width:640px){ .hsg-hero-body { gap:14px; padding:18px; align-items:flex-start; } .hsg-hero-title { font-size:20px; } .hsg-hero-sub { font-size:12px; } .hsg-hero-icon { font-size:36px; } }

/* ── hsg-* scoped ── */
.hsg-status-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 16px; padding: 24px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 20px;
}
@media(max-width:640px){ .hsg-status-card { flex-direction: column; align-items: flex-start; } }

.hsg-status-icon {
    width: 64px; height: 64px; border-radius: 16px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 28px;
    background: rgba(8,145,178,.1); border: 2px solid rgba(8,145,178,.2);
}
.hsg-status-icon.confirmed { background: rgba(22,163,74,.1); border-color: rgba(22,163,74,.2); }
.hsg-status-icon.searching  { background: rgba(217,119,6,.1); border-color: rgba(217,119,6,.2); }

.hsg-status-title { font-size: 18px; font-weight: 800; color: var(--u-text); margin-bottom: 4px; }
.hsg-status-sub   { font-size: 13px; color: var(--u-muted); }

.hsg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
@media(max-width:600px){ .hsg-grid { grid-template-columns: 1fr; } }

.hsg-info-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; padding: 18px;
}
.hsg-info-card h4 {
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: var(--u-muted); margin: 0 0 12px;
}
.hsg-field { margin-bottom: 10px; }
.hsg-field:last-child { margin-bottom: 0; }
.hsg-field-label { font-size: 11px; color: var(--u-muted); margin-bottom: 2px; }
.hsg-field-value { font-size: 14px; font-weight: 600; color: var(--u-text); }

.hsg-type-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;
    background: rgba(8,145,178,.1); color: #0891b2; border: 1px solid rgba(8,145,178,.2);
}

.hsg-cost-highlight {
    background: linear-gradient(135deg, rgba(22,163,74,.06) 0%, rgba(5,150,105,.06) 100%);
    border: 1px solid rgba(22,163,74,.15); border-radius: 14px; padding: 20px;
    margin-bottom: 20px;
}
.hsg-cost-amount {
    font-size: 32px; font-weight: 800; color: #16a34a; line-height: 1;
    margin-bottom: 4px;
}
.hsg-cost-sub { font-size: 13px; color: var(--u-muted); }

.hsg-progress-bar-wrap {
    background: var(--u-line); border-radius: 6px; height: 8px; margin-top: 12px; overflow: hidden;
}
.hsg-progress-bar { height: 100%; border-radius: 6px; background: #16a34a; }

.hsg-empty {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 16px; padding: 48px 24px; text-align: center;
}
.hsg-empty-icon  { font-size: 48px; margin-bottom: 12px; }
.hsg-empty-title { font-size: 18px; font-weight: 700; color: var(--u-text); margin-bottom: 8px; }
.hsg-empty-sub   { font-size: 14px; color: var(--u-muted); max-width: 380px; margin: 0 auto 20px; line-height: 1.6; }

.hsg-guide-card {
    background: linear-gradient(135deg, rgba(8,145,178,.06) 0%, rgba(37,99,235,.06) 100%);
    border: 1px solid rgba(8,145,178,.15); border-radius: 16px; padding: 20px; margin-top: 20px;
}
.hsg-guide-card h4 { font-size: 14px; font-weight: 700; color: var(--u-text); margin: 0 0 12px; }
.hsg-types-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; }
@media(max-width:600px){ .hsg-types-grid { grid-template-columns: 1fr; } }
.hsg-type-card {
    background: var(--u-card); border: 1px solid var(--u-line); border-radius: 12px; padding: 14px;
}
.hsg-type-card-icon { font-size: 24px; margin-bottom: 6px; }
.hsg-type-card-title { font-size: 13px; font-weight: 700; color: var(--u-text); margin-bottom: 4px; }
.hsg-type-card-desc { font-size: 12px; color: var(--u-muted); line-height: 1.5; }
.hsg-type-card-cost { font-size: 12px; font-weight: 700; color: #0891b2; margin-top: 6px; }
</style>
@endpush

@section('content')

{{-- ══════ Hero ══════ --}}
@php
    $hsgStatusLabel = $accommodation
        ? ($accommodation->booking_status === 'confirmed' ? 'Onaylandı' : ($accommodation->booking_status === 'searching' ? 'Aranıyor' : ucfirst($accommodation->booking_status ?? 'Planlanıyor')))
        : 'Henüz yok';
    $hsgCityLabel = $accommodation && $accommodation->city ? $accommodation->city : '—';
    $hsgTypeLabel = $accommodation && $accommodation->type ? ($typeLabels[$accommodation->type] ?? $accommodation->type) : 'Belirlenmemiş';
@endphp
<div class="hsg-hero">
    <div class="hsg-hero-body">
        <div class="hsg-hero-main">
            <div class="hsg-hero-label"><span class="hsg-hero-marker"></span>Barınma Süreci</div>
            <h1 class="hsg-hero-title">Konut & Barınma</h1>
            <div class="hsg-hero-sub">Almanya'da yaşayacağın ev için tüm detaylar: kontrat, aylık kira, taşınma tarihi ve barınma seçenekleri.</div>
            <div class="hsg-hero-stats">
                <span class="hsg-hero-stat">🏠 {{ $hsgStatusLabel }}</span>
                <span class="hsg-hero-stat">📍 {{ $hsgCityLabel }}</span>
                <span class="hsg-hero-stat">🔑 {{ $hsgTypeLabel }}</span>
            </div>
        </div>
        <div class="hsg-hero-icon">🏠</div>
    </div>
</div>

@include('partials.student-process-tabs', ['active' => 'housing'])

@if($accommodation)

{{-- Durum Kartı --}}
@php
$iconClass = $accommodation->booking_status === 'confirmed' ? 'confirmed' : ($accommodation->booking_status === 'searching' ? 'searching' : '');
$iconEmoji = match($accommodation->booking_status) {
    'confirmed' => '🏠',
    'booked'    => '🔑',
    'applied'   => '📋',
    'cancelled' => '❌',
    default     => '🔍'
};
@endphp
<div class="hsg-status-card">
    <div class="hsg-status-icon {{ $iconClass }}">{{ $iconEmoji }}</div>
    <div style="flex:1;min-width:0;">
        <div class="hsg-status-title">
            {{ $typeLabels[$accommodation->type] ?? $accommodation->type }}
        </div>
        <div class="hsg-status-sub">
            Durum: <span class="badge {{ $accommodation->statusBadge() }}">{{ $accommodation->statusLabel() }}</span>
            @if($accommodation->city)
                &nbsp;·&nbsp; {{ $accommodation->city }}
                @if($accommodation->postal_code) {{ $accommodation->postal_code }}@endif
            @endif
        </div>
    </div>
    @if($accommodation->move_in_date)
    <div style="text-align:right;flex-shrink:0;">
        <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:2px;">Taşınma Tarihi</div>
        <div style="font-size:var(--tx-base);font-weight:700;color:var(--u-text);">{{ $accommodation->move_in_date->format('d.m.Y') }}</div>
        @php $daysLeft = now()->diffInDays($accommodation->move_in_date, false); @endphp
        @if($daysLeft >= 0)
        <div style="font-size:var(--tx-xs);color:#0891b2;">{{ $daysLeft }} gün kaldı</div>
        @endif
    </div>
    @endif
</div>

{{-- Maliyet --}}
@if($accommodation->monthly_cost_eur)
@php
$eurTry = app(\App\Services\CurrencyRateService::class)->getRate('EUR', 'TRY');
$tryAmount = $eurTry ? round($accommodation->monthly_cost_eur * $eurTry) : null;
@endphp
<div class="hsg-cost-highlight">
    <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--u-muted);margin-bottom:8px;">Aylık Kira</div>
    <div class="hsg-cost-amount">€ {{ number_format($accommodation->monthly_cost_eur, 0, ',', '.') }}</div>
    <div class="hsg-cost-sub">
        @if($tryAmount)≈ ₺ {{ number_format($tryAmount, 0, ',', '.') }}&nbsp;·&nbsp;@endif
        {{ $accommodation->utilities_included ? 'Faturalar dahil' : 'Faturalar hariç' }}
    </div>
</div>
@endif

{{-- Detaylar --}}
<div class="hsg-grid">
    <div class="hsg-info-card">
        <h4>Adres Bilgileri</h4>
        @if($accommodation->address)
        <div class="hsg-field">
            <div class="hsg-field-label">Adres</div>
            <div class="hsg-field-value">{{ $accommodation->address }}</div>
        </div>
        @endif
        @if($accommodation->city)
        <div class="hsg-field">
            <div class="hsg-field-label">Şehir / Posta Kodu</div>
            <div class="hsg-field-value">{{ $accommodation->city }}{{ $accommodation->postal_code ? ' · ' . $accommodation->postal_code : '' }}</div>
        </div>
        @endif
        @if($accommodation->contract_end_date)
        <div class="hsg-field">
            <div class="hsg-field-label">Sözleşme Bitiş Tarihi</div>
            <div class="hsg-field-value">{{ $accommodation->contract_end_date->format('d.m.Y') }}</div>
        </div>
        @endif
        @if(!$accommodation->address && !$accommodation->city)
        <p style="font-size:var(--tx-sm);color:var(--u-muted);margin:0;">Adres bilgisi henüz girilmemiş.</p>
        @endif
    </div>

    <div class="hsg-info-card">
        <h4>Ev Sahibi / Yurt İletişim</h4>
        @if($accommodation->landlord_name)
        <div class="hsg-field">
            <div class="hsg-field-label">Ad Soyad</div>
            <div class="hsg-field-value">{{ $accommodation->landlord_name }}</div>
        </div>
        @endif
        @if($accommodation->landlord_phone)
        <div class="hsg-field">
            <div class="hsg-field-label">Telefon</div>
            <div class="hsg-field-value">
                <a href="tel:{{ $accommodation->landlord_phone }}" style="color:var(--u-brand);">{{ $accommodation->landlord_phone }}</a>
            </div>
        </div>
        @endif
        @if($accommodation->landlord_email)
        <div class="hsg-field">
            <div class="hsg-field-label">E-posta</div>
            <div class="hsg-field-value">
                <a href="mailto:{{ $accommodation->landlord_email }}" style="color:var(--u-brand);">{{ $accommodation->landlord_email }}</a>
            </div>
        </div>
        @endif
        @if(!$accommodation->landlord_name && !$accommodation->landlord_phone && !$accommodation->landlord_email)
        <p style="font-size:var(--tx-sm);color:var(--u-muted);margin:0;">İletişim bilgisi girilmemiş.</p>
        @endif
    </div>
</div>

@if($accommodation->notes)
<div class="hsg-info-card" style="margin-bottom:16px;">
    <h4>Danışman Notları</h4>
    <p style="font-size:var(--tx-sm);color:var(--u-text);margin:0;line-height:1.6;">{{ $accommodation->notes }}</p>
</div>
@endif

@else
{{-- Boş durum --}}
<div class="hsg-empty">
    <div class="hsg-empty-icon">🏠</div>
    <div class="hsg-empty-title">Konut Bilgisi Henüz Girilmedi</div>
    <div class="hsg-empty-sub">
        Danışmanınız konut durumunuzu sisteme girdikten sonra adres, kira ve ev sahibi bilgileri burada görüntülenecek.
    </div>
    <a href="{{ route('student.messages') }}" class="btn">Danışmana Mesaj Gönder</a>
</div>
@endif

{{-- Konut Türleri Rehberi --}}
<div class="hsg-guide-card">
    <h4>🏠 Almanya'da Konut Seçenekleri</h4>
    <div class="hsg-types-grid">
        <div class="hsg-type-card">
            <div class="hsg-type-card-icon">🏫</div>
            <div class="hsg-type-card-title">Yurt (Wohnheim)</div>
            <div class="hsg-type-card-desc">Üniversite yurtları, sosyal ortam ve uygun fiyat. Talep erken, başvurun erken.</div>
            <div class="hsg-type-card-cost">€ 200 – 400 / ay</div>
        </div>
        <div class="hsg-type-card">
            <div class="hsg-type-card-icon">🏢</div>
            <div class="hsg-type-card-title">Kiralık Daire (WG)</div>
            <div class="hsg-type-card-desc">Oda paylaşımı (Wohngemeinschaft). WG-Gesucht.de ve Immoscout24 en popüler platformlar.</div>
            <div class="hsg-type-card-cost">€ 350 – 700 / ay</div>
        </div>
        <div class="hsg-type-card">
            <div class="hsg-type-card-icon">👨‍👩‍👧</div>
            <div class="hsg-type-card-title">Aile Yanı</div>
            <div class="hsg-type-card-desc">Ev sahibi aileyle birlikte yaşama. Dil pratiği ve kültürel uyum için ideal.</div>
            <div class="hsg-type-card-cost">€ 400 – 600 / ay</div>
        </div>
    </div>
</div>

@endsection
