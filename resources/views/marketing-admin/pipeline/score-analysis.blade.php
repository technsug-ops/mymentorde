@extends('marketing-admin.layouts.app')

@section('title', 'Score Analizi')
@section('page_subtitle', 'Score Analizi — tier bazli donusum orani, dagilim ve aksiyon sinyalleri')

@section('topbar-actions')
@php $pipelineIsAdmin = in_array(auth()->user()?->role, ['marketing_admin','sales_admin','manager','system_admin']); @endphp
<a class="btn {{ request()->is('mktg-admin/pipeline') && !request()->is('mktg-admin/pipeline/*') ? '' : 'alt' }}" href="/mktg-admin/pipeline" style="font-size:var(--tx-xs);padding:6px 12px;">Genel Bakış</a>
@if($pipelineIsAdmin)
<a class="btn {{ request()->is('mktg-admin/pipeline/value') ? '' : 'alt' }}" href="/mktg-admin/pipeline/value" style="font-size:var(--tx-xs);padding:6px 12px;">Pipeline Value</a>
<a class="btn {{ request()->is('mktg-admin/pipeline/loss-analysis') ? '' : 'alt' }}" href="/mktg-admin/pipeline/loss-analysis" style="font-size:var(--tx-xs);padding:6px 12px;">Loss Analysis</a>
<a class="btn {{ request()->is('mktg-admin/pipeline/conversion-time') ? '' : 'alt' }}" href="/mktg-admin/pipeline/conversion-time" style="font-size:var(--tx-xs);padding:6px 12px;">Conversion Time</a>
@endif
<a class="btn {{ request()->is('mktg-admin/pipeline/re-engagement') ? '' : 'alt' }}" href="/mktg-admin/pipeline/re-engagement" style="font-size:var(--tx-xs);padding:6px 12px;">Re-Engagement</a>
@if($pipelineIsAdmin)
<a class="btn {{ request()->is('mktg-admin/pipeline/score-analysis') ? '' : 'alt' }}" href="/mktg-admin/pipeline/score-analysis" style="font-size:var(--tx-xs);padding:6px 12px;">Score Analizi</a>
@endif
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

