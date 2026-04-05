@extends('manager.layouts.app')

@section('title', 'Manager – Bayi Yönetimi')
@section('page_title', 'Bayi Yönetimi')

@push('head')
<style>
.mgr-kpi-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:12px; }
@media(max-width:900px){ .mgr-kpi-strip { grid-template-columns:1fr 1fr; } }
@media(max-width:500px){ .mgr-kpi-strip { grid-template-columns:1fr; } }
.mgr-kpi { background:var(--surface,#fff); border:1px solid var(--border,#e2e8f0); border-top:3px solid #1e40af; border-radius:10px; padding:12px 14px; }
.mgr-kpi-label { font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.mgr-kpi-val   { font-size:22px; font-weight:800; color:var(--text,#0f172a); line-height:1; }
.mgr-table { width:100%; border-collapse:collapse; font-size:12px; }
.mgr-table thead tr { background:var(--bg,#f8fafc); }
.mgr-table th { padding:7px 10px; text-align:left; font-size:10px; font-weight:700; color:var(--muted,#64748b); text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.mgr-table th.right { text-align:right; }
.mgr-table th.center { text-align:center; }
.mgr-table tbody tr { border-bottom:1px solid var(--border,#e2e8f0); }
.mgr-table tbody tr:hover { background:rgba(30,64,175,.03); }
.mgr-table td { padding:8px 10px; vertical-align:middle; }
.mgr-table td.right { text-align:right; }
.mgr-table td.center { text-align:center; }
.mgr-detail-btn { display:inline-block; padding:4px 10px; font-size:11px; font-weight:600; color:#1e40af; border:1px solid rgba(30,64,175,.3); border-radius:6px; background:rgba(30,64,175,.05); text-decoration:none; white-space:nowrap; }
/* Liderlik sıra rozeti */
.rank-badge { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:50%; font-size:11px; font-weight:800; flex-shrink:0; }
.rank-1 { background:rgba(234,179,8,.15); color:#92400e; border:1px solid rgba(234,179,8,.4); }
.rank-2 { background:rgba(30,64,175,.1); color:#1e40af; border:1px solid rgba(30,64,175,.25); }
.rank-n { background:var(--bg,#f8fafc); color:var(--muted,#64748b); border:1px solid var(--border,#e2e8f0); }
</style>
@endpush

@section('content')

{{-- KPI Strip --}}
<div class="mgr-kpi-strip">
    <div class="mgr-kpi">
        <div class="mgr-kpi-label">Toplam Bayi</div>
        <div class="mgr-kpi-val">{{ $kpis['total'] }}</div>
    </div>
    <div class="mgr-kpi">
        <div class="mgr-kpi-label">Aktif</div>
        <div class="mgr-kpi-val" style="color:#15803d;">{{ $kpis['active'] }}</div>
    </div>
    <div class="mgr-kpi">
        <div class="mgr-kpi-label">Toplam Kazanç (EUR)</div>
        <div class="mgr-kpi-val">{{ number_format($kpis['earned'], 0, ',', '.') }}</div>
    </div>
    <div class="mgr-kpi" style="border-top-color:{{ $kpis['pending'] > 0 ? '#d97706' : '#1e40af' }};">
        <div class="mgr-kpi-label">Bekleyen (EUR)</div>
        <div class="mgr-kpi-val" style="{{ $kpis['pending'] > 0 ? 'color:#b45309;' : '' }}">{{ number_format($kpis['pending'], 0, ',', '.') }}</div>
    </div>
</div>

{{-- Liderlik Tabloları --}}
@php
    $topByLeads     = collect($enriched)->filter(fn($d) => ($d['leads'] ?? 0) > 0)->sortByDesc('leads')->take(5)->values();
    $topByConverted = collect($enriched)->filter(fn($d) => ($d['converted'] ?? 0) > 0)->sortByDesc('converted')->take(5)->values();
@endphp
<div class="grid2" style="margin-bottom:12px;">
    <section class="panel">
        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px;">Bu Ay En Fazla Lead (Top 5)</div>
        <div class="list">
            @forelse($topByLeads as $i => $d)
                <div class="item" style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="rank-badge {{ $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : 'rank-n') }}">{{ $i + 1 }}</span>
                        <div>
                            <div style="font-weight:600;color:var(--text,#0f172a);font-size:var(--tx-sm);">{{ $d['name'] }}</div>
                            <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);">{{ $d['code'] }}</div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:var(--tx-lg);font-weight:800;color:var(--text,#0f172a);line-height:1;">{{ $d['leads'] }}</div>
                        <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);">lead</div>
                    </div>
                </div>
            @empty
                <div class="item" style="color:var(--muted,#64748b);">Lead kaydı bulunamadı.</div>
            @endforelse
        </div>
    </section>

    <section class="panel">
        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px;">En Fazla Dönüşüm (Top 5)</div>
        <div class="list">
            @forelse($topByConverted as $i => $d)
                <div class="item" style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="rank-badge {{ $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : 'rank-n') }}">{{ $i + 1 }}</span>
                        <div>
                            <div style="font-weight:600;color:var(--text,#0f172a);font-size:var(--tx-sm);">{{ $d['name'] }}</div>
                            <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);">
                                {{ $d['leads'] > 0 ? round($d['converted'] / $d['leads'] * 100) : 0 }}% oran
                            </div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:var(--tx-lg);font-weight:800;color:var(--text,#0f172a);line-height:1;">{{ $d['converted'] }}</div>
                        <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);">dönüşüm</div>
                    </div>
                </div>
            @empty
                <div class="item" style="color:var(--muted,#64748b);">Dönüşüm kaydı bulunamadı.</div>
            @endforelse
        </div>
    </section>
</div>

{{-- Bayi Tablosu --}}
<section class="panel" style="padding:0;overflow:hidden;">
    <div style="padding:14px 16px;border-bottom:1px solid var(--border,#e2e8f0);">
        <span style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">
            {{ count($enriched) }} Bayi
        </span>
    </div>
    <div style="overflow-x:auto;">
        <table class="mgr-table">
            <thead>
                <tr>
                    <th>Bayi Adı</th>
                    <th>Kod</th>
                    <th>Tür</th>
                    <th class="center">Durum</th>
                    <th class="center">Öğrenci</th>
                    <th class="center">Lead</th>
                    <th class="center">Dönüşen</th>
                    <th class="right">Kazanılan (EUR)</th>
                    <th class="right">Bekleyen (EUR)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($enriched as $d)
                    <tr>
                        <td style="font-weight:600;color:var(--text,#0f172a);">{{ $d['name'] }}</td>
                        <td style="color:var(--muted,#64748b);font-family:monospace;">{{ $d['code'] }}</td>
                        <td style="color:var(--muted,#64748b);">{{ $d['type'] ?: '–' }}</td>
                        <td class="center">
                            <span class="badge {{ $d['is_active'] ? 'ok' : 'pending' }}">
                                {{ $d['is_active'] ? 'Aktif' : 'Pasif' }}
                            </span>
                        </td>
                        <td class="center" style="font-weight:600;">{{ $d['students'] }}</td>
                        <td class="center">{{ $d['leads'] }}</td>
                        <td class="center">
                            @if($d['leads'] > 0)
                                {{ $d['converted'] }}
                                <span style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-left:2px;">({{ round($d['converted'] / $d['leads'] * 100) }}%)</span>
                            @else
                                <span style="color:var(--muted,#64748b);">0</span>
                            @endif
                        </td>
                        <td class="right" style="font-weight:500;">
                            {{ $d['earned'] > 0 ? number_format($d['earned'], 2, ',', '.') : '–' }}
                        </td>
                        <td class="right">
                            @if($d['pending'] > 0)
                                <span style="color:#b45309;font-weight:600;">{{ number_format($d['pending'], 2, ',', '.') }}</span>
                            @else
                                <span style="color:var(--muted,#64748b);">–</span>
                            @endif
                        </td>
                        <td>
                            <a class="mgr-detail-btn" href="/manager/dealers/{{ $d['code'] }}">Detay →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="padding:28px;text-align:center;color:var(--muted,#64748b);">Bayi kaydı bulunamadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@endsection
