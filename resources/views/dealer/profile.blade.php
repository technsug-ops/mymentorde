@extends('dealer.layouts.app')

@section('title', 'Profilim')
@section('page_title', 'Profilim')

@push('head')
<style>
/* Hero */
.dprf-hero {
    background: linear-gradient(to right, #14532d 0%, #15803d 60%, #16a34a 100%);
    border-radius: 14px;
    padding: 26px 28px 22px;
    display: flex; align-items: center; gap: 20px; flex-wrap: wrap;
    position: relative; overflow: hidden; margin-bottom: 20px;
}
.dprf-hero::before {
    content:''; position:absolute; top:-40px; right:-40px;
    width:200px; height:200px; border-radius:50%;
    background:rgba(255,255,255,.05); pointer-events:none;
}
.dprf-avatar {
    width:76px; height:76px; border-radius:50%; flex-shrink:0;
    background:rgba(255,255,255,.15); border:3px solid rgba(255,255,255,.35);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:700; font-size:24px;
}
.dprf-info { flex:1; min-width:180px; }
.dprf-name  { font-size:20px; font-weight:800; color:#fff; margin:0 0 3px; }
.dprf-email { font-size:12px; color:rgba(255,255,255,.75); margin-bottom:10px; }
.dprf-chips { display:flex; gap:6px; flex-wrap:wrap; }
.dprf-chip {
    background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.25);
    border-radius:999px; padding:3px 12px; font-size:11px; color:#fff; font-weight:600;
}
.dprf-chip.active { background:rgba(134,239,172,.22); border-color:rgba(134,239,172,.45); }
.dprf-meta { display:flex; gap:18px; flex-wrap:wrap; margin-left:auto; }
.dprf-meta-item { text-align:center; min-width:60px; }
.dprf-meta-val   { font-size:14px; font-weight:700; color:#fff; line-height:1; margin-bottom:3px; }
.dprf-meta-label { font-size:10px; color:rgba(255,255,255,.65); text-transform:uppercase; letter-spacing:.04em; }
.dprf-meta-sep   { width:1px; background:rgba(255,255,255,.2); align-self:stretch; }

/* Form fields */
.dprf-field { margin-bottom:14px; }
.dprf-field:last-child { margin-bottom:0; }
.dprf-field label {
    display:block; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.04em;
    color:var(--muted,#64748b); margin-bottom:6px;
}
.dprf-field input,
.dprf-field select,
.dprf-field textarea {
    width:100%; box-sizing:border-box;
    border:1.5px solid var(--border,#e2e8f0); border-radius:8px;
    padding:10px 12px; font-size:13px; color:var(--text,#0f172a);
    background:var(--surface,#fff); font-family:inherit;
    transition:border-color .15s, box-shadow .15s;
}
.dprf-field input:focus,
.dprf-field select:focus,
.dprf-field textarea:focus {
    outline:none; border-color:var(--c-accent,#16a34a);
    box-shadow:0 0 0 3px rgba(22,163,74,.12);
}
.dprf-field input:disabled { background:var(--bg,#f8fafc); color:var(--muted,#64748b); }
.dprf-field textarea { min-height:100px; resize:vertical; }
.dprf-field .dprf-hint { font-size:11px; color:var(--muted,#64748b); margin-top:4px; }

/* Section title inside panel */
.dprf-section-title {
    font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
    color:var(--muted,#64748b); margin:0 0 16px; padding-bottom:10px;
    border-bottom:1px solid var(--border,#e2e8f0);
    display:flex; align-items:center; gap:7px;
}
</style>
@endpush

@section('content')
@php
    $initials = strtoupper(substr($displayName ?: ($dealer?->dealer_code ?? 'DR'), 0, 2));
    $isActive = (bool)($dealer?->is_active ?? false);
@endphp

{{-- Hero --}}
<div class="dprf-hero">
    <div class="dprf-avatar">{{ $initials }}</div>
    <div class="dprf-info">
        <div class="dprf-name">{{ $displayName ?: 'İsim belirtilmedi' }}</div>
        <div class="dprf-email">{{ auth()->user()?->email ?? '-' }}</div>
        <div class="dprf-chips">
            <span class="dprf-chip">{{ $dealerCode ?: '-' }}</span>
            @if($dealer?->dealer_type_code)
                <span class="dprf-chip">{{ $dealer->dealer_type_code }}</span>
            @endif
            <span class="dprf-chip {{ $isActive ? 'active' : '' }}">{{ $isActive ? '✓ Aktif' : 'Pasif' }}</span>
        </div>
    </div>
    @if($companyName || $region)
    <div class="dprf-meta">
        @if($companyName)
        <div class="dprf-meta-item">
            <div class="dprf-meta-val">{{ Str::limit($companyName, 12) }}</div>
            <div class="dprf-meta-label">Firma</div>
        </div>
        @endif
        @if($companyName && $region)<div class="dprf-meta-sep"></div>@endif
        @if($region)
        <div class="dprf-meta-item">
            <div class="dprf-meta-val">{{ Str::limit($region, 10) }}</div>
            <div class="dprf-meta-label">Bölge</div>
        </div>
        @endif
    </div>
    @endif
</div>

<form method="POST" action="{{ route('dealer.profile.update') }}">
@csrf

<div class="panel" style="margin-bottom:14px;">
    <div class="dprf-section-title">📞 İletişim Bilgileri</div>
    <div class="grid2" style="margin-bottom:0;">
        <div class="dprf-field">
            <label>Görünen Ad</label>
            <input name="display_name" value="{{ $displayName }}" placeholder="Ad Soyad">
        </div>
        <div class="dprf-field">
            <label>Firma / Kurum</label>
            <input name="company_name" value="{{ $companyName }}" placeholder="Şirket adı">
        </div>
        <div class="dprf-field">
            <label>Telefon</label>
            <input name="phone" value="{{ $phone }}" placeholder="+90 5xx xxx xx xx">
        </div>
        <div class="dprf-field">
            <label>WhatsApp</label>
            <input name="whatsapp" value="{{ $whatsapp }}" placeholder="+90 5xx xxx xx xx">
        </div>
        <div class="dprf-field">
            <label>Bölge / Şehir</label>
            <input name="region" value="{{ $region }}" placeholder="İstanbul, Ankara...">
        </div>
        <div class="dprf-field">
            <label>Çalışma Durumu</label>
            <select name="work_status">
                <option value=""           @selected($workStatus === '')>Seçiniz</option>
                <option value="individual"  @selected($workStatus === 'individual')>Bireysel</option>
                <option value="fulltime"    @selected($workStatus === 'fulltime')>Tam Zamanlı</option>
                <option value="parttime"    @selected($workStatus === 'parttime')>Yarı Zamanlı</option>
                <option value="institution" @selected($workStatus === 'institution')>Eğitim Kurumu</option>
            </select>
        </div>
        <div class="dprf-field">
            <label>Dealer Code</label>
            <input value="{{ $dealerCode }}" disabled>
            <div class="dprf-hint">Bu alan değiştirilemez.</div>
        </div>
    </div>
</div>

<div class="panel" style="margin-bottom:16px;">
    <div class="dprf-section-title">✍️ Kısa Tanıtım</div>
    <div class="dprf-field">
        <textarea name="bio" placeholder="Bayi/partner tanıtımı, çalışma bölgesi, hedef öğrenci profili...">{{ $bio }}</textarea>
        <div class="dprf-hint">Manager ve operasyon ekibinin profili hızlı anlamasına yardım eder.</div>
    </div>
</div>

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
    <button class="btn btn-primary" type="submit">Değişiklikleri Kaydet</button>
    <a class="btn alt" href="{{ route('dealer.referral-links') }}">Referans Linklerim</a>
    <a class="btn alt" href="{{ route('dealer.leads') }}">Yönlendirmelerim</a>
</div>
</form>

@include('dealer._partials.usage-guide', [
    'items' => [
        'Çalışma Durumu seçimi manager ekibine bayi tipini hızlıca aktarır.',
        'Telefon/WhatsApp bilgileri operasyon ekibi ile hızlı iletişim için kullanılır.',
        'Kısa tanıtım manager panelinde dealer kartında özet olarak gösterilir.',
    ]
])

@endsection
