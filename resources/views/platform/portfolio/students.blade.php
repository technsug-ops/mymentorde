@extends('platform.layouts.app')

@section('title', 'Öğrenci Kotası — Platform')

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">Öğrenci Kotası</h1>
        <p class="plat-page-sub">Paket sınırları ve büyüme sinyali</p>
    </div>
</div>

<div class="plat-card" style="margin-bottom:18px;border-left:3px solid var(--plat-accent-2);">
    <div style="font-size:12px;color:var(--plat-muted);line-height:1.7;">
        <strong style="color:#fff;">Bu ekranda ne yok:</strong>
        kişisel veri (ad, e-posta, öğrenci no) ve operasyon detayı.
        <br>Veri sorumluluğu müşteride; servis sağlayıcı olarak bakmamız gereken tek şey
        <strong style="color:#fff;">kapasite ve paket uyumu</strong>.
    </div>
</div>

<div class="plat-grid plat-grid-4" style="margin-bottom:24px;">
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="alert-triangle" size="12" /> Limit Doldu</div>
        <div class="plat-kpi-value" style="{{ $atLimit > 0 ? 'color:#dc2626;' : '' }}">{{ $atLimit }}</div>
        <div class="plat-kpi-sub">firma · üst pakete geçmeli</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="trending-up" size="12" /> Sınıra Yakın</div>
        <div class="plat-kpi-value" style="{{ $nearLimit > 0 ? 'color:#f59e0b;' : '' }}">{{ $nearLimit }}</div>
        <div class="plat-kpi-sub">%{{ $threshold }} üzeri kullanım</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="graduation-cap" size="12" /> Toplam Öğrenci</div>
        <div class="plat-kpi-value">{{ number_format($totalStuds, 0, ',', '.') }}</div>
        <div class="plat-kpi-sub">tüm firmalar</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="users" size="12" /> Bekleyen Aday</div>
        <div class="plat-kpi-value">{{ number_format($totalLeads, 0, ',', '.') }}</div>
        <div class="plat-kpi-sub">henüz dönüşmemiş</div>
    </div>
</div>

<div class="plat-card">
    <h3 class="plat-card-title"><x-icon name="gauge" size="16" /> Kota Kullanımı</h3>
    @include('platform.portfolio._quota-table')
</div>

@endsection