.pl-stats { display:flex; gap:0; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; overflow:hidden; background:var(--u-card,#fff); }
.pl-stat  { flex:1; padding:14px 18px; border-right:1px solid var(--u-line,#e2e8f0); min-width:0; }
.pl-stat:last-child { border-right:none; }
.pl-val   { font-size:26px; font-weight:700; line-height:1.1; color:var(--u-brand,#1e40af); }
.pl-lbl   { font-size:11px; color:var(--u-muted,#64748b); margin-top:3px; }

.tl-wrap { overflow-x:auto; border:1px solid var(--u-line,#e2e8f0); border-radius:10px; }
.tl-tbl  { width:100%; border-collapse:collapse; }
.tl-tbl th { text-align:left; padding:9px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); border-bottom:1px solid var(--u-line,#e2e8f0); }
.tl-tbl td { padding:9px 12px; font-size:13px; border-bottom:1px solid var(--u-line,#e2e8f0); vertical-align:middle; }
.tl-tbl tr:last-child td { border-bottom:none; }

/* Tier conversion rows */
.tier-conv-row { display:grid; grid-template-columns:130px 1fr 90px 90px 120px 160px; gap:0; align-items:center; padding:12px 16px; border-bottom:1px solid var(--u-line,#e2e8f0); }
.tier-conv-row:last-child { border-bottom:none; }
.tier-conv-row-head { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff)); border-bottom:1px solid var(--u-line,#e2e8f0); padding:9px 16px; border-radius:10px 10px 0 0; }
.tier-conv-name { display:flex; align-items:center; gap:8px; }
.tier-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.conv-bar-bg   { background:var(--u-line,#e2e8f0); border-radius:4px; height:6px; overflow:hidden; }
.conv-bar-fill { height:100%; border-radius:4px; transition:width .4s ease; }
.signal { display:flex; align-items:center; gap:5px; font-size:11px; font-weight:600; }

/* Score bar in top 20 */
.score-bar-bg   { background:var(--u-line,#e2e8f0); border-radius:3px; height:4px; overflow:hidden; margin-top:3px; }
.score-bar-fill { height:100%; border-radius:3px; }

@media(max-width:900px) {
    .tier-conv-row { grid-template-columns:1fr 1fr; gap:8px; }
    .tier-conv-row-head { display:none; }
}
</style>

@php
$tierMeta = [
    'Cold (0-19)'        => ['color'=>'#64748b', 'signal'=>'nurture',    'signal_label'=>'Besle',        'signal_color'=>'var(--u-muted,#64748b)'],
    'Warm (20-49)'       => ['color'=>'#7c3aed', 'signal'=>'engage',     'signal_label'=>'Temas Kur',    'signal_color'=>'#7c3aed'],
    'Hot (50-79)'        => ['color'=>'#d97706', 'signal'=>'push',       'signal_label'=>'İlerlet',      'signal_color'=>'var(--u-warn,#d97706)'],
    'Sales Ready (80-99)'=> ['color'=>'#0891b2', 'signal'=>'close',      'signal_label'=>'Kapat!',       'signal_color'=>'#0891b2'],
    'Champion (100+)'    => ['color'=>'#16a34a', 'signal'=>'priority',   'signal_label'=>'Öncelikli',    'signal_color'=>'var(--u-ok,#16a34a)'],
];

$totalLeads   = collect($scoreRows)->sum('total');
$totalConv    = collect($scoreRows)->sum('converted');
$hotPlusTotal = collect($scoreRows)->whereIn('label', ['Hot (50-79)','Sales Ready (80-99)','Champion (100+)'])->sum('total');
$hotPlusPct   = $totalLeads > 0 ? round($hotPlusTotal / $totalLeads * 100) : 0;
$overallConv  = $totalLeads > 0 ? round($totalConv / $totalLeads * 100, 1) : 0;
$champRow     = collect($scoreRows)->firstWhere('label', 'Champion (100+)');
$champConv    = $champRow['conv_rate'] ?? 0;

$maxTotal = collect($scoreRows)->max('total') ?: 1;
$maxConv  = $topLeads->max('lead_score') ?: 100;
@endphp

<div style="display:grid;gap:12px;">

    {{-- KPI Bar --}}
    <div class="pl-stats">
        <div class="pl-stat">
            <div class="pl-val">{{ $avgScore }}</div>
            <div class="pl-lbl">Ort. Lead Score</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val">{{ $medScore }}</div>
            <div class="pl-lbl">Medyan Score</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:var(--u-warn,#d97706);">{{ $hotPlusTotal }}</div>
            <div class="pl-lbl">Hot+ Lead (50+) · %{{ $hotPlusPct }}</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:var(--u-ok,#16a34a);">{{ $overallConv }}%</div>
            <div class="pl-lbl">Genel Dönüşüm</div>
        </div>
        <div class="pl-stat">
            <div class="pl-val" style="color:#16a34a;">{{ $champConv }}%</div>
            <div class="pl-lbl">Champion Dönüşüm</div>
        </div>
    </div>

    {{-- Tier Bazlı Dönüşüm Analizi --}}
    <div class="card" style="padding:0;overflow:hidden;">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);padding:14px 16px 10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            Tier Bazlı Dönüşüm Analizi
        </div>
        {{-- Header --}}
        <div style="display:grid;grid-template-columns:130px 1fr 90px 90px 120px 160px;gap:0;padding:9px 16px;background:color-mix(in srgb,var(--u-brand,#1e40af) 4%,var(--u-card,#fff));border-bottom:1px solid var(--u-line,#e2e8f0);">
            <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);">Tier</div>
            <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);">Dağılım</div>
            <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);text-align:right;">Lead</div>
            <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);text-align:right;">Onaylanan</div>
            <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);text-align:right;">Dönüşüm %</div>
            <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);text-align:right;">Aksiyon</div>
        </div>
        @foreach($scoreRows as $row)
        @php
            $meta    = $tierMeta[$row['label']] ?? ['color'=>'var(--u-brand)','signal_label'=>'—','signal_color'=>'var(--u-brand)'];
            $barPct  = $maxTotal > 0 ? round($row['total'] / $maxTotal * 100) : 0;
            $badgeT  = $row['conv_rate'] >= 30 ? 'ok' : ($row['conv_rate'] >= 15 ? 'warn' : 'info');
        @endphp
        <div style="display:grid;grid-template-columns:130px 1fr 90px 90px 120px 160px;gap:0;padding:11px 16px;border-bottom:1px solid var(--u-line,#e2e8f0);align-items:center;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:10px;height:10px;border-radius:50%;background:{{ $meta['color'] }};flex-shrink:0;"></div>
                <span style="font-size:var(--tx-sm);font-weight:600;">{{ $row['label'] }}</span>
            </div>
            <div style="padding-right:16px;">
                <div class="conv-bar-bg">
                    <div class="conv-bar-fill" style="width:{{ $barPct }}%;background:{{ $meta['color'] }};"></div>
                </div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);margin-top:2px;">{{ $barPct }}% of max</div>
            </div>
            <div style="text-align:right;font-weight:700;font-size:var(--tx-base);color:{{ $meta['color'] }};">{{ $row['total'] }}</div>
            <div style="text-align:right;color:var(--u-ok,#16a34a);font-weight:600;">{{ $row['converted'] }}</div>
            <div style="text-align:right;">
                <span class="badge {{ $badgeT }}">{{ $row['conv_rate'] }}%</span>
            </div>
            <div style="text-align:right;">
                <span style="display:inline-flex;align-items:center;gap:4px;font-size:var(--tx-xs);font-weight:700;color:{{ $meta['signal_color'] }};background:color-mix(in srgb,{{ $meta['color'] }} 10%,var(--u-card,#fff));padding:3px 8px;border-radius:6px;border:1px solid color-mix(in srgb,{{ $meta['color'] }} 20%,transparent);">
                    {{ $meta['signal_label'] }}
                </span>
            </div>
        </div>
        @endforeach
        {{-- Toplam --}}
        <div style="display:grid;grid-template-columns:130px 1fr 90px 90px 120px 160px;gap:0;padding:10px 16px;background:color-mix(in srgb,var(--u-brand,#1e40af) 3%,var(--u-card,#fff));">
            <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted,#64748b);">TOPLAM</div>
            <div></div>
            <div style="text-align:right;font-weight:700;font-size:var(--tx-sm);">{{ $totalLeads }}</div>
            <div style="text-align:right;font-weight:700;font-size:var(--tx-sm);color:var(--u-ok,#16a34a);">{{ $totalConv }}</div>
            <div style="text-align:right;font-weight:700;font-size:var(--tx-sm);">{{ $overallConv }}%</div>
            <div></div>
        </div>
    </div>

    {{-- Top 20 --}}
    <div class="card">
        <div style="font-weight:700;font-size:var(--tx-sm);text-transform:uppercase;letter-spacing:.04em;color:var(--u-muted,#64748b);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            En Yüksek Puanlı Adaylar
            <span style="font-weight:400;font-size:var(--tx-xs);margin-left:6px;">Top 20 — Öncelikli Temas Listesi</span>
        </div>
        <div class="tl-wrap">
            <table class="tl-tbl">
                <thead><tr>
                    <th style="width:36px;">#</th>
                    <th>Aday</th>
                    <th style="width:200px;">Puan</th>
                    <th style="width:120px;text-align:center;">Tier</th>
                    <th style="width:140px;text-align:right;">Sözleşme</th>
                </tr></thead>
                <tbody>
                    @forelse($topLeads as $i => $lead)
                    @php
                        $tier = $lead->lead_score_tier ?? 'cold';
                        $tColors = ['champion'=>'#16a34a','sales_ready'=>'#0891b2','hot'=>'#d97706','warm'=>'#7c3aed','cold'=>'#64748b'];
                        $tBadges = ['champion'=>'ok','sales_ready'=>'info','hot'=>'warn','warm'=>'info','cold'=>'pending'];
                        $barColor = $tColors[$tier] ?? 'var(--u-brand)';
                        $badgeType = $tBadges[$tier] ?? 'info';
                        $barPct = $maxConv > 0 ? min(100, round($lead->lead_score / $maxConv * 100)) : 0;
                        $rank = $i + 1;
                    @endphp
                    <tr>
                        <td style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);font-weight:700;">
                            @if($rank === 1) 🥇
                            @elseif($rank === 2) 🥈
                            @elseif($rank === 3) 🥉
                            @else {{ $rank }}
                            @endif
                        </td>
                        <td>
                            <a href="/mktg-admin/scoring/{{ $lead->id }}/history"
                               style="font-weight:600;color:var(--u-text,#0f172a);text-decoration:none;">
                                {{ $lead->first_name }} {{ $lead->last_name }}
                            </a>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="font-weight:700;font-size:var(--tx-base);color:{{ $barColor }};min-width:32px;">{{ $lead->lead_score }}</span>
                                <div style="flex:1;">
                                    <div class="score-bar-bg">
                                        <div class="score-bar-fill" style="width:{{ $barPct }}%;background:{{ $barColor }};"></div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge {{ $badgeType }}">{{ $tier }}</span>
                        </td>
                        <td style="text-align:right;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $lead->contract_status ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--u-muted,#64748b);">Veri yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Rehber --}}
    <details class="card">
        <summary class="det-sum">
            <h3>📖 Kullanım Kılavuzu — Score Analizi</h3>
            <span class="det-chev">▼</span>
        </summary>
        <ol style="margin:0;padding-left:18px;font-size:var(--tx-sm);color:var(--u-muted,#64748b);line-height:1.7;">
            <li><strong>Tier Dağılımı:</strong> Bar uzunluğu o tier'daki lead sayısını gösterir. En kalabalık tier hangisi? Çoğu lead Cold/Warm'da yığılıyorsa scoring veya lead kalitesi sorunu var.</li>
            <li><strong>Dönüşüm % hedefleri:</strong> Cold &lt;5% normal · Warm %10–20 · Hot %25–40 · Sales Ready %50+ · Champion %70+ — bu bantların altındaysa tier'a özel aksiyon şart.</li>
            <li><strong>Aksiyon sinyalleri:</strong> Besle (içerik/bilgi), Temas Kur (sıcak temas), İlerlet (demo/teklif), Kapat (sözleşmeye yönlendir), Öncelikli (kaynak harca).</li>
            <li><strong>Champion Dönüşüm düşükse:</strong> En yüksek puanlı leadler kapanmıyor — senior aksiyon zamanlaması veya teklif kalitesi sorunlu.</li>
            <li>Scoring kural kalibrasyonu için <a href="/mktg-admin/scoring/config" style="color:var(--u-brand,#1e40af);">Scoring → Kural Yapılandırma</a> sayfasını kullan.</li>
        </ol>
    </details>

</div>
@endsection
