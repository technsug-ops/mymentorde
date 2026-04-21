@extends('manager.layouts.app')

@section('title', 'Manager – Eğitim Danışmanı Yönetimi')
@section('page_title', 'Eğitim Danışmanı Yönetimi')

@push('head')
<style>
.mgr-kpi-strip { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-bottom:12px; }
@media(max-width:700px){ .mgr-kpi-strip { grid-template-columns:1fr; } }
.mgr-kpi { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-top:3px solid #1e40af; border-radius:10px; padding:12px 14px; }
.mgr-kpi-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.mgr-kpi-val   { font-size:22px; font-weight:800; color:var(--text,#0f172a); line-height:1; }
.mgr-table { width:100%; border-collapse:collapse; font-size:12px; }
.mgr-table thead tr { background:var(--bg,#f8fafc); }
.mgr-table th { padding:7px 10px; text-align:left; font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.mgr-table th.center { text-align:center; }
.mgr-table tbody tr { border-bottom:1px solid var(--border,#e2e8f0); }
.mgr-table tbody tr:hover { background:rgba(30,64,175,.03); }
.mgr-table td { padding:8px 10px; vertical-align:middle; }
.mgr-table td.center { text-align:center; }
.mgr-detail-btn { display:inline-block; padding:4px 10px; font-size:11px; font-weight:600; color:#1e40af; border:1px solid rgba(30,64,175,.3); border-radius:6px; background:rgba(30,64,175,.05); text-decoration:none; white-space:nowrap; }
</style>
@endpush

@section('content')

@include('partials.manager-hero', [
    'label' => 'Danışman Kadrosu',
    'title' => 'Eğitim Danışmanları',
    'sub'   => 'Danışman portföyü, öğrenci yükleri ve kapasite dağılımı. Yük dengesini ve atanamayan lead riskini tek bakışta gör.',
    'icon'  => '🧑\u{200D}🏫',
    'bg'    => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1400&q=80',
    'tone'  => 'indigo',
    'stats' => [
        ['icon' => '👥', 'text' => ($kpis['total'] ?? 0) . ' danışman'],
        ['icon' => '🎓', 'text' => ($kpis['total_students'] ?? 0) . ' öğrenci'],
        ['icon' => '⚠️', 'text' => ($kpis['over_capacity'] ?? 0) . ' kapasitede'],
    ],
])

{{-- KPI Strip --}}
<div class="mgr-kpi-strip">
    <div class="mgr-kpi">
        <div class="mgr-kpi-label">Toplam Eğitim Danışmanı</div>
        <div class="mgr-kpi-val">{{ $kpis['total'] }}</div>
    </div>
    <div class="mgr-kpi">
        <div class="mgr-kpi-label">Toplam Aktif Öğrenci</div>
        <div class="mgr-kpi-val">{{ $kpis['total_students'] }}</div>
    </div>
    <div class="mgr-kpi" style="border-top-color:{{ $kpis['over_capacity'] > 0 ? '#dc2626' : '#1e40af' }};">
        <div class="mgr-kpi-label">Kapasitede (≥20)</div>
        <div class="mgr-kpi-val" style="{{ $kpis['over_capacity'] > 0 ? 'color:#dc2626;' : '' }}">{{ $kpis['over_capacity'] }}</div>
    </div>
</div>

{{-- Eğitim Danışmanı Tablosu --}}
<section class="panel" style="padding:0;overflow:hidden;">
    <div style="padding:14px 16px;border-bottom:1px solid var(--border,#e2e8f0);">
        <span style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">
            {{ count($seniors) }} Eğitim Danışmanı
        </span>
    </div>
    <div style="overflow-x:auto;">
        <table class="mgr-table">
            <thead>
                <tr>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th class="center">Aktif Öğrenci</th>
                    <th class="center">Arşiv</th>
                    <th class="center">Bekleyen Aday Öğrenci</th>
                    <th class="center">Kapasite</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($seniors as $s)
                    @php
                        $capCls   = $s['active'] >= 20 ? 'danger' : ($s['active'] >= 15 ? 'warn' : 'ok');
                        $capLabel = $s['active'] >= 20 ? 'Dolu' : ($s['active'] >= 15 ? 'Yoğun' : 'Uygun');
                    @endphp
                    <tr>
                        <td style="font-weight:600;color:var(--text,#0f172a);">{{ $s['name'] }}</td>
                        <td style="color:var(--muted,#64748b);">{{ $s['email'] }}</td>
                        <td class="center">
                            <span style="font-size:var(--tx-base);font-weight:800;color:var(--text,#0f172a);">{{ $s['active'] }}</span>
                        </td>
                        <td class="center" style="color:var(--muted,#64748b);">{{ $s['archived'] }}</td>
                        <td class="center">
                            @if($s['guest_count'] > 0)
                                <span class="badge warn">{{ $s['guest_count'] }}</span>
                            @else
                                <span style="color:var(--muted,#64748b);">0</span>
                            @endif
                        </td>
                        <td class="center">
                            <span class="badge {{ $capCls }}">{{ $capLabel }}</span>
                        </td>
                        <td>
                            <a class="mgr-detail-btn" href="/manager/seniors/{{ urlencode($s['email']) }}">Detay →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="padding:28px;text-align:center;color:var(--muted,#64748b);">Eğitim Danışmanı kaydı bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@endsection
