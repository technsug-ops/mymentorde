@extends('marketing-admin.layouts.app')

@section('title', 'KPI Dashboard')
@section('page_subtitle', 'KPI & Raporlar — dönem bazlı pazarlama ve satış performans analizi')

@section('topbar-actions')
<a class="btn {{ request()->is('mktg-admin/kpi') ? '' : 'alt' }}" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/kpi">KPI Dashboard</a>
<a class="btn alt" style="font-size:var(--tx-xs);padding:6px 12px;" href="/mktg-admin/reports">Raporlar</a>
@endsection

@section('content')
<style>
details summary::-webkit-details-marker { display:none; }
details summary { outline:none; list-style:none; }
.det-sum { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.det-sum h3 { margin:0; font-size:14px; font-weight:700; }
.det-chev { font-size:11px; color:var(--u-muted,#64748b); transition:transform .2s; }
details[open] .det-chev { transform:rotate(180deg); }
details[open] .det-sum { margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--u-line,#e2e8f0); }

.ks-bar { display:flex; flex-wrap:wrap; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.ks-item { flex:1 1 0; padding:12px 16px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.ks-item:last-child { border-right:none; }
.ks-val  { font-size:20px; font-weight:700; line-height:1.1; color:var(--u-brand,#1e40af); }
.ks-val.ok   { color:var(--u-ok,#16a34a); }
.ks-val.warn { color:var(--u-warn,#d97706); }
.ks-val.danger { color:var(--u-danger,#dc2626); }
.ks-lbl  { font-size:11px; color:var(--u-muted,#64748b); margin-top:2px; }
.ks-section-label { font-size:10px; font-weight:700; color:var(--u-muted,#64748b); text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px; }

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; }
.tl-tbl th { text-align:left; padding:9px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); border-bottom:1px solid var(--u-line,#e2e8f0); }
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:middle; }
.tl-tbl tr:last-child td { border-bottom:none; }

.wf-field { display:flex; flex-direction:column; gap:3px; }
.wf-field label { font-size:11px; font-weight:600; color:var(--u-muted,#64748b); }
.wf-field input { height:34px; padding:0 10px; border:1px solid var(--u-line,#e2e8f0); border-radius:8px; background:var(--u-card,#fff); color:var(--u-text,#0f172a); font-size:13px; outline:none; }
.wf-field input:focus { border-color:var(--u-brand,#1e40af); }

.qs-btn { height:28px; padding:0 10px; border:1px solid var(--u-line,#e2e8f0); border-radius:6px; font-size:11px; font-weight:600; cursor:pointer; background:var(--u-card,#fff); color:var(--u-muted,#64748b); white-space:nowrap; transition:all .15s; }
.qs-btn:hover { border-color:var(--u-brand,#1e40af); color:var(--u-brand,#1e40af); }

/* Chart legend */
.ch-legend { display:flex; flex-wrap:wrap; gap:6px 12px; margin-top:12px; padding-top:10px; border-top:1px solid var(--u-line,#e2e8f0); }
.ch-legend-item { display:flex; align-items:center; gap:5px; font-size:11px; color:var(--u-muted,#64748b); }
.ch-legend-dot { width:9px; height:9px; border-radius:50%; flex-shrink:0; }

/* Chart tabs */
.ch-tabs { display:flex; gap:4px; margin-bottom:10px; }
.ch-tab { padding:4px 10px; border:1px solid var(--u-line,#e2e8f0); border-radius:6px; font-size:11px; font-weight:600; cursor:pointer; background:var(--u-card,#fff); color:var(--u-muted,#64748b); transition:all .15s; }
.ch-tab.active { background:var(--u-brand,#1e40af); color:#fff; border-color:transparent; }
</style>

@php
$roi       = (float)($kpis['roi'] ?? 0);
$convRate  = (float)($kpis['conversion_rate'] ?? 0);
$roiClass  = $roi > 0 ? 'ok' : ($roi < 0 ? 'danger' : '');
$convClass = $convRate >= 20 ? 'ok' : ($convRate >= 10 ? 'warn' : '');
$statusColors = [
    'new'=>'#1e40af','contacted'=>'#7c3aed','qualified'=>'#d97706',
    'meeting_scheduled'=>'#0891b2','proposal_sent'=>'#f59e0b',
    'converted'=>'#16a34a','lost'=>'#dc2626',
];
@endphp

@php
$_jsSources  = collect($sourceSummary)->map(fn($r) => ['label'=>$r['source'],'value'=>(int)$r['lead_count'],'conv'=>(float)$r['conversion_rate']])->values()->toArray();
$_jsPipeline = collect($pipelineSummary)->map(fn($r) => ['label'=>$r['status'],'value'=>(int)$r['count']])->values()->toArray();
$_jsTrend    = collect($trend)->map(fn($r) => ['label'=>$r['label'],'leads'=>(int)$r['leads'],'converted'=>(int)$r['converted'],'revenue'=>(float)$r['revenue']])->values()->toArray();
$_jsExtProv  = collect($externalProviderSummary)->map(fn($r) => ['label'=>$r['provider'],'spend'=>(float)($r['spend']??0),'clicks'=>(int)($r['clicks']??0)])->values()->toArray();
@endphp
{{-- JS veri köprüsü --}}
<script>
var _kpiSources  = {!! json_encode($_jsSources) !!};
var _kpiPipeline = {!! json_encode($_jsPipeline) !!};
var _kpiTrend    = {!! json_encode($_jsTrend) !!};
var _kpiExtProv  = {!! json_encode($_jsExtProv) !!};
</script>

<div style="display:flex;align-items:center;justify-content:flex-end;gap:12px;margin-bottom:12px;">
    <span id="kpi-last-updated" style="font-size:12px;color:var(--u-muted);">Az önce güncellendi</span>
    <button id="kpi-refresh-btn" onclick="refreshKpi()"
            style="background:var(--u-card);border:1px solid var(--u-line);border-radius:7px;padding:6px 14px;font-size:12px;font-weight:600;cursor:pointer;color:var(--u-text);">
        Yenile
    </button>
</div>

<div id="kpi-content">
<div style="display:grid;gap:12px;">

    {{-- 1. FİLTRE --}}
    <div class="card">
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;">
            <div style="display:flex;flex-direction:column;gap:6px;">
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <button type="button" class="qs-btn" onclick="setRange(7)">Son 7 Gün</button>
                    <button type="button" class="qs-btn" onclick="setRange(30)">Son 30 Gün</button>
                    <button type="button" class="qs-btn" onclick="setRange(90)">Son 90 Gün</button>
                    <button type="button" class="qs-btn" onclick="setThisMonth()">Bu Ay</button>
                    <button type="button" class="qs-btn" onclick="setLastMonth()">Önceki Ay</button>
                </div>
                <form id="kpiFilterForm" method="GET" action="/mktg-admin/kpi" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
                    <div class="wf-field">
                        <label>Başlangıç</label>
                        <input type="date" id="start_date" name="start_date" value="{{ $range['start_date'] ?? '' }}">
                    </div>
                    <div class="wf-field">
                        <label>Bitiş</label>
                        <input type="date" id="end_date" name="end_date" value="{{ $range['end_date'] ?? '' }}">
                    </div>
                    <button type="submit" class="btn" style="height:34px;font-size:var(--tx-xs);padding:0 18px;">Filtrele</button>
                    <a href="/mktg-admin/kpi" class="btn alt" style="height:34px;font-size:var(--tx-xs);padding:0 12px;display:flex;align-items:center;color:var(--u-muted,#64748b);">Temizle</a>
                </form>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end;">
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $range['start_date'] ?? '—' }} – {{ $range['end_date'] ?? '—' }}</div>
                <form method="POST" action="/mktg-admin/reports/generate">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $range['start_date'] ?? '' }}">
                    <input type="hidden" name="end_date" value="{{ $range['end_date'] ?? '' }}">
                    <input type="hidden" name="report_type" value="kpi_snapshot">
                    <button type="submit" class="btn ok" style="height:34px;font-size:var(--tx-xs);padding:0 16px;">Rapor Oluştur & Kaydet</button>
                </form>
            </div>
        </div>
    </div>

    {{-- 2. PIPELINE KPIs --}}
    <div>
        <div class="ks-section-label">Pipeline & Dönüşüm</div>
        <div class="ks-bar">
            <div class="ks-item"><div class="ks-val">{{ $kpis['lead_count'] ?? 0 }}</div><div class="ks-lbl">Lead</div></div>
            <div class="ks-item"><div class="ks-val">{{ $kpis['verified_count'] ?? 0 }}</div><div class="ks-lbl">Doğrulanan</div></div>
            <div class="ks-item"><div class="ks-val ok">{{ $kpis['converted_count'] ?? 0 }}</div><div class="ks-lbl">Dönüştürülen</div></div>
            <div class="ks-item"><div class="ks-val {{ $convClass }}">{{ number_format($convRate,1,'.',',' ) }}%</div><div class="ks-lbl">Dönüşüm Oranı</div></div>
            <div class="ks-item"><div class="ks-val">{{ $kpis['active_campaign_count'] ?? 0 }}</div><div class="ks-lbl">Aktif Kampanya</div></div>
            <div class="ks-item"><div class="ks-val warn">{{ $kpis['open_guest_count'] ?? 0 }}</div><div class="ks-lbl">Açık Aday Öğrenci</div></div>
            <div class="ks-item"><div class="ks-val">{{ $kpis['archived_guest_count'] ?? 0 }}</div><div class="ks-lbl">Arşivlenen</div></div>
        </div>
    </div>

    {{-- 3. FİNANSAL KPIs --}}
    <div>
        <div class="ks-section-label">Finansal</div>
        <div class="ks-bar">
            <div class="ks-item"><div class="ks-val danger">{{ number_format((float)($kpis['spent_total']??0),2,'.',',' ) }} €</div><div class="ks-lbl">Harcama</div></div>
            <div class="ks-item"><div class="ks-val ok">{{ number_format((float)($kpis['revenue_total']??0),2,'.',',' ) }} €</div><div class="ks-lbl">Gelir</div></div>
            <div class="ks-item" style="border-left:3px solid {{ $roi>=0?'var(--u-ok,#16a34a)':'var(--u-danger,#dc2626)' }};">
                <div class="ks-val {{ $roiClass }}" style="font-size:var(--tx-2xl);">{{ $roi>=0?'+':'' }}{{ number_format($roi,1,'.',',' ) }}%</div>
                <div class="ks-lbl">ROI</div>
            </div>
            <div class="ks-item"><div class="ks-val">{{ number_format((float)($kpis['cpl']??0),2,'.',',' ) }} €</div><div class="ks-lbl">CPL</div></div>
            <div class="ks-item"><div class="ks-val">{{ number_format((float)($kpis['cpa']??0),2,'.',',' ) }} €</div><div class="ks-lbl">CPA</div></div>
        </div>
    </div>

    {{-- 4. EXTERNAL KPIs --}}
    <div>
        <div class="ks-section-label">External Platform</div>
        <div class="ks-bar">
            <div class="ks-item"><div class="ks-val danger">{{ number_format((float)($kpis['external_spend']??0),2,'.',',' ) }} €</div><div class="ks-lbl">Harcama</div></div>
            <div class="ks-item"><div class="ks-val">{{ number_format((float)($kpis['external_impressions']??0),0,'.',',' ) }}</div><div class="ks-lbl">Gösterim</div></div>
            <div class="ks-item"><div class="ks-val">{{ number_format((float)($kpis['external_clicks']??0),0,'.',',' ) }}</div><div class="ks-lbl">Tıklama</div></div>
            <div class="ks-item"><div class="ks-val ok">{{ number_format((float)($kpis['external_conversions']??0),0,'.',',' ) }}</div><div class="ks-lbl">Dönüşüm</div></div>
            <div class="ks-item"><div class="ks-val">{{ number_format((float)($kpis['external_ctr']??0),2,'.',',' ) }}%</div><div class="ks-lbl">CTR</div></div>
            <div class="ks-item"><div class="ks-val">{{ number_format((float)($kpis['external_cpc']??0),2,'.',',' ) }} €</div><div class="ks-lbl">CPC</div></div>
        </div>
    </div>

    {{-- 5. GRAFİKLER: Kaynak + Pipeline Doughnut --}}
    <div class="grid2" style="align-items:stretch;">

        {{-- Kaynak Dağılımı --}}
        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
                Kaynak Dağılımı
                <span style="font-weight:400;font-size:var(--tx-xs);margin-left:6px;">lead sayısı</span>
            </div>
            @if(count($sourceSummary) > 0)
            <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
                <div style="position:relative;width:180px;height:180px;flex-shrink:0;">
                    <canvas id="chartSource"></canvas>
                    <div id="chartSourceCenter" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                        <div style="font-size:var(--tx-xl);font-weight:700;color:var(--u-brand,#1e40af);">{{ $kpis['lead_count'] ?? 0 }}</div>
                        <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">toplam</div>
                    </div>
                </div>
                <div style="flex:1;min-width:0;">
                    <div id="legendSource" class="ch-legend"></div>
                    <div style="margin-top:10px;">
                        <div class="tl-wrap">
                            <table class="tl-tbl">
                                <thead><tr><th>Kaynak</th><th style="text-align:right;width:60px;">Lead</th><th style="text-align:right;width:70px;">Dön.%</th></tr></thead>
                                <tbody>
                                @foreach($sourceSummary as $row)
                                @php $cr=(float)$row['conversion_rate']; @endphp
                                <tr>
                                    <td style="font-size:var(--tx-xs);">{{ $row['source'] }}</td>
                                    <td style="text-align:right;font-weight:600;font-size:var(--tx-xs);">{{ $row['lead_count'] }}</td>
                                    <td style="text-align:right;"><span class="badge {{ $cr>=20?'ok':($cr>=10?'warn':'info') }}" style="font-size:var(--tx-xs);">{{ number_format($cr,1) }}%</span></td>
                                </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div style="color:var(--u-muted,#64748b);font-size:var(--tx-sm);padding:16px 0;">Veri yok.</div>
            @endif
        </div>

        {{-- Pipeline Durum --}}
        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
                Pipeline Durum Dağılımı
            </div>
            @if(count($pipelineSummary) > 0)
            <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
                <div style="position:relative;width:180px;height:180px;flex-shrink:0;">
                    <canvas id="chartPipeline"></canvas>
                    <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                        <div style="font-size:var(--tx-xl);font-weight:700;color:var(--u-warn,#d97706);">{{ $kpis['open_guest_count'] ?? 0 }}</div>
                        <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">açık</div>
                    </div>
                </div>
                <div style="flex:1;min-width:0;">
                    <div id="legendPipeline" class="ch-legend"></div>
                    <div style="margin-top:10px;">
                        <div class="tl-wrap">
                            <table class="tl-tbl">
                                <thead><tr><th>Durum</th><th style="text-align:right;width:60px;">Sayı</th></tr></thead>
                                <tbody>
                                @foreach($pipelineSummary as $row)
                                <tr>
                                    <td style="font-size:var(--tx-xs);">{{ $row['status'] }}</td>
                                    <td style="text-align:right;font-weight:600;font-size:var(--tx-xs);color:{{ $statusColors[$row['status']] ?? 'var(--u-brand,#1e40af)' }};">{{ $row['count'] }}</td>
                                </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div style="color:var(--u-muted,#64748b);font-size:var(--tx-sm);padding:16px 0;">Veri yok.</div>
            @endif
        </div>

    </div>

    {{-- 6. TREND GRAFİĞİ --}}
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);">
                Dönem Trendi
                <span style="font-weight:400;font-size:var(--tx-xs);margin-left:6px;">{{ count($trend) }} dönem</span>
            </div>
            <div class="ch-tabs">
                <button class="ch-tab active" onclick="switchTrend('leadsConv',this)">Lead & Dönüşüm</button>
                <button class="ch-tab" onclick="switchTrend('revenue',this)">Gelir</button>
            </div>
        </div>
        @if(count($trend) > 0)
        <div style="position:relative;height:220px;">
            <canvas id="chartTrend"></canvas>
        </div>
        <div style="margin-top:12px;">
            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr>
                        <th>Tarih</th>
                        <th style="text-align:right;width:80px;">Lead</th>
                        <th style="text-align:right;width:100px;">Dönüştürülen</th>
                        <th style="text-align:right;width:120px;">Gelir</th>
                    </tr></thead>
                    <tbody>
                        @foreach($trend as $row)
                        <tr>
                            <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $row['label'] }}</td>
                            <td style="text-align:right;font-weight:600;">{{ $row['leads'] }}</td>
                            <td style="text-align:right;color:var(--u-ok,#16a34a);font-weight:600;">{{ $row['converted'] }}</td>
                            <td style="text-align:right;font-size:var(--tx-xs);">{{ number_format((float)$row['revenue'],2,'.',',' ) }} €</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div style="color:var(--u-muted,#64748b);font-size:var(--tx-sm);padding:16px 0;">Trend verisi yok.</div>
        @endif
    </div>

    {{-- 7. EXTERNAL PLATFORM --}}
    <div class="grid2" style="align-items:stretch;">
        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
                External Platform
                @if(count($externalProviderSummary) > 0)
                <span style="font-weight:400;font-size:var(--tx-xs);margin-left:6px;">harcama karşılaştırması</span>
                @endif
            </div>
            @if(count($externalProviderSummary) > 0)
            <div style="height:180px;position:relative;margin-bottom:10px;">
                <canvas id="chartExtProv"></canvas>
            </div>
            @endif
            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr><th>Platform</th><th style="text-align:right;">Harcama</th><th style="text-align:right;">Tıklama</th><th style="text-align:right;">Dönüşüm</th></tr></thead>
                    <tbody>
                        @forelse($externalProviderSummary as $row)
                        <tr>
                            <td style="font-weight:600;">{{ $row['provider'] }}</td>
                            <td style="text-align:right;color:var(--u-danger,#dc2626);font-weight:600;">{{ number_format((float)($row['spend']??0),2,'.',',' ) }} €</td>
                            <td style="text-align:right;">{{ number_format((float)($row['clicks']??0),0,'.',',' ) }}</td>
                            <td style="text-align:right;color:var(--u-ok,#16a34a);font-weight:600;">{{ number_format((float)($row['conversions']??0),0,'.',',' ) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--u-muted,#64748b);">External platform verisi yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">Top External Kampanyalar</div>
            <div class="tl-wrap">
                <table class="tl-tbl">
                    <thead><tr><th>Kampanya</th><th style="text-align:right;width:100px;">Harcama</th><th style="text-align:right;width:90px;">Dönüşüm</th></tr></thead>
                    <tbody>
                        @forelse($externalCampaignSummary as $row)
                        <tr>
                            <td style="font-size:var(--tx-xs);">{{ $row['campaign'] }}</td>
                            <td style="text-align:right;font-size:var(--tx-xs);color:var(--u-danger,#dc2626);">{{ number_format((float)($row['spend']??0),2,'.',',' ) }} €</td>
                            <td style="text-align:right;"><span class="badge ok" style="font-size:var(--tx-xs);">{{ number_format((float)($row['conversions']??0),0) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="text-align:center;padding:20px;color:var(--u-muted,#64748b);">Veri yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 8. SON RAPORLAR --}}
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);">Son Raporlar</div>
            <a class="btn alt" style="font-size:var(--tx-xs);padding:4px 12px;" href="/mktg-admin/reports">Tümünü Gör →</a>
        </div>
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th style="width:60px;">#</th><th>Tür</th><th>Dönem</th>
                    <th style="width:70px;text-align:center;">Tarih</th>
                    <th style="width:120px;text-align:right;">İndir</th>
                </tr></thead>
                <tbody>
                    @forelse($recentReports as $report)
                    <tr>
                        <td style="color:var(--u-muted,#64748b);font-size:var(--tx-xs);">#{{ $report->id }}</td>
                        <td><span class="badge info" style="font-size:var(--tx-xs);">{{ $report->report_type }}</span></td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ optional($report->period_start)->toDateString() }} – {{ optional($report->period_end)->toDateString() }}</td>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);text-align:center;">{{ optional($report->created_at)->format('d.m') }}</td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:4px;justify-content:flex-end;">
                                <a class="btn alt" style="font-size:var(--tx-xs);padding:3px 9px;" href="/mktg-admin/reports/{{ $report->id }}/download/csv">CSV</a>
                                <a class="btn alt" style="font-size:var(--tx-xs);padding:3px 9px;" href="/mktg-admin/reports/{{ $report->id }}/download/json">JSON</a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Henüz rapor yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 9. REHBER --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — KPI Dashboard</h3>
            <span class="det-chev">▼</span>
        </summary>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:var(--tx-sm);line-height:1.7;color:var(--u-muted,#64748b);">
            <div>
                <p style="margin:0 0 8px;font-weight:600;color:var(--u-text,#0f172a);">Metrik Eşikleri</p>
                <ul style="margin:0;padding-left:16px;">
                    <li><strong style="color:var(--u-text,#0f172a);">Dönüşüm %:</strong> ≥20% yeşil · ≥10% turuncu. Sektör ort. %15–25.</li>
                    <li><strong style="color:var(--u-text,#0f172a);">ROI:</strong> Yeşil = pozitif. (Gelir−Harcama)/Harcama×100.</li>
                    <li><strong style="color:var(--u-text,#0f172a);">CTR:</strong> %1–3 normal · %3+ iyi. CPA &lt; ortalama paket fiyatı olmalı.</li>
                </ul>
            </div>
            <div>
                <p style="margin:0 0 8px;font-weight:600;color:var(--u-text,#0f172a);">Rapor İş Akışı</p>
                <ul style="margin:0;padding-left:16px;">
                    <li>Dönem seç → <strong>Rapor Oluştur & Kaydet</strong>.</li>
                    <li>Kaydedilen raporları CSV veya JSON olarak indir.</li>
                    <li>External veri yoksa Entegrasyonlar menüsünden platform bağla.</li>
                </ul>
            </div>
        </div>
    </details>

</div>
</div>{{-- /kpi-content --}}

{{-- Chart.js --}}
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function() {
    const PALETTE = [
        '#1e40af','#16a34a','#d97706','#7c3aed','#0891b2',
        '#dc2626','#f59e0b','#10b981','#6366f1','#f97316',
        '#06b6d4','#84cc16','#a855f7','#14b8a6',
    ];
    const STATUS_COLORS = {
        new:'#1e40af', contacted:'#7c3aed', qualified:'#d97706',
        meeting_scheduled:'#0891b2', proposal_sent:'#f59e0b',
        converted:'#16a34a', lost:'#dc2626',
    };
    const defaults = { font:{family:'inherit'}, color:'#64748b' };
    Chart.defaults.font.family = 'inherit';
    Chart.defaults.color       = '#64748b';

    function makeLegend(containerId, labels, colors) {
        var el = document.getElementById(containerId);
        if (!el) return;
        el.innerHTML = labels.map(function(l, i) {
            return '<div class="ch-legend-item">'
                + '<div class="ch-legend-dot" style="background:' + (colors[i] || PALETTE[i % PALETTE.length]) + '"></div>'
                + '<span>' + l + '</span></div>';
        }).join('');
    }

    /* ---- Kaynak Doughnut ---- */
    var srcEl = document.getElementById('chartSource');
    if (srcEl && _kpiSources && _kpiSources.length) {
        var srcLabels = _kpiSources.map(function(r){ return r.label; });
        var srcVals   = _kpiSources.map(function(r){ return r.value; });
        var srcColors = srcLabels.map(function(_, i){ return PALETTE[i % PALETTE.length]; });
        new Chart(srcEl, {
            type: 'doughnut',
            data: { labels: srcLabels, datasets: [{ data: srcVals, backgroundColor: srcColors, borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }] },
            options: {
                cutout: '68%', responsive: true, maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function(ctx) { return ' ' + ctx.label + ': ' + ctx.parsed; } } }
                }
            }
        });
        makeLegend('legendSource', srcLabels, srcColors);
    }

    /* ---- Pipeline Doughnut ---- */
    var pipEl = document.getElementById('chartPipeline');
    if (pipEl && _kpiPipeline && _kpiPipeline.length) {
        var pipLabels = _kpiPipeline.map(function(r){ return r.label; });
        var pipVals   = _kpiPipeline.map(function(r){ return r.value; });
        var pipColors = pipLabels.map(function(l){ return STATUS_COLORS[l] || PALETTE[0]; });
        new Chart(pipEl, {
            type: 'doughnut',
            data: { labels: pipLabels, datasets: [{ data: pipVals, backgroundColor: pipColors, borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }] },
            options: {
                cutout: '68%', responsive: true, maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function(ctx) { return ' ' + ctx.label + ': ' + ctx.parsed; } } }
                }
            }
        });
        makeLegend('legendPipeline', pipLabels, pipColors);
    }

    /* ---- Trend Bar+Line ---- */
    var trendEl = document.getElementById('chartTrend');
    window.__trendChart = null;
    if (trendEl && _kpiTrend && _kpiTrend.length) {
        var tLabels = _kpiTrend.map(function(r){ return r.label; });
        window.__trendLeadsConvDatasets = [
            {
                label: 'Lead', type: 'bar',
                data: _kpiTrend.map(function(r){ return r.leads; }),
                backgroundColor: 'rgba(30,64,175,0.18)', borderColor: '#1e40af',
                borderWidth: 1.5, borderRadius: 4,
            },
            {
                label: 'Dönüştürülen', type: 'line',
                data: _kpiTrend.map(function(r){ return r.converted; }),
                borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.12)',
                borderWidth: 2, pointRadius: 3, tension: 0.3, fill: true,
            }
        ];
        window.__trendRevenueDatasets = [
            {
                label: 'Gelir (€)', type: 'bar',
                data: _kpiTrend.map(function(r){ return r.revenue; }),
                backgroundColor: 'rgba(22,163,74,0.2)', borderColor: '#16a34a',
                borderWidth: 1.5, borderRadius: 4,
            }
        ];
        var trendOpts = {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 11 }, maxRotation: 45 } },
                y: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 11 } }, beginAtZero: true }
            },
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
        };
        window.__trendChart = new Chart(trendEl, {
            type: 'bar',
            data: { labels: tLabels, datasets: window.__trendLeadsConvDatasets },
            options: trendOpts
        });
    }

    window.switchTrend = function(mode, btn) {
        document.querySelectorAll('.ch-tab').forEach(function(b){ b.classList.remove('active'); });
        btn.classList.add('active');
        if (!window.__trendChart) return;
        if (mode === 'revenue') {
            window.__trendChart.data.datasets = window.__trendRevenueDatasets;
        } else {
            window.__trendChart.data.datasets = window.__trendLeadsConvDatasets;
        }
        window.__trendChart.update();
    };

    /* ---- External Platform Bar ---- */
    var extEl = document.getElementById('chartExtProv');
    if (extEl && _kpiExtProv && _kpiExtProv.length) {
        var extLabels = _kpiExtProv.map(function(r){ return r.label; });
        var extSpend  = _kpiExtProv.map(function(r){ return r.spend; });
        var extClicks = _kpiExtProv.map(function(r){ return r.clicks; });
        new Chart(extEl, {
            type: 'bar',
            data: {
                labels: extLabels,
                datasets: [
                    { label: 'Harcama (€)', data: extSpend, backgroundColor: 'rgba(220,38,38,0.2)', borderColor: '#dc2626', borderWidth: 1.5, borderRadius: 4 },
                    { label: 'Tıklama', data: extClicks, backgroundColor: 'rgba(8,145,178,0.2)', borderColor: '#0891b2', borderWidth: 1.5, borderRadius: 4 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                    y: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 11 } }, beginAtZero: true }
                },
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
            }
        });
    }
})();

/* Filter shortcuts */
function setRange(days) {
    var e = new Date(), s = new Date(); s.setDate(e.getDate()-days+1);
    document.getElementById('start_date').value = s.toISOString().split('T')[0];
    document.getElementById('end_date').value   = e.toISOString().split('T')[0];
}
function setThisMonth() {
    var d = new Date();
    document.getElementById('start_date').value = new Date(d.getFullYear(),d.getMonth(),1).toISOString().split('T')[0];
    document.getElementById('end_date').value   = d.toISOString().split('T')[0];
}
function setLastMonth() {
    var d = new Date();
    document.getElementById('start_date').value = new Date(d.getFullYear(),d.getMonth()-1,1).toISOString().split('T')[0];
    document.getElementById('end_date').value   = new Date(d.getFullYear(),d.getMonth(),0).toISOString().split('T')[0];
}
</script>

{{-- KPI Gerçek Zamanlı Yenileme --}}
<script>
(function(){
    var INTERVAL = 30000;
    var timer;
    var lastUpdated = new Date();

    function updateTimestamp(){
        var el = document.getElementById('kpi-last-updated');
        if(!el) return;
        var diff = Math.round((new Date() - lastUpdated) / 1000);
        el.textContent = diff < 5 ? 'Az önce güncellendi' : diff + ' saniye önce güncellendi';
    }

    function refreshKpi(){
        var btn = document.getElementById('kpi-refresh-btn');
        if(btn){ btn.disabled = true; btn.textContent = 'Yükleniyor...'; }

        fetch(window.location.href, {headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'}})
            .then(function(r){ return r.text(); })
            .then(function(html){
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newContent = doc.getElementById('kpi-content');
                var oldContent = document.getElementById('kpi-content');
                if(newContent && oldContent) oldContent.innerHTML = newContent.innerHTML;
                lastUpdated = new Date();
                if(btn){ btn.disabled = false; btn.textContent = 'Yenile'; }
            })
            .catch(function(){
                if(btn){ btn.disabled = false; btn.textContent = 'Yenile'; }
            });
    }

    window.refreshKpi = refreshKpi;

    setInterval(updateTimestamp, 10000);
    timer = setInterval(refreshKpi, INTERVAL);

    document.addEventListener('visibilitychange', function(){
        if(document.visibilityState === 'visible') refreshKpi();
    });
})();
</script>
@endsection
