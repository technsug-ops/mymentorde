@extends('manager.layouts.app')
@section('title', 'Finansal Raporlar & Projeksiyon')
@section('page_title', 'Finansal Raporlar & Projeksiyon')

@push('head')
<style>
.fin-bar-wrap { display:flex;align-items:center;gap:8px; }
.fin-bar { height:10px;border-radius:5px;min-width:2px;transition:width .3s; }
.fin-table { width:100%;border-collapse:collapse;font-size:12px; }
.fin-table th { padding:7px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;background:var(--u-bg);white-space:nowrap; }
.fin-table td { padding:8px 12px;border-bottom:1px solid var(--u-line);vertical-align:middle; }
.fin-table tbody tr:hover { background:rgba(30,64,175,.025); }
.proj-card { background:var(--u-card);border:1px solid var(--u-line);border-radius:10px;padding:14px 18px;text-align:center; }
.proj-card .amount { font-size:22px;font-weight:800;color:#1e40af;line-height:1.1; }
.proj-card .label { font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px; }
.yoy-pill { display:inline-block;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700; }
.trend-up   { background:#dcfce7;color:#15803d; }
.trend-down { background:#fee2e2;color:#dc2626; }
.trend-flat { background:#f1f5f9;color:var(--u-muted); }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<div style="display:flex;gap:6px;align-items:center;margin-bottom:14px;font-size:11px;color:var(--u-muted);">
    <a href="/manager/finance" style="color:#1e40af;text-decoration:none;font-weight:700;">Finans</a>
    <span>›</span><span>Raporlar & Projeksiyon</span>
</div>

{{-- Dönem Seçici --}}
<div style="display:flex;gap:6px;align-items:center;margin-bottom:16px;flex-wrap:wrap;">
    @foreach(['1m'=>'1 Ay','3m'=>'3 Ay','6m'=>'6 Ay','1y'=>'1 Yıl'] as $p => $lbl)
    <a href="/manager/finance/reports?period={{ $p }}"
       style="padding:6px 18px;font-size:12px;font-weight:700;border-radius:8px;text-decoration:none;border:1.5px solid {{ $period===$p ? '#1e40af' : 'var(--u-line)' }};background:{{ $period===$p ? '#1e40af' : 'var(--u-card)' }};color:{{ $period===$p ? '#fff' : 'var(--u-muted)' }};">
        {{ $lbl }}
    </a>
    @endforeach
    <span style="font-size:11px;color:var(--u-muted);margin-left:4px;">
        {{ $history->first()['label'] ?? '' }} – {{ $history->last()['label'] ?? '' }}
    </span>
</div>

{{-- ─── Özet KPI'lar ─── --}}
@php
    $maxIncome = $history->max('income') ?: 1;
    $maxAbsNet = max(abs($history->max('net')), abs($history->min('net'))) ?: 1;
    $lyTotal = $historyTotal['last_year_income'];
    $overallYoy = $lyTotal > 0 ? round(($historyTotal['income'] - $lyTotal) / $lyTotal * 100, 1) : null;
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px;">
    @foreach([
        ['label'=>'Toplam Gelir',  'val'=>$historyTotal['income'],  'color'=>'#1e40af', 'sub'=>'dönem toplamı'],
        ['label'=>'Toplam Gider',  'val'=>$historyTotal['expense'], 'color'=>'#dc2626', 'sub'=>'dönem toplamı'],
        ['label'=>'Net Kar',       'val'=>$historyTotal['net'],     'color'=>$historyTotal['net']>=0?'#16a34a':'#dc2626', 'sub'=>'gelir − gider'],
        ['label'=>'Geçen Yıl Aynı Dönem','val'=>$historyTotal['last_year_income'],'color'=>'#7c3aed','sub'=>'karşılaştırma'],
    ] as $k)
    <div style="background:var(--u-card);border:1px solid var(--u-line);border-top:3px solid {{ $k['color'] }};border-radius:10px;padding:12px 14px;">
        <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">{{ $k['label'] }}</div>
        <div style="font-size:20px;font-weight:800;color:{{ $k['color'] }};">€{{ number_format($k['val'],0,'.','.') }}</div>
        <div style="font-size:10px;color:var(--u-muted);margin-top:2px;">{{ $k['sub'] }}</div>
    </div>
    @endforeach
</div>
@if($overallYoy !== null)
<div style="margin-bottom:16px;padding:8px 14px;border-radius:8px;background:{{ $overallYoy>=0?'#eff6ff':'#fff1f2' }};border:1px solid {{ $overallYoy>=0?'#bfdbfe':'#fecdd3' }};font-size:12px;font-weight:700;color:{{ $overallYoy>=0?'#1e40af':'#dc2626' }};">
    Geçen yıl aynı döneme göre: {{ $overallYoy >= 0 ? '▲' : '▼' }} %{{ abs($overallYoy) }}
    (€{{ number_format($historyTotal['income'] - $lyTotal, 0, '.', '.') }} {{ $overallYoy >= 0 ? 'artış' : 'düşüş' }})
</div>
@endif

{{-- ─── Çizgi Grafik ─── --}}
<section class="panel" style="padding:0;overflow:hidden;margin-bottom:16px;">
    <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div style="font-weight:700;font-size:var(--tx-sm);">📉 Gelir Trendi & Projeksiyon</div>
        <div style="display:flex;gap:14px;flex-wrap:wrap;">
            <span style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#1e40af;"><span style="display:inline-block;width:22px;height:3px;background:#1e40af;border-radius:2px;"></span> Gerçek Gelir</span>
            <span style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#7c3aed;"><span style="display:inline-block;width:22px;height:3px;background:#7c3aed;border-radius:2px;border-top:2px dashed #7c3aed;background:none;"></span> Pipeline</span>
            <span style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#0891b2;"><span style="display:inline-block;width:22px;height:3px;background:#0891b2;border-radius:2px;border-top:2px dashed #0891b2;background:none;"></span> Trend</span>
            <span style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#16a34a;"><span style="display:inline-block;width:22px;height:3px;background:#16a34a;border-radius:2px;border-top:2px dashed #16a34a;background:none;"></span> YoY</span>
        </div>
    </div>
    <div style="padding:16px;overflow-x:auto;">
        <canvas id="finChart" style="width:100%;height:280px;display:block;"></canvas>
    </div>
</section>

@php
$_chartData = [
    'history'  => $history->map(fn($r) => ['label' => $r['label'], 'income' => $r['income'], 'expense' => $r['expense']])->values(),
    'pipeline' => $pipelineProjection->map(fn($r) => ['label' => $r['label'], 'value' => $r['expected']])->values(),
    'trend'    => $trendProjection->map(fn($r) => ['label' => $r['label'], 'value' => $r['projected']])->values(),
    'yoy'      => $yoyProjection->map(fn($r) => ['label' => $r['label'], 'value' => $r['projected']])->values(),
];
@endphp

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function() {
    var chartData = @json($_chartData);

    var canvas = document.getElementById('finChart');
    if (!canvas) return;

    function draw() {
        var dpr = window.devicePixelRatio || 1;
        var W   = canvas.parentElement.clientWidth - 32;
        var H   = 280;
        canvas.width  = W * dpr;
        canvas.height = H * dpr;
        canvas.style.width  = W + 'px';
        canvas.style.height = H + 'px';

        var ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);

        var PAD = { top: 20, right: 20, bottom: 36, left: 64 };
        var cW = W - PAD.left - PAD.right;
        var cH = H - PAD.top  - PAD.bottom;

        // Build full label list: history + 3 projection months
        var hLen = chartData.history.length;
        var pLen = 3;
        var labels = chartData.history.map(function(r){ return r.label; })
                    .concat(chartData.pipeline.map(function(r){ return r.label; }));

        // All income values to find Y range
        var allVals = chartData.history.map(function(r){ return r.income; })
            .concat(chartData.pipeline.map(function(r){ return r.value; }))
            .concat(chartData.trend.map(function(r){ return r.value; }))
            .concat(chartData.yoy.map(function(r){ return r.value; }))
            .filter(function(v){ return v > 0; });

        var maxVal = allVals.length ? Math.max.apply(null, allVals) : 1;
        // Round up to nice number
        var mag = Math.pow(10, Math.floor(Math.log10(maxVal)));
        maxVal  = Math.ceil(maxVal / mag) * mag;
        maxVal  = maxVal * 1.1;

        var totalPts = hLen + pLen;
        var xStep = cW / (totalPts - 1);

        function xPos(i)  { return PAD.left + i * xStep; }
        function yPos(v)  { return PAD.top + cH - (v / maxVal) * cH; }

        // ── Grid lines ──
        ctx.strokeStyle = getComputedStyle(document.documentElement).getPropertyValue('--u-line') || '#e2e8f0';
        ctx.lineWidth = 1;
        var gridLines = 5;
        for (var g = 0; g <= gridLines; g++) {
            var gy = PAD.top + (cH / gridLines) * g;
            ctx.beginPath();
            ctx.moveTo(PAD.left, gy);
            ctx.lineTo(PAD.left + cW, gy);
            ctx.stroke();
            // Y label
            var yVal = maxVal - (maxVal / gridLines) * g;
            ctx.fillStyle = '#94a3b8';
            ctx.font = '10px system-ui,sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText('€' + (yVal >= 1000 ? Math.round(yVal/1000) + 'K' : Math.round(yVal)), PAD.left - 6, gy + 4);
        }

        // ── Divider: actual vs projected ──
        var divX = xPos(hLen - 1) + xStep / 2;
        ctx.save();
        ctx.setLineDash([4, 4]);
        ctx.strokeStyle = '#cbd5e1';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.moveTo(divX, PAD.top);
        ctx.lineTo(divX, PAD.top + cH);
        ctx.stroke();
        ctx.restore();
        ctx.fillStyle = '#94a3b8';
        ctx.font = '9px system-ui,sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('PROJEKSIYON →', divX + 36, PAD.top + 12);

        // ── X axis labels ──
        ctx.fillStyle = '#64748b';
        ctx.font      = '10px system-ui,sans-serif';
        ctx.textAlign = 'center';
        for (var i = 0; i < labels.length; i++) {
            ctx.fillText(labels[i], xPos(i), H - 8);
        }

        // ── Draw line helper ──
        function drawLine(points, color, dashed, lineW, dotR) {
            if (!points || points.length < 1) return;
            ctx.save();
            ctx.strokeStyle = color;
            ctx.lineWidth   = lineW || 2.5;
            ctx.lineJoin    = 'round';
            if (dashed) ctx.setLineDash([6, 4]);
            else        ctx.setLineDash([]);

            ctx.beginPath();
            for (var j = 0; j < points.length; j++) {
                if (j === 0) ctx.moveTo(points[j][0], points[j][1]);
                else         ctx.lineTo(points[j][0], points[j][1]);
            }
            ctx.stroke();
            ctx.restore();

            // Dots
            for (var j = 0; j < points.length; j++) {
                ctx.beginPath();
                ctx.arc(points[j][0], points[j][1], dotR || 4, 0, Math.PI * 2);
                ctx.fillStyle = color;
                ctx.fill();
                ctx.fillStyle = '#fff';
                ctx.beginPath();
                ctx.arc(points[j][0], points[j][1], (dotR || 4) - 1.5, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        // ── Actual income (solid blue) ──
        var actualPts = chartData.history.map(function(r, i) {
            return [xPos(i), yPos(r.income)];
        });
        drawLine(actualPts, '#1e40af', false, 2.5, 4);

        // ── Bridge: last actual → first projection (shared anchor) ──
        var lastActual = actualPts[actualPts.length - 1];

        // ── Pipeline (dashed purple) ──
        var pipePts = [lastActual].concat(chartData.pipeline.map(function(r, i) {
            return [xPos(hLen + i), yPos(r.value)];
        }));
        drawLine(pipePts, '#7c3aed', true, 2, 3.5);

        // ── Trend (dashed cyan) ──
        var trendPts = [lastActual].concat(chartData.trend.map(function(r, i) {
            return [xPos(hLen + i), yPos(r.value)];
        }));
        drawLine(trendPts, '#0891b2', true, 2, 3.5);

        // ── YoY (dashed green) ──
        var yoyPts = [lastActual].concat(chartData.yoy.map(function(r, i) {
            return [xPos(hLen + i), yPos(r.value)];
        }));
        drawLine(yoyPts, '#16a34a', true, 2, 3.5);

        // ── Tooltip on hover ──
        canvas._chartMeta = { xPos: xPos, yPos: yPos, hLen: hLen, labels: labels,
            actual: chartData.history, pipeline: chartData.pipeline,
            trend: chartData.trend, yoy: chartData.yoy,
            PAD: PAD, cH: cH, xStep: xStep, maxVal: maxVal };
    }

    draw();
    window.addEventListener('resize', draw);

    // Tooltip
    canvas.addEventListener('mousemove', function(e) {
        var m = canvas._chartMeta;
        if (!m) return;
        var rect = canvas.getBoundingClientRect();
        var mx   = e.clientX - rect.left;
        var idx  = Math.round((mx - m.PAD.left) / m.xStep);
        idx = Math.max(0, Math.min(idx, m.labels.length - 1));

        var tip = document.getElementById('finChartTip');
        if (!tip) {
            tip = document.createElement('div');
            tip.id = 'finChartTip';
            tip.style.cssText = 'position:fixed;background:#1e293b;color:#fff;font-size:11px;padding:8px 12px;border-radius:8px;pointer-events:none;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.2);line-height:1.8;';
            document.body.appendChild(tip);
        }

        var isProj = idx >= m.hLen;
        var projIdx = idx - m.hLen;
        var lines = ['<strong>' + m.labels[idx] + (isProj ? ' (Projeksiyon)' : '') + '</strong>'];

        if (!isProj) {
            lines.push('💰 Gelir: €' + Number(m.actual[idx].income).toLocaleString('tr-TR'));
            lines.push('📉 Gider: €' + Number(m.actual[idx].expense).toLocaleString('tr-TR'));
        } else {
            if (m.pipeline[projIdx]) lines.push('🔮 Pipeline: €' + Number(m.pipeline[projIdx].value).toLocaleString('tr-TR'));
            if (m.trend[projIdx])    lines.push('📊 Trend: €'    + Number(m.trend[projIdx].value).toLocaleString('tr-TR'));
            if (m.yoy[projIdx])      lines.push('📅 YoY: €'      + Number(m.yoy[projIdx].value).toLocaleString('tr-TR'));
        }

        tip.innerHTML = lines.join('<br>');
        tip.style.display = 'block';
        tip.style.left = (e.clientX + 14) + 'px';
        tip.style.top  = (e.clientY - 10) + 'px';
    });

    canvas.addEventListener('mouseleave', function() {
        var tip = document.getElementById('finChartTip');
        if (tip) tip.style.display = 'none';
    });
}());
</script>
@endpush

{{-- ─── Aylık Tablo ─── --}}
<section class="panel" style="padding:0;overflow:hidden;margin-bottom:16px;">
    <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);font-weight:700;font-size:var(--tx-sm);">
        📊 Aylık Döküm — Geçmiş {{ $months }} Ay
    </div>
    <div style="overflow-x:auto;">
    <table class="fin-table">
        <thead>
            <tr>
                <th>Ay</th>
                <th>Gelir</th>
                <th style="min-width:160px;">Gelir Çubuğu</th>
                <th>Gider</th>
                <th>Net</th>
                <th>Geçen Yıl</th>
                <th>YoY</th>
            </tr>
        </thead>
        <tbody>
        @foreach($history as $row)
        @php
            $barW = $maxIncome > 0 ? round($row['income'] / $maxIncome * 140) : 0;
            $netColor = $row['net'] >= 0 ? '#16a34a' : '#dc2626';
            $yoyClass = !isset($row['yoy_change']) ? 'trend-flat' : ($row['yoy_change'] >= 0 ? 'trend-up' : 'trend-down');
        @endphp
        <tr>
            <td style="font-weight:700;white-space:nowrap;">{{ $row['label'] }}</td>
            <td style="font-weight:700;color:#1e40af;white-space:nowrap;">€{{ number_format($row['income'],0,'.','.') }}</td>
            <td>
                <div class="fin-bar-wrap">
                    <div class="fin-bar" style="width:{{ $barW }}px;background:#1e40af;"></div>
                </div>
            </td>
            <td style="color:#dc2626;white-space:nowrap;">€{{ number_format($row['expense'],0,'.','.') }}</td>
            <td style="font-weight:700;color:{{ $netColor }};white-space:nowrap;">
                {{ $row['net'] >= 0 ? '+' : '' }}€{{ number_format($row['net'],0,'.','.') }}
            </td>
            <td style="color:var(--u-muted);white-space:nowrap;">€{{ number_format($row['last_year_income'],0,'.','.') }}</td>
            <td>
                @if(isset($row['yoy_change']))
                <span class="yoy-pill {{ $yoyClass }}">{{ $row['yoy_change'] >= 0 ? '▲' : '▼' }} %{{ abs($row['yoy_change']) }}</span>
                @else
                <span style="font-size:10px;color:var(--u-muted);">—</span>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr style="background:var(--u-bg);font-weight:800;">
                <td style="padding:8px 12px;font-size:12px;">TOPLAM</td>
                <td style="padding:8px 12px;color:#1e40af;">€{{ number_format($historyTotal['income'],0,'.','.') }}</td>
                <td></td>
                <td style="padding:8px 12px;color:#dc2626;">€{{ number_format($historyTotal['expense'],0,'.','.') }}</td>
                <td style="padding:8px 12px;color:{{ $historyTotal['net']>=0?'#16a34a':'#dc2626' }};">
                    {{ $historyTotal['net']>=0?'+':'' }}€{{ number_format($historyTotal['net'],0,'.','.') }}
                </td>
                <td style="padding:8px 12px;color:var(--u-muted);">€{{ number_format($historyTotal['last_year_income'],0,'.','.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    </div>
</section>

{{-- ─── Projeksiyon 1: Pipeline ─── --}}
<section class="panel" style="padding:0;overflow:hidden;margin-bottom:16px;">
    <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <div>
            <div style="font-weight:700;font-size:var(--tx-sm);">🔮 Projeksiyon 1 — Mevcut Sözleşme Pipeline</div>
            <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">
                {{ $pendingContracts->count() }} bekleyen sözleşme · Ort. imzalama süresi: <strong>{{ $avgTimeToSign }} gün</strong>
            </div>
        </div>
        <div style="font-size:13px;font-weight:800;color:#7c3aed;">
            Pipeline Toplamı: €{{ number_format($pipelineTotal,0,'.','.') }}
        </div>
    </div>
    <div style="padding:14px 16px;">

        {{-- 3 ay dağılımı --}}
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px;">
        @foreach($pipelineProjection as $pm)
        <div class="proj-card" style="border-top:3px solid #7c3aed;">
            <div class="label">{{ $pm['label'] }}</div>
            <div class="amount" style="color:#7c3aed;">€{{ number_format($pm['expected'],0,'.','.') }}</div>
            <div style="font-size:10px;color:var(--u-muted);margin-top:4px;">beklenen gelir</div>
        </div>
        @endforeach
        </div>

        @if($pipelineUnscheduled > 0)
        <div style="padding:8px 12px;background:#faf5ff;border:1px solid #e9d5ff;border-radius:7px;font-size:11px;color:#7c3aed;margin-bottom:14px;">
            ⚠ €{{ number_format($pipelineUnscheduled,0,'.','.') }} değerinde sözleşmenin imzalanma tarihi öngörülemiyor (tarih bilgisi eksik veya 3 ayı aşıyor).
        </div>
        @endif

        {{-- Pipeline detay tablosu --}}
        @if($pendingContracts->isNotEmpty())
        <div style="font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Bekleyen Sözleşmeler</div>
        <div style="overflow-x:auto;">
        <table class="fin-table">
            <thead>
                <tr>
                    <th>Öğrenci</th>
                    <th>Paket</th>
                    <th>Tutar</th>
                    <th>Talep Tarihi</th>
                    <th>Tahmini İmza</th>
                </tr>
            </thead>
            <tbody>
            @foreach($pendingContracts as $c)
            @php
                $reqDate = $c->contract_requested_at ? \Carbon\Carbon::parse($c->contract_requested_at) : null;
                $estSign = $reqDate ? $reqDate->copy()->addDays($avgTimeToSign) : null;
            @endphp
            <tr>
                <td style="font-weight:700;">{{ $c->first_name }} {{ $c->last_name }}</td>
                <td style="font-size:11px;color:var(--u-muted);">{{ $c->selected_package_title ?: '—' }}</td>
                <td style="font-weight:700;color:#7c3aed;">€{{ number_format($c->contract_amount_eur,0,'.','.') }}</td>
                <td style="font-size:11px;color:var(--u-muted);">{{ $reqDate ? $reqDate->format('d.m.Y') : '—' }}</td>
                <td style="font-size:11px;">
                    @if($estSign)
                    <span style="color:{{ $estSign->isPast() ? '#dc2626' : '#16a34a' }};">{{ $estSign->format('d.m.Y') }}</span>
                    @else <span style="color:var(--u-muted);">—</span> @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @else
        <div style="text-align:center;padding:20px;color:var(--u-muted);font-size:13px;">Bekleyen sözleşme yok.</div>
        @endif
    </div>
</section>

{{-- ─── Projeksiyon 2: Trend + YoY ─── --}}
<section class="panel" style="padding:0;overflow:hidden;">
    <div style="padding:12px 16px;border-bottom:1px solid var(--u-line);">
        <div style="font-weight:700;font-size:var(--tx-sm);">📈 Projeksiyon 2 — Geçmiş Trend & YoY Analizi</div>
        <div style="font-size:11px;color:var(--u-muted);margin-top:2px;">
            Doğrusal trend (son 6 ay eğimi: {{ $slope >= 0 ? '+' : '' }}€{{ number_format($slope,0,'.','.') }}/ay) ·
            Ort. YoY büyüme: <strong style="color:{{ $avgYoyRate>=0?'#16a34a':'#dc2626' }};">{{ $avgYoyRate >= 0 ? '+' : '' }}%{{ round($avgYoyRate * 100, 1) }}</strong>
        </div>
    </div>
    <div style="padding:14px 16px;">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

            {{-- Trend Projeksiyonu --}}
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;">
                    📊 Trend Bazlı (Doğrusal Regresyon)
                </div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                @foreach($trendProjection as $tp)
                <div class="proj-card" style="border-top:3px solid #0891b2;">
                    <div class="label">{{ $tp['label'] }}</div>
                    <div class="amount" style="color:#0891b2;">€{{ number_format($tp['projected'],0,'.','.') }}</div>
                    <div style="font-size:10px;color:var(--u-muted);margin-top:4px;">trend tahmini</div>
                </div>
                @endforeach
                </div>
                <div style="margin-top:10px;padding:8px 12px;background:#e0f2fe;border-radius:7px;font-size:11px;color:#0369a1;">
                    Son 6 ay gelir ortalaması: <strong>€{{ number_format($yMean,0,'.','.') }}/ay</strong>
                    · Aylık eğim: {{ $slope>=0?'+':'' }}€{{ number_format($slope,0,'.','.') }}
                </div>
            </div>

            {{-- YoY Projeksiyonu --}}
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;">
                    📅 YoY Bazlı (Geçen Yıl × Büyüme Oranı)
                </div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                @foreach($yoyProjection as $yp)
                <div class="proj-card" style="border-top:3px solid #16a34a;">
                    <div class="label">{{ $yp['label'] }}</div>
                    <div class="amount" style="color:#16a34a;">€{{ number_format($yp['projected'],0,'.','.') }}</div>
                    <div style="font-size:10px;color:var(--u-muted);margin-top:4px;">
                        geçen yıl: €{{ number_format($yp['last_year'],0,'.','.') }}
                    </div>
                </div>
                @endforeach
                </div>
                <div style="margin-top:10px;padding:8px 12px;background:#dcfce7;border-radius:7px;font-size:11px;color:#166534;">
                    Ortalama YoY büyüme oranı: <strong>{{ $avgYoyRate >= 0 ? '+' : '' }}%{{ round($avgYoyRate * 100, 1) }}</strong>
                    (son {{ $months }} ay verisi ile hesaplandı)
                </div>
            </div>

        </div>

        {{-- Karşılaştırma tablosu --}}
        <div style="margin-top:16px;overflow-x:auto;">
        <table class="fin-table">
            <thead>
                <tr>
                    <th>Ay</th>
                    <th>Pipeline Tahmini</th>
                    <th>Trend Tahmini</th>
                    <th>YoY Tahmini</th>
                    <th>Geçen Yıl Gerçek</th>
                </tr>
            </thead>
            <tbody>
            @foreach($trendProjection as $i => $tp)
            @php
                $pp = $pipelineProjection[$i] ?? null;
                $yp = $yoyProjection[$i] ?? null;
            @endphp
            <tr>
                <td style="font-weight:700;">{{ $tp['label'] }}</td>
                <td style="color:#7c3aed;font-weight:700;">€{{ number_format($pp['expected'] ?? 0, 0, '.', '.') }}</td>
                <td style="color:#0891b2;font-weight:700;">€{{ number_format($tp['projected'], 0, '.', '.') }}</td>
                <td style="color:#16a34a;font-weight:700;">€{{ number_format($yp['projected'] ?? 0, 0, '.', '.') }}</td>
                <td style="color:var(--u-muted);">€{{ number_format($yp['last_year'] ?? 0, 0, '.', '.') }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>

    </div>
</section>

@endsection
