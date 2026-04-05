@extends('manager.layouts.app')
@section('title','GDPR Uyum Paneli')
@section('page_title', 'GDPR Uyum Paneli')

@push('head')
<style>
.gdpr-kpi-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:16px; }
@media(max-width:900px){ .gdpr-kpi-strip { grid-template-columns:1fr 1fr; } }
.gdpr-kpi { background:#fff; border:1px solid #e2e8f0; border-top:3px solid #1e40af; border-radius:10px; padding:14px 16px; }
.gdpr-kpi-val   { font-size:28px; font-weight:900; line-height:1; margin-bottom:4px; }
.gdpr-kpi-label { font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }

.gdpr-policy-row { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid #f1f5f9; }
.gdpr-policy-row:last-child { border-bottom:none; }

.gdpr-stat-mini  { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; text-align:center; }
.gdpr-stat-mini-val { font-size:22px; font-weight:900; color:#0f172a; line-height:1; margin-bottom:3px; }
.gdpr-stat-mini-lbl { font-size:10px; color:#64748b; text-transform:uppercase; letter-spacing:.04em; }

.gdpr-link-btn { display:flex; align-items:center; gap:8px; padding:10px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; font-weight:600; color:#374151; text-decoration:none; transition:all .15s; background:#fff; }
.gdpr-link-btn:hover { background:#f8fafc; border-color:#cbd5e1; color:#0f172a; }
</style>
@endpush

@section('content')
@php
$totalConsent = max(1, $consentStats['total']);
$activeRate   = round($consentStats['active'] / $totalConsent * 100, 1);
@endphp

<div class="page-header">
    <div>
        <h1 style="margin:0">GDPR Uyum Paneli</h1>
        <div class="muted" style="font-size:var(--tx-xs);margin-top:2px;">Veri gizliliği ve kişisel veri uyum durumu</div>
    </div>
    <div style="display:flex;gap:8px;">
        <a class="btn alt" href="{{ url('/manager/requests') }}">Silme Talepleri</a>
        <a class="btn alt" href="{{ url('/config') }}#gdpr">⚙ Ayarlar</a>
    </div>
</div>

{{-- KPI Strip --}}
<div class="gdpr-kpi-strip">
    <div class="gdpr-kpi" style="border-top-color:#d97706;">
        <div class="gdpr-kpi-val" style="color:#d97706;">{{ $pendingErasures }}</div>
        <div class="gdpr-kpi-label">Bekleyen Silme Talebi</div>
    </div>
    <div class="gdpr-kpi" style="border-top-color:#16a34a;">
        <div class="gdpr-kpi-val" style="color:#16a34a;">{{ $completedErasures }}</div>
        <div class="gdpr-kpi-label">Tamamlanan Silme</div>
    </div>
    <div class="gdpr-kpi">
        <div class="gdpr-kpi-val" style="color:#1e40af;">{{ $recentExports }}</div>
        <div class="gdpr-kpi-label">Son 30 Gün Export</div>
    </div>
    <div class="gdpr-kpi" style="border-top-color:#dc2626;">
        <div class="gdpr-kpi-val" style="color:#dc2626;">{{ $piiAccessLogs }}</div>
        <div class="gdpr-kpi-label">Son 7 Gün PII Erişim</div>
    </div>
</div>

{{-- Uyarı Bandı --}}
@if($pendingErasures > 0)
<div class="card" style="margin-bottom:16px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;border-left:3px solid #d97706;background:#fffbeb;">
    <div>
        <div style="font-size:var(--tx-sm);font-weight:700;color:#92400e;">⚠️ {{ $pendingErasures }} bekleyen silme talebi var</div>
        <div style="font-size:var(--tx-xs);color:#78350f;margin-top:2px;">GDPR kapsamındaki silme talepleri 30 gün içinde işlenmelidir.</div>
    </div>
    <a href="{{ url('/manager/requests') }}" class="btn warn" style="white-space:nowrap;flex-shrink:0;">İncele →</a>
</div>
@endif

<div class="grid2" style="margin-bottom:16px;">
    {{-- Rıza Kayıtları --}}
    <div class="card">
        <h3 style="margin:0 0 14px;">Rıza Kayıtları</h3>

        <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px;">
            <span style="font-size:var(--tx-xs);color:#374151;">Aktif rıza oranı</span>
            <span style="font-size:var(--tx-lg);font-weight:900;color:#16a34a;">%{{ $activeRate }}</span>
        </div>
        <div style="height:8px;background:#f1f5f9;border-radius:999px;overflow:hidden;margin-bottom:14px;">
            <div style="width:{{ $activeRate }}%;height:100%;background:#16a34a;border-radius:999px;"></div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;">
            <div class="gdpr-stat-mini">
                <div class="gdpr-stat-mini-val">{{ $consentStats['total'] }}</div>
                <div class="gdpr-stat-mini-lbl">Toplam</div>
            </div>
            <div class="gdpr-stat-mini" style="background:#f0fdf4;border-color:#bbf7d0;">
                <div class="gdpr-stat-mini-val" style="color:#16a34a;">{{ $consentStats['active'] }}</div>
                <div class="gdpr-stat-mini-lbl">Aktif</div>
            </div>
            <div class="gdpr-stat-mini" style="background:#fef2f2;border-color:#fecaca;">
                <div class="gdpr-stat-mini-val" style="color:#dc2626;">{{ $consentStats['revoked'] }}</div>
                <div class="gdpr-stat-mini-lbl">Geri Alınan</div>
            </div>
        </div>
    </div>

    {{-- Hızlı Erişim --}}
    <div class="card">
        <h3 style="margin:0 0 12px;">Hızlı Erişim</h3>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <a class="gdpr-link-btn" href="{{ url('/manager/requests') }}">
                🗑️ Silme Talepleri
                @if($pendingErasures > 0)
                <span class="badge warn" style="margin-left:auto;font-size:var(--tx-xs);">{{ $pendingErasures }}</span>
                @endif
            </a>
            <a class="gdpr-link-btn" href="{{ url('/config') }}#gdpr">
                ⚙️ Veri Saklama Ayarları
            </a>
            <a class="gdpr-link-btn" href="{{ url('/manager/notification-stats') }}">
                📊 Bildirim İstatistikleri
            </a>
        </div>
        <div style="margin-top:12px;padding:10px 12px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;">
            <div style="font-size:var(--tx-xs);font-weight:700;color:#0369a1;margin-bottom:3px;">📋 GDPR / DSGVO Uyum Notu</div>
            <div style="font-size:var(--tx-xs);color:#0c4a6e;line-height:1.6;">
                Veri saklama politikaları her gece 03:00'te otomatik çalışır.
                Silme talepleri 30 gün içinde işlenmelidir.
            </div>
        </div>
    </div>
</div>

{{-- Veri Saklama Politikaları --}}
<div class="card">
    <h3 style="margin:0 0 12px;">Aktif Veri Saklama Politikaları</h3>
    @if($retentionPolicies->isEmpty())
    <div style="padding:24px;text-align:center;">
        <div class="muted" style="font-size:var(--tx-sm);margin-bottom:6px;">Politika tanımlanmamış.</div>
        <a href="{{ url('/config') }}#gdpr" class="btn alt">Politika Ekle →</a>
    </div>
    @else
    <div class="list">
        @foreach($retentionPolicies as $policy)
        <div class="gdpr-policy-row">
            <span class="badge info" style="flex-shrink:0;font-size:var(--tx-xs);">{{ $policy->entity_type }}</span>
            <span style="font-size:var(--tx-xs);color:#374151;flex:1;">{{ $policy->action }}</span>
            <span style="font-size:var(--tx-xs);color:#64748b;white-space:nowrap;">{{ $policy->retention_days }} gün</span>
            <span class="badge {{ $policy->is_active ? 'ok' : 'danger' }}" style="flex-shrink:0;font-size:var(--tx-xs);">
                {{ $policy->is_active ? 'Aktif' : 'Pasif' }}
            </span>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
