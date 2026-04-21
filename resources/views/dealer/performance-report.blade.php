@extends('dealer.layouts.app')

@section('title', 'Performans Raporu')
@section('page_title', 'Performans Raporu')
@section('page_subtitle', 'Aylık lead, dönüşüm ve kazanç trendi')

@push('head')
<style>
/* KPI */
.perf-kpi-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:900px){ .perf-kpi-strip { grid-template-columns:1fr 1fr; } }

.perf-kpi { background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-top:3px solid var(--border);border-radius:12px;padding:16px 18px; }
.perf-kpi.leads    { border-top-color:#3b82f6; }
.perf-kpi.conv     { border-top-color:#16a34a; }
.perf-kpi.rate     { border-top-color:#0891b2; }
.perf-kpi.earned   { border-top-color:#d97706; }
.perf-kpi-label    { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted,#64748b);margin-bottom:6px; }
.perf-kpi-val      { font-size:26px;font-weight:900;color:var(--text,#0f172a);line-height:1; }
.perf-kpi-sub      { font-size:10px;color:var(--muted,#64748b);margin-top:3px; }

/* Period selector */
.perf-header {
    display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;
    gap:10px;margin-bottom:16px;
}
.perf-period-form { display:flex;align-items:center;gap:8px; }
.perf-period-label { font-size:12px;color:var(--muted,#64748b);font-weight:600; }
.perf-period-select {
    border:1.5px solid var(--border,#e2e8f0);border-radius:8px;padding:7px 12px;
    font-size:13px;color:var(--text,#0f172a);background:var(--surface,#fff);cursor:pointer;
}
.perf-period-select:focus { outline:none;border-color:#16a34a; }

/* Table card */
.perf-table-card { background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:12px;overflow:hidden; }
.perf-table { width:100%;border-collapse:collapse;font-size:13px; }
.perf-table thead tr { background:var(--bg,#f8fafc);border-bottom:2px solid var(--border,#e2e8f0); }
.perf-table th { padding:11px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted,#64748b); }
.perf-table th.center { text-align:center; }
.perf-table th.right  { text-align:right; }
.perf-table tbody tr { border-bottom:1px solid var(--border,#e2e8f0);transition:background .12s; }
.perf-table tbody tr:last-child { border-bottom:none; }
.perf-table tbody tr:hover { background:var(--bg,#f8fafc); }
.perf-table td { padding:12px 16px;vertical-align:middle; }
.perf-table td.center { text-align:center; }
.perf-table td.right  { text-align:right; }

.perf-month  { font-size:13px;font-weight:700; }
.perf-num    { font-size:14px;font-weight:600; }
.perf-earned { font-size:13px;font-weight:700;color:#16a34a; }

.perf-badge { display:inline-block;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700; }
.perf-badge.ok   { background:rgba(22,163,74,.12);color:#15803d; }
.perf-badge.info { background:rgba(8,145,178,.12);color:#0e7490; }
.perf-badge.muted{ background:var(--bg,#f1f5f9);color:var(--muted,#64748b); }

/* Mini spark bars */
.perf-spark { display:flex;flex-direction:column;gap:3px;min-width:100px; }
.perf-spark-row { display:flex;align-items:center;gap:5px; }
.perf-spark-label { font-size:10px;color:var(--muted,#64748b);width:30px;flex-shrink:0; }
.perf-spark-track { flex:1;height:5px;background:var(--border,#e2e8f0);border-radius:999px;overflow:hidden; }
.perf-spark-fill  { height:100%;border-radius:999px; }

/* Empty */
.perf-empty { padding:48px 20px;text-align:center;color:var(--muted,#64748b);font-size:13px; }

/* Guide */
.perf-guide { background:var(--bg,#f1f5f9);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:16px 20px;margin-top:16px; }
.perf-guide-title { font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted,#64748b);margin-bottom:10px; }
.perf-guide ul { margin:0;padding-left:18px; }
.perf-guide li { font-size:13px;color:var(--muted,#64748b);margin-bottom:6px; }
</style>
@endpush

@section('content')

@php
    $ds             = $dealerStats ?? [];
    $totalLeads     = $trend->sum('leads');
    $totalConverted = $trend->sum('converted');
    $totalEarned    = $trend->sum('earned');
    $avgConvRate    = $totalLeads > 0 ? round($totalConverted / $totalLeads * 100, 1) : 0;
    $maxLeads       = max(1, $trend->max('leads'));
    $maxEarned      = max(1, $trend->max('earned'));
@endphp

@include('partials.manager-hero', [
    'label' => 'Performans Analitik',
    'title' => 'Performans Raporu',
    'sub'   => 'Aylık lead/dönüşüm/kazanç trendleri ve toplam istatistiklerin. Hangi ay pik yaptı, nerede yavaşladı görebilirsin.',
    'icon'  => '🏆',
    'bg'    => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1400&q=80',
    'tone'  => 'teal',
    'stats' => [
        ['icon' => '📥', 'text' => ($ds['guest_total'] ?? 0) . ' toplam lead'],
        ['icon' => '✅', 'text' => ($ds['converted_total'] ?? 0) . ' dönüşen'],
        ['icon' => '📈', 'text' => '%' . ($ds['conversion_rate'] ?? 0) . ' dönüşüm'],
        ['icon' => '💶', 'text' => '€' . number_format($totalEarned, 0, ',', '.') . ' kazanç'],
    ],
])

{{-- KPI --}}
<div class="perf-kpi-strip">
    <div class="perf-kpi leads">
        <div class="perf-kpi-label">Toplam Yönlendirme</div>
        <div class="perf-kpi-val">{{ $ds['guest_total'] ?? 0 }}</div>
        <div class="perf-kpi-sub">tüm zamanlar</div>
    </div>
    <div class="perf-kpi conv">
        <div class="perf-kpi-label">Öğrenciye Dönüşen</div>
        <div class="perf-kpi-val">{{ $ds['converted_total'] ?? 0 }}</div>
    </div>
    <div class="perf-kpi rate">
        <div class="perf-kpi-label">Dönüşüm Oranı</div>
        <div class="perf-kpi-val">%{{ $ds['conversion_rate'] ?? 0 }}</div>
    </div>
    <div class="perf-kpi earned">
        <div class="perf-kpi-label">Toplam Kazanç</div>
        <div class="perf-kpi-val" style="font-size:var(--tx-xl);">{{ number_format($totalEarned, 0, ',', '.') }} €</div>
    </div>
</div>

{{-- Header: dönem + export --}}
<div class="perf-header">
    <div style="font-size:var(--tx-sm);font-weight:700;">📈 Aylık Trend</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <form class="perf-period-form" method="GET">
            <span class="perf-period-label">Dönem:</span>
            <select class="perf-period-select" name="months" onchange="this.form.submit()">
                @foreach([3, 6, 12] as $mo)
                <option value="{{ $mo }}" @selected($mo === $months)>Son {{ $mo }} Ay</option>
                @endforeach
            </select>
        </form>
        <a href="/dealer/performance/export?months={{ $months }}" class="btn"
           style="font-size:var(--tx-xs);padding:7px 14px;">⬇ CSV İndir</a>
    </div>
</div>

{{-- Tablo --}}
<div class="perf-table-card">
    <div style="overflow-x:auto;">
        <table class="perf-table">
            <thead>
                <tr>
                    <th>Ay</th>
                    <th class="center">Lead</th>
                    <th class="center">Dönüşüm</th>
                    <th class="center">Oran</th>
                    <th class="right">Kazanç (EUR)</th>
                    <th style="min-width:130px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($trend as $row)
                <tr>
                    <td><span class="perf-month">{{ $row['label'] }}</span></td>
                    <td class="center"><span class="perf-num">{{ $row['leads'] }}</span></td>
                    <td class="center"><span class="perf-num">{{ $row['converted'] }}</span></td>
                    <td class="center">
                        <span class="perf-badge {{ $row['conv_rate'] >= 30 ? 'ok' : ($row['conv_rate'] >= 10 ? 'info' : 'muted') }}">
                            %{{ $row['conv_rate'] }}
                        </span>
                    </td>
                    <td class="right"><span class="perf-earned">{{ number_format($row['earned'], 2, ',', '.') }}</span></td>
                    <td>
                        <div class="perf-spark">
                            <div class="perf-spark-row">
                                <span class="perf-spark-label">Lead</span>
                                <div class="perf-spark-track">
                                    <div class="perf-spark-fill" style="background:#3b82f6;width:{{ round($row['leads']/$maxLeads*100) }}%;"></div>
                                </div>
                            </div>
                            <div class="perf-spark-row">
                                <span class="perf-spark-label">Kaz.</span>
                                <div class="perf-spark-track">
                                    <div class="perf-spark-fill" style="background:#16a34a;width:{{ round($row['earned']/$maxEarned*100) }}%;"></div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="perf-empty">Seçilen dönemde veri bulunamadı.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="perf-guide">
    <div class="perf-guide-title">💡 Rapor Hakkında</div>
    <ul>
        <li>Dönem seçici üstten son 3 / 6 / 12 ay arasında geçiş sağlar.</li>
        <li>CSV İndir ile tüm dönem verisini dışa aktarabilirsiniz.</li>
        <li>Oran renk kodu: <strong style="color:#15803d;">yeşil</strong> %30+, <strong style="color:#0e7490;">mavi</strong> %10+, gri altı.</li>
    </ul>
</div>

@endsection
