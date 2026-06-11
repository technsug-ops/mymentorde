@extends('platform.layouts.app')

@section('title', 'MRR Trendi — MentorDE Platform')

@push('styles')
<style>
    .mrr-filterbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; background:var(--plat-panel); border:1px solid var(--plat-border); border-radius:12px; padding:14px 18px; margin-bottom:20px; }
    .mrr-filterbar form { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin:0; }
    .mrr-filterbar label { font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; color:var(--plat-muted); margin-right:4px; }
    .mrr-filterbar select { background:var(--plat-bg); border:1px solid var(--plat-border); color:var(--plat-text); padding:7px 10px; border-radius:7px; font-size:13px; font-family:inherit; min-width:120px; }

    .mrr-kpi-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-bottom:24px; }
    @media (max-width: 1100px) { .mrr-kpi-grid { grid-template-columns:repeat(2, 1fr); } }
    @media (max-width: 720px)  { .mrr-kpi-grid { grid-template-columns:1fr; } }

    .mrr-delta-up   { color:var(--plat-ok); font-weight:800; }
    .mrr-delta-down { color:var(--plat-danger); font-weight:800; }
    .mrr-delta-flat { color:var(--plat-muted); font-weight:600; }

    /* SVG line chart container */
    .mrr-chart-wrap { background:var(--plat-panel-2); border:1px solid var(--plat-border); border-radius:10px; padding:18px 16px; }
    .mrr-chart-svg  { width:100%; height:260px; display:block; }

    /* Stacked bar (tier) */
    .mrr-stack { display:flex; height:36px; background:var(--plat-panel-2); border-radius:8px; overflow:hidden; margin:8px 0 14px; }
    .mrr-stack-seg { height:100%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; color:#fff; }
    .mrr-stack-seg.trial   { background:linear-gradient(135deg, #3b6db8, #60a5fa); }
    .mrr-stack-seg.basic   { background:linear-gradient(135deg, #22a55e, #4ade80); }
    .mrr-stack-seg.gold    { background:linear-gradient(135deg, #d97706, #fbbf24); }
    .mrr-stack-seg.premium { background:linear-gradient(135deg, #7e58bf, #b395e6); }

    .mrr-tier-grid { display:grid; grid-template-columns:120px 1fr 100px 110px; gap:12px; align-items:center; padding:8px 0; border-bottom:1px dashed var(--plat-border); font-size:12px; }
    .mrr-tier-grid:last-child { border-bottom:0; }

    .mrr-export-btn { background:rgba(74,222,128,.10); border:1px solid rgba(74,222,128,.30); color:var(--plat-ok); padding:7px 12px; border-radius:7px; font-size:12px; font-weight:700; display:inline-flex; align-items:center; gap:6px; text-decoration:none; }
    .mrr-export-btn:hover { background:rgba(74,222,128,.20); color:#fff; border-color:var(--plat-ok); }

    .mrr-trend-table tr td { padding:9px 12px; font-size:12px; }
    .mrr-trend-table tr td.num { text-align:right; font-weight:700; color:#fff; }
</style>
@endpush

@section('content')

<div class="plat-page-header">
    <div>
        <h1 class="plat-page-title">MRR Trendi</h1>
        <p class="plat-page-sub">Aylık tekrarlayan gelir, ARR projeksiyonu, ARPU & LTV</p>
    </div>
    <a href="{{ route('platform.mrr-trend.export', request()->only('range')) }}" class="mrr-export-btn">
        <x-icon name="download" size="14" /> CSV İndir
    </a>
</div>

{{-- FILTER BAR --}}
<div class="mrr-filterbar">
    <x-icon name="filter" size="14" />
    <form method="GET" action="{{ route('platform.mrr-trend') }}">
        <label for="range">Dönem</label>
        <select name="range" id="range" onchange="this.form.submit()">
            <option value="6m"  {{ $range === '6m'  ? 'selected' : '' }}>Son 6 ay</option>
            <option value="12m" {{ $range === '12m' ? 'selected' : '' }}>Son 12 ay</option>
            <option value="24m" {{ $range === '24m' ? 'selected' : '' }}>Son 24 ay</option>
        </select>
        <span style="margin-left:14px;font-size:12px;color:var(--plat-muted);">
            {{ $months }} ay · {{ $activeCompanies }} aktif şirket
        </span>
    </form>
</div>

{{-- KPI GRID (3 main + 2 metric) --}}
<div class="mrr-kpi-grid">
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="dollar-sign" size="12" /> Şu Anki MRR</div>
        <div class="plat-kpi-value">€{{ number_format($currentMrr, 0, ',', '.') }}</div>
        <div class="plat-kpi-sub">
            @if($mrrDelta > 0)
                <span class="mrr-delta-up"><x-icon name="trending-up" size="11" /> +€{{ number_format($mrrDelta, 0, ',', '.') }} ({{ $mrrDeltaPct }}%)</span>
            @elseif($mrrDelta < 0)
                <span class="mrr-delta-down">▼ €{{ number_format(abs($mrrDelta), 0, ',', '.') }} ({{ $mrrDeltaPct }}%)</span>
            @else
                <span class="mrr-delta-flat">— değişim yok</span>
            @endif
            <span style="color:var(--plat-muted);margin-left:6px;">(geçen aya göre)</span>
        </div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="trending-up" size="12" /> ARR Projeksiyonu</div>
        <div class="plat-kpi-value">€{{ number_format($arrProjection, 0, ',', '.') }}</div>
        <div class="plat-kpi-sub">MRR × 12 — yıllık tekrarlayan</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="target" size="12" /> ARPU</div>
        <div class="plat-kpi-value">€{{ number_format($arpu, 2, ',', '.') }}</div>
        <div class="plat-kpi-sub">aktif şirket başına aylık gelir</div>
    </div>
</div>

{{-- LTV + Churn metric (2 sutun) --}}
<div class="plat-grid plat-grid-2" style="margin-bottom:24px;">
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="sparkles" size="12" /> LTV Tahmini</div>
        <div class="plat-kpi-value">€{{ number_format($ltv, 2, ',', '.') }}</div>
        <div class="plat-kpi-sub">ARPU × {{ $avgSubMonths }} ay (ortalama abonelik)</div>
    </div>
    <div class="plat-kpi">
        <div class="plat-kpi-label"><x-icon name="percent" size="12" /> Churn Rate</div>
        <div class="plat-kpi-value">{{ number_format($churnRatePct, 2, ',', '.') }}%</div>
        <div class="plat-kpi-sub">
            son {{ $months }} ay toplam churn: <strong style="color:#fff;">{{ $totalChurnAll }}</strong>
        </div>
    </div>
</div>

{{-- 12 AY MRR TREND — SVG LINE CHART --}}
<div class="plat-card" style="margin-bottom:24px;">
    <h3 class="plat-card-title"><x-icon name="trending-up" size="16" /> MRR Trend — Son {{ $months }} Ay</h3>

    @php
        $w = 800; $h = 240; $pad = 28;
        $count = count($trend);
        $stepX = $count > 1 ? ($w - 2 * $pad) / ($count - 1) : 0;
        $maxY  = max(1, $trendMaxMrr);
        $points = [];
        $areaPoints = [];
        foreach ($trend as $i => $row) {
            $x = $pad + $stepX * $i;
            $y = $h - $pad - (($row['mrr'] / $maxY) * ($h - 2 * $pad));
            $points[] = round($x, 1) . ',' . round($y, 1);
            $areaPoints[] = round($x, 1) . ',' . round($y, 1);
        }
        // close area path
        if (!empty($areaPoints)) {
            $first = explode(',', $areaPoints[0]);
            $last  = explode(',', $areaPoints[count($areaPoints) - 1]);
            $areaPath = 'M ' . $first[0] . ',' . ($h - $pad)
                      . ' L ' . implode(' L ', $areaPoints)
                      . ' L ' . $last[0] . ',' . ($h - $pad) . ' Z';
        } else {
            $areaPath = '';
        }
    @endphp

    <div class="mrr-chart-wrap">
        <svg class="mrr-chart-svg" viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" role="img" aria-label="MRR trend">
            <defs>
                <linearGradient id="mrrArea" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%"   stop-color="#7e58bf" stop-opacity="0.42"/>
                    <stop offset="100%" stop-color="#7e58bf" stop-opacity="0"/>
                </linearGradient>
                <linearGradient id="mrrLine" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%"   stop-color="#b395e6"/>
                    <stop offset="100%" stop-color="#7e58bf"/>
                </linearGradient>
            </defs>

            {{-- Grid horizontal --}}
            @for($g = 0; $g <= 4; $g++)
                @php $gy = $pad + (($h - 2 * $pad) / 4) * $g; @endphp
                <line x1="{{ $pad }}" y1="{{ $gy }}" x2="{{ $w - $pad }}" y2="{{ $gy }}" stroke="#2e2848" stroke-width="1" stroke-dasharray="3,3"/>
            @endfor

            @if($areaPath !== '')
                <path d="{{ $areaPath }}" fill="url(#mrrArea)" stroke="none"/>
            @endif
            @if(!empty($points))
                <polyline points="{{ implode(' ', $points) }}" fill="none" stroke="url(#mrrLine)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                @foreach($trend as $i => $row)
                    @php
                        $cx = $pad + $stepX * $i;
                        $cy = $h - $pad - (($row['mrr'] / $maxY) * ($h - 2 * $pad));
                    @endphp
                    <circle cx="{{ round($cx, 1) }}" cy="{{ round($cy, 1) }}" r="3.5" fill="#fff" stroke="#7e58bf" stroke-width="2">
                        <title>{{ $row['month'] }}: €{{ number_format($row['mrr'], 0, ',', '.') }}</title>
                    </circle>
                @endforeach
            @endif

            {{-- X-axis month labels (her 2-3 ayda bir) --}}
            @php $labelEvery = max(1, (int) ceil($count / 8)); @endphp
            @foreach($trend as $i => $row)
                @if($i % $labelEvery === 0 || $i === $count - 1)
                    @php $lx = $pad + $stepX * $i; @endphp
                    <text x="{{ round($lx, 1) }}" y="{{ $h - 6 }}" font-size="10" fill="#a09bb5" text-anchor="middle" font-family="-apple-system, Segoe UI, Inter, sans-serif">{{ $row['month'] }}</text>
                @endif
            @endforeach

            {{-- Y-axis max --}}
            <text x="{{ $pad - 4 }}" y="{{ $pad + 4 }}" font-size="10" fill="#a09bb5" text-anchor="end" font-family="-apple-system, Segoe UI, Inter, sans-serif">€{{ number_format($maxY, 0, ',', '.') }}</text>
            <text x="{{ $pad - 4 }}" y="{{ $h - $pad + 4 }}" font-size="10" fill="#a09bb5" text-anchor="end" font-family="-apple-system, Segoe UI, Inter, sans-serif">€0</text>
        </svg>
    </div>

    <p style="font-size:11px;color:var(--plat-muted);margin-top:10px;">
        Not: Geçmiş aylar mevcut Company state'inden simüle edilir (snapshot tablo henüz yok).
        Snapshot eklendiğinde tarihsel veri gerçek olur.
    </p>
</div>

{{-- TIER STACKED BAR --}}
<div class="plat-card" style="margin-bottom:24px;">
    <h3 class="plat-card-title"><x-icon name="layers" size="16" /> Tier Bazlı MRR Dağılımı</h3>

    @php
        $totalForStack = max(1, array_sum(array_column($tierBreakdown, 'total_mrr')));
    @endphp
    <div class="mrr-stack">
        @foreach($tierBreakdown as $tier => $row)
            @php $pct = round(($row['total_mrr'] / $totalForStack) * 100); @endphp
            @if($pct > 0)
                <div class="mrr-stack-seg {{ $tier }}" style="width:{{ $pct }}%;" title="{{ $tierLabels[$tier] ?? $tier }}: €{{ number_format($row['total_mrr'], 0, ',', '.') }}">
                    @if($pct >= 6){{ $pct }}%@endif
                </div>
            @endif
        @endforeach
    </div>

    <div style="margin-top:8px;">
        <div class="mrr-tier-grid" style="font-weight:700;color:var(--plat-muted);text-transform:uppercase;font-size:10px;letter-spacing:.5px;">
            <div>Tier</div>
            <div>Hesap</div>
            <div style="text-align:right;">Birim/ay</div>
            <div style="text-align:right;">Toplam MRR</div>
        </div>
        @foreach($tierBreakdown as $tier => $row)
            <div class="mrr-tier-grid">
                <div><span class="plat-badge plat-badge-{{ $tier }}">{{ $tierLabels[$tier] ?? $tier }}</span></div>
                <div style="color:#fff;font-weight:700;">{{ $row['companies'] }} <span style="color:var(--plat-muted);font-weight:500;">şirket</span></div>
                <div style="text-align:right;color:var(--plat-text);">€{{ number_format($row['unit_mrr'], 0, ',', '.') }}</div>
                <div style="text-align:right;color:var(--plat-accent-2);font-weight:800;">€{{ number_format($row['total_mrr'], 0, ',', '.') }}</div>
            </div>
        @endforeach
    </div>
</div>

{{-- AYLIK NET NEW MRR TABLO --}}
<div class="plat-card">
    <h3 class="plat-card-title"><x-icon name="bar-chart-3" size="16" /> Aylık Net New MRR & Hareket</h3>
    <table class="plat-table mrr-trend-table" style="margin-top:8px;">
        <thead>
            <tr>
                <th>Ay</th>
                <th style="text-align:right;">MRR (€)</th>
                <th style="text-align:right;">Aktif</th>
                <th style="text-align:right;">Yeni</th>
                <th style="text-align:right;">Churn</th>
                <th style="text-align:right;">Net New</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_reverse($trend) as $row)
                <tr>
                    <td style="font-weight:700;color:#fff;">{{ $row['month'] }}</td>
                    <td class="num">€{{ number_format($row['mrr'], 0, ',', '.') }}</td>
                    <td class="num">{{ $row['active'] }}</td>
                    <td class="num" style="color:var(--plat-ok);">+{{ $row['new'] }}</td>
                    <td class="num" style="color:{{ $row['churn'] > 0 ? 'var(--plat-danger)' : 'var(--plat-muted)' }};">
                        {{ $row['churn'] > 0 ? '-' . $row['churn'] : '0' }}
                    </td>
                    <td class="num" style="color:{{ $row['net_new'] > 0 ? 'var(--plat-ok)' : ($row['net_new'] < 0 ? 'var(--plat-danger)' : 'var(--plat-muted)') }};">
                        {{ $row['net_new'] > 0 ? '+' . $row['net_new'] : (string) $row['net_new'] }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
