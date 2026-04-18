@extends('marketing-admin.layouts.app')

@section('title', 'Marketing Dashboard')

@section('page_subtitle', 'Marketing Dashboard — kampanya, lead ve dönüşüm özeti')

@section('content')
<style>
/* ── Hero ── */
.mkd-hero {
    background: var(--hero-gradient, linear-gradient(to right, var(--u-brand-2,#6d28d9), var(--u-brand,#7c3aed)));
    border-radius: 14px;
    padding: 26px 28px 22px;
    position: relative; overflow: hidden; margin-bottom: 16px;
}
.mkd-hero::before {
    content:''; position:absolute; top:-50px; right:-50px;
    width:240px; height:240px; border-radius:50%;
    background:rgba(255,255,255,.05); pointer-events:none;
}
.mkd-hero-top { display:flex; align-items:center; gap:16px; flex-wrap:wrap; position:relative; z-index:1; margin-bottom:14px; }
.mkd-avatar { width:52px; height:52px; border-radius:50%; background:rgba(255,255,255,.15); border:2px solid rgba(255,255,255,.35); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:18px; flex-shrink:0; }
.mkd-hero-name  { font-size:18px; font-weight:700; color:#fff; margin-bottom:4px; }
.mkd-hero-badge { display:inline-block; background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); border-radius:999px; padding:2px 10px; font-size:11px; color:#fff; font-weight:600; margin-right:4px; }
.mkd-hero-stats { display:flex; gap:18px; flex-wrap:wrap; margin-left:auto; flex-shrink:0; }
.mkd-hstat { text-align:center; }
.mkd-hstat-val   { font-size:18px; font-weight:700; color:#fff; line-height:1; margin-bottom:2px; }
.mkd-hstat-label { font-size:10px; color:rgba(255,255,255,.65); }
.mkd-hstat-sep   { width:1px; background:rgba(255,255,255,.2); align-self:stretch; }
.mkd-hero-actions { display:flex; gap:6px; flex-wrap:wrap; position:relative; z-index:1; }
.mkd-btn { padding:7px 14px; border-radius:8px; font-size:12px; font-weight:600; text-decoration:none; border:none; cursor:pointer; transition:all .15s; }
.mkd-btn.primary { background:#fff; color:var(--u-brand,#1e40af); }
.mkd-btn.ghost   { background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.25); }
.mkd-btn.ghost:hover { background:rgba(255,255,255,.22); }

/* ── Company row ── */
.mkd-company-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; position:relative; z-index:1; margin-bottom:12px; }
.mkd-company-row select { background:rgba(255,255,255,.12)!important; border:1px solid rgba(255,255,255,.3)!important; border-radius:8px!important; color:#fff!important; font-size:12px; padding:5px 10px; min-width:200px; cursor:pointer; }
.mkd-company-row select option { background:var(--u-brand,#6d28d9); color:#fff; }
.mkd-company-label { font-size:12px; color:rgba(255,255,255,.7); font-weight:500; }

/* ── Mode tabs ── */
.mkd-mode-tabs { display:flex; gap:8px; align-items:center; margin-bottom:16px; }
.mkd-mode-tab { padding:8px 20px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; border:1px solid var(--u-line,#e2e8f0); color:var(--u-muted,#64748b); background:var(--u-card,#fff); transition:all .15s; }
.mkd-mode-tab.active { background:var(--u-brand,#1e40af); color:#fff; border-color:var(--u-brand,#1e40af); }
.mkd-mode-tab:hover:not(.active) { border-color:var(--u-brand,#1e40af); color:var(--u-brand,#1e40af); }

/* ── KPI strip ── */
.mkd-kpis { display:grid; grid-template-columns:repeat(6,1fr); gap:10px; margin-bottom:16px; }
@media(max-width:1100px){ .mkd-kpis{ grid-template-columns:repeat(3,1fr); } }
@media(max-width:700px) { .mkd-kpis{ grid-template-columns:repeat(2,1fr); } }
.mkd-kpi { background:var(--u-card,#fff); border:1px solid var(--u-line,#e2e8f0); border-top:3px solid var(--u-line,#e2e8f0); border-radius:12px; padding:14px 16px; }
.mkd-kpi.c1{ border-top-color:var(--u-brand,#1e40af); }
.mkd-kpi.c2{ border-top-color:#16a34a; }
.mkd-kpi.c3{ border-top-color:#0891b2; }
.mkd-kpi.c4{ border-top-color:#d97706; }
.mkd-kpi.c5{ border-top-color:#7c3aed; }
.mkd-kpi.c6{ border-top-color:#dc2626; }
.mkd-kpi-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--u-muted,#64748b); margin-bottom:5px; }
.mkd-kpi-val   { font-size:24px; font-weight:900; color:var(--u-text,#0f172a); line-height:1; }
.mkd-kpi-delta { font-size:11px; margin-top:4px; }

/* ── Quick grid ── */
.mkd-quick-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:8px; margin-bottom:16px; }
@media(max-width:900px){ .mkd-quick-grid{ grid-template-columns:repeat(3,1fr); } }
@media(max-width:600px){ .mkd-quick-grid{ grid-template-columns:repeat(2,1fr); } }
.mkd-quick-link { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:7px; padding:14px 8px 12px; background:var(--u-card,#fff); border:1px solid var(--u-line,#e2e8f0); border-radius:12px; text-decoration:none; color:var(--u-text,#374151); font-size:12px; font-weight:600; text-align:center; transition:all .12s; }
.mkd-quick-link:hover { background:color-mix(in srgb,var(--u-brand,#1e40af) 6%,var(--u-card,#fff)); border-color:color-mix(in srgb,var(--u-brand,#1e40af) 40%,#fff); color:var(--u-brand,#1e40af); transform:translateY(-2px); text-decoration:none; }
.mkd-quick-icon { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:800; color:#fff; flex-shrink:0; }

/* ── Content cards ── */
.mkd-card { background:var(--u-card,#fff); border:1px solid var(--u-line,#e2e8f0); border-radius:12px; overflow:hidden; }
.mkd-card-head { padding:14px 20px; border-bottom:1px solid var(--u-line,#e2e8f0); display:flex; align-items:center; justify-content:space-between; gap:8px; }
.mkd-card-head h3 { margin:0; font-size:14px; font-weight:700; color:var(--u-text,#0f172a); }
.mkd-card-body { padding:20px; }

/* ── Source table ── */
.mkd-src-row { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid var(--u-line,#e2e8f0); }
.mkd-src-row:last-child { border-bottom:none; }
.mkd-src-name { flex:2; font-size:13px; font-weight:600; color:var(--u-text,#0f172a); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* hide default top bar on dashboard */
.top { display:none!important; }
</style>

@php
    $bm       = $benchmark ?? [];
    $bmGuests = $bm['guests']      ?? null;
    $bmConv   = $bm['conversions'] ?? null;
    $bmRate   = $bm['conv_rate']   ?? null;
    $bmSpend  = $bm['spend']       ?? null;
    $bmExtConv= $bm['ext_conv']    ?? null;
    $mkdName  = auth()->user()?->name ?? 'Marketing';
    $mkdInit  = strtoupper(substr($mkdName, 0, 2));
    $mkdRole  = (string) auth()->user()?->role;
    $donutTotal = 0;
    $donutSegments = [];
    if (!empty($sourcePerformance)) {
        $donutColors = ['#1e40af','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#84cc16'];
        $donutR = 90; $donutCx = 120; $donutCy = 120;
        $donutCirc = 2 * M_PI * $donutR;
        $donutTotal = array_sum(array_column($sourcePerformance, 'guest_count'));
        $gap = 3; // px gap between segments
        $offset = $donutCirc * 0.25; $cumulative = 0;
        foreach ($sourcePerformance as $i => $row) {
            $frac = $donutTotal > 0 ? $row['guest_count'] / $donutTotal : 0;
            $dash = max(0, $frac * $donutCirc - $gap);
            $off  = $donutCirc - $cumulative * $donutCirc + $offset;
            $donutSegments[] = [
                'color'  => $donutColors[$i % count($donutColors)],
                'dash'   => $dash,
                'gapDash'=> $donutCirc - $dash,
                'offset' => $off,
                'source' => $row['source'], 'count' => $row['guest_count'],
                'pct'    => $donutTotal > 0 ? round($row['guest_count'] / $donutTotal * 100) : 0,
            ];
            $cumulative += $frac;
        }
    }
@endphp

{{-- Hero --}}
<div class="mkd-hero">
    <div class="mkd-company-row">
        <span class="mkd-company-label">Aktif Firma</span>
        <select id="mktgCompanySwitch"><option value="">Yükleniyor...</option></select>
        <button type="button" id="mktgCompanyReload" class="mkd-btn ghost" style="padding:5px 12px;font-size:var(--tx-xs);">Yenile</button>
        <span id="mktgCompanyStatus" class="mkd-company-label"></span>
    </div>
    <div class="mkd-hero-top">
        <div class="mkd-avatar">{{ $mkdInit }}</div>
        <div>
            <div class="mkd-hero-name">{{ $mkdName }}</div>
            <span class="mkd-hero-badge" style="text-transform:capitalize;">{{ $mkdRole }}</span>
            <span class="mkd-hero-badge">Son 30 Gün</span>
        </div>
        <div class="mkd-hero-stats">
            <div class="mkd-hstat">
                <div class="mkd-hstat-val">{{ number_format((float)($kpis['guest_count'] ?? 0)) }}</div>
                <div class="mkd-hstat-label">Yeni Aday Öğrenci</div>
            </div>
            <div class="mkd-hstat-sep"></div>
            <div class="mkd-hstat">
                <div class="mkd-hstat-val">{{ number_format((float)($kpis['conversion_rate'] ?? 0), 1) }}%</div>
                <div class="mkd-hstat-label">Dönüşüm</div>
            </div>
            <div class="mkd-hstat-sep"></div>
            <div class="mkd-hstat">
                <div class="mkd-hstat-val">{{ number_format((float)($kpis['roi'] ?? 0), 1) }}%</div>
                <div class="mkd-hstat-label">ROI</div>
            </div>
        </div>
    </div>
    <div class="mkd-hero-actions">
        <a class="mkd-btn primary" href="/mktg-admin/campaigns">Kampanyalar</a>
        <a class="mkd-btn ghost"   href="/mktg-admin/pipeline">Pipeline</a>
        <a class="mkd-btn ghost"   href="/mktg-admin/lead-sources">Lead Kaynakları</a>
        <a class="mkd-btn ghost"   href="/mktg-admin/kpi">KPI Dashboard</a>
        <a class="mkd-btn ghost"   href="/mktg-admin/content">CMS İçerik</a>
        <a class="mkd-btn ghost"   href="/mktg-admin/social/metrics">Sosyal Medya</a>
    </div>
</div>

{{-- Duyuru Tab --}}
<div class="mkd-mode-tabs" id="mkd-tabs">
    <button class="mkd-mode-tab" id="tab-blt" onclick="switchMkdTab('blt',this)"
            style="display:flex;align-items:center;gap:6px;cursor:pointer;margin-left:auto;">
        📢 Duyurular
        @if(($bulletinUnread ?? 0) > 0)
        <span style="background:#ef4444;color:#fff;font-size:10px;font-weight:800;border-radius:999px;padding:1px 7px;line-height:16px;">{{ $bulletinUnread }}</span>
        @endif
    </button>
</div>

{{-- Duyuru panosu (gizli, tab'a tıklayınca yükle) --}}
<div id="panel-blt" style="display:none;"></div>

<div id="dash-main-content">

{{-- KPI --}}
<div class="mkd-kpis">
    <div class="mkd-kpi c1">
        <div class="mkd-kpi-label">Son 30g Aday Öğrenci</div>
        <div class="mkd-kpi-val">{{ number_format((float)($kpis['guest_count'] ?? 0)) }}</div>
        @if($bmGuests)
        <div class="mkd-kpi-delta">
            <span style="color:{{ $bmGuests['up'] ? '#15803d' : '#b91c1c' }}">{{ $bmGuests['up'] ? '↑' : '↓' }} {{ abs($bmGuests['delta']) }}%</span>
            <span style="color:var(--u-muted,#64748b);font-size:var(--tx-xs);"> önceki: {{ (int)$bmGuests['prev'] }}</span>
        </div>
        @endif
    </div>
    <div class="mkd-kpi c2">
        <div class="mkd-kpi-label">Dönüşüm Oranı</div>
        <div class="mkd-kpi-val">{{ number_format((float)($kpis['conversion_rate'] ?? 0), 1) }}%</div>
        @if($bmRate)
        <div class="mkd-kpi-delta">
            <span style="color:{{ $bmRate['up'] ? '#15803d' : '#b91c1c' }}">{{ $bmRate['up'] ? '↑' : '↓' }} {{ abs($bmRate['delta']) }}%</span>
        </div>
        @endif
    </div>
    <div class="mkd-kpi c3">
        <div class="mkd-kpi-label">CPA</div>
        <div class="mkd-kpi-val" style="font-size:var(--tx-lg);">{{ number_format((float)($kpis['cpa'] ?? 0), 2) }} €</div>
    </div>
    <div class="mkd-kpi c4">
        <div class="mkd-kpi-label">Kampanya ROI</div>
        <div class="mkd-kpi-val">{{ number_format((float)($kpis['roi'] ?? 0), 1) }}%</div>
    </div>
    <div class="mkd-kpi c5">
        <div class="mkd-kpi-label">Ext. Spend (30g)</div>
        <div class="mkd-kpi-val" style="font-size:var(--tx-lg);">{{ number_format((float)($kpis['external_spend'] ?? 0), 0) }} €</div>
        @if($bmSpend)
        <div class="mkd-kpi-delta">
            <span style="color:{{ $bmSpend['up'] ? '#b91c1c' : '#15803d' }}">{{ $bmSpend['up'] ? '↑' : '↓' }} {{ abs($bmSpend['delta']) }}%</span>
        </div>
        @endif
    </div>
    <div class="mkd-kpi c6">
        <div class="mkd-kpi-label">Ext. Konversiyon</div>
        <div class="mkd-kpi-val">{{ number_format((float)($kpis['external_conversions'] ?? 0)) }}</div>
        @if($bmExtConv)
        <div class="mkd-kpi-delta">
            <span style="color:{{ $bmExtConv['up'] ? '#15803d' : '#b91c1c' }}">{{ $bmExtConv['up'] ? '↑' : '↓' }} {{ abs($bmExtConv['delta']) }}%</span>
        </div>
        @endif
    </div>
</div>

{{-- Hızlı Erişim --}}
<div class="mkd-quick-grid">
    <a class="mkd-quick-link" href="/mktg-admin/campaigns">
        <span class="mkd-quick-icon" style="background:#1e40af;">📣</span>Kampanyalar
    </a>
    <a class="mkd-quick-link" href="/mktg-admin/pipeline">
        <span class="mkd-quick-icon" style="background:#0891b2;">📈</span>Pipeline
    </a>
    <a class="mkd-quick-link" href="/mktg-admin/content">
        <span class="mkd-quick-icon" style="background:#7c3aed;">📝</span>İçerik
    </a>
    <a class="mkd-quick-link" href="/mktg-admin/tasks">
        <span class="mkd-quick-icon" style="background:#059669;">✅</span>Görevler
    </a>
    <a class="mkd-quick-link" href="/mktg-admin/email/campaigns">
        <span class="mkd-quick-icon" style="background:#d97706;">✉️</span>E-posta
    </a>
    <a class="mkd-quick-link" href="/mktg-admin/kpi">
        <span class="mkd-quick-icon" style="background:#dc2626;">📊</span>KPI Rapor
    </a>
</div>

{{-- İçerik: Kaynak + UTM --}}
<div class="grid2" style="gap:14px;">

    {{-- Kaynak Donut --}}
    <div class="mkd-card">
        <div class="mkd-card-head">
            <h3>📊 Kaynak Performansı</h3>
            @if($donutTotal > 0)
                <span style="font-size:var(--tx-xs);color:var(--u-muted,#64748b);">{{ $donutTotal }} toplam</span>
            @endif
        </div>
        @if(!empty($donutSegments))
        <div style="display:flex;align-items:stretch;">
            <div style="flex:1.4;display:flex;align-items:center;justify-content:center;padding:20px 12px;">
                <svg viewBox="0 0 240 240" style="width:100%;height:auto;display:block;">
                    @foreach($donutSegments as $seg)
                    <circle cx="{{ $donutCx }}" cy="{{ $donutCy }}" r="{{ $donutR }}"
                        fill="none" stroke="{{ $seg['color'] }}" stroke-width="40"
                        stroke-dasharray="{{ number_format($seg['dash'],4,'.','') }} {{ number_format($seg['gapDash'],4,'.','') }}"
                        stroke-dashoffset="{{ number_format($seg['offset'],4,'.','') }}"
                        stroke-linecap="butt"/>
                    @endforeach
                    <text x="{{ $donutCx }}" y="{{ $donutCy - 8 }}" text-anchor="middle" font-size="30" font-weight="700" fill="#1e3a5f">{{ $donutTotal }}</text>
                    <text x="{{ $donutCx }}" y="{{ $donutCy + 16 }}" text-anchor="middle" font-size="12" fill="#6b7280">toplam lead</text>
                </svg>
            </div>
            <div style="flex:1;padding:16px 18px;overflow:hidden;border-left:1px solid var(--u-line,#e2e8f0);">
                @foreach($donutSegments as $seg)
                <div class="mkd-src-row">
                    <span style="width:10px;height:10px;border-radius:50%;background:{{ $seg['color'] }};flex-shrink:0;"></span>
                    <span class="mkd-src-name" title="{{ $seg['source'] }}">{{ $seg['source'] ?: '(doğrudan)' }}</span>
                    <span style="font-size:var(--tx-xs);color:var(--u-text,#0f172a);font-weight:600;">{{ $seg['count'] }}</span>
                    <span style="font-size:var(--tx-xs);font-weight:700;color:{{ $seg['color'] }};width:32px;text-align:right;">{{ $seg['pct'] }}%</span>
                </div>
                @endforeach
                <div style="margin-top:10px;display:flex;gap:5px;flex-wrap:wrap;">
                    <span style="font-size:var(--tx-xs);background:rgba(8,145,178,.1);color:#0e7490;padding:2px 8px;border-radius:999px;">Verified: {{ number_format((float)($kpis['verified_count'] ?? 0)) }}</span>
                    <span style="font-size:var(--tx-xs);background:rgba(8,145,178,.1);color:#0e7490;padding:2px 8px;border-radius:999px;">Kampanya: {{ number_format((float)($kpis['campaign_count'] ?? 0)) }}</span>
                </div>
            </div>
        </div>
        @else
        <div style="padding:32px 20px;text-align:center;color:var(--u-muted,#64748b);font-size:var(--tx-sm);">Kaynak verisi yok.</div>
        @endif
    </div>

    {{-- UTM + External --}}
    <div class="mkd-card">
        <div class="mkd-card-head">
            <h3>🎯 UTM Kampanya Dağılımı</h3>
        </div>
        <div class="mkd-card-body" style="padding-bottom:0;">
            @if(!empty($topCampaigns))
            @php $utmMax = max(1, collect($topCampaigns)->max('total')); @endphp
            @foreach($topCampaigns as $row)
            <div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid var(--u-line,#e2e8f0);">
                <span style="flex:2;font-size:var(--tx-xs);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                <div style="flex:3;height:5px;background:var(--u-bg,#f1f5f9);border-radius:999px;overflow:hidden;">
                    <div style="width:{{ round($row['total']/$utmMax*100) }}%;height:100%;background:var(--u-brand,#1e40af);border-radius:999px;"></div>
                </div>
                <span style="font-size:var(--tx-xs);font-weight:700;color:var(--u-text,#0f172a);width:28px;text-align:right;">{{ $row['total'] }}</span>
            </div>
            @endforeach
            @else
            <p style="color:var(--u-muted,#64748b);font-size:var(--tx-sm);">UTM verisi yok.</p>
            @endif
        </div>

        @if(!empty($externalByProvider))
        <div class="mkd-card-head" style="border-top:1px solid var(--u-line,#e2e8f0);border-bottom:none;padding:12px 20px;">
            <h3 style="font-size:var(--tx-sm);">🌐 External Provider (30g)</h3>
        </div>
        <div class="mkd-card-body" style="padding-top:10px;">
            @foreach($externalByProvider as $row)
            <div style="display:flex;align-items:center;gap:12px;padding:6px 0;border-bottom:1px solid var(--u-line,#e2e8f0);font-size:var(--tx-xs);">
                <span style="flex:1;font-weight:600;">{{ $row['provider'] }}</span>
                <span style="color:var(--u-muted,#64748b);">{{ number_format($row['spend'],2) }} €</span>
                <span style="color:var(--u-muted,#64748b);">{{ number_format($row['clicks']) }} tık</span>
                <span style="font-weight:700;color:#15803d;">{{ number_format($row['conversions']) }} conv</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>


<details class="card" style="margin-top:12px;">
    <summary class="det-sum">
        <h3>📖 Kullanım Kılavuzu</h3>
        <span class="det-chev">▼</span>
    </summary>
    <div style="padding-top:12px;">
        <h4 style="margin:0 0 10px;font-size:var(--tx-sm);font-weight:700;">Marketing Dashboard — Genel Bakış</h4>
        <p style="font-size:var(--tx-sm);color:var(--u-muted,#64748b);margin:0 0 12px;">Tüm pazarlama faaliyetlerinin tek ekranda özeti. Sabah ilk bu sayfayla günü başlat.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">📊 KPI Kartları</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li><strong>Toplam Lead:</strong> Bugüne kadar sisteme giren tüm adaylar</li>
                    <li><strong>Bu Ay Lead:</strong> Cari aydaki yeni kayıtlar</li>
                    <li><strong>Aktif Kampanya:</strong> Şu an çalışan kampanya sayısı</li>
                    <li><strong>Dönüşüm Oranı:</strong> Aday Öğrenci → Öğrenci oranı (hedef: %15+)</li>
                    <li><strong>Toplam Harcama:</strong> Onaylı bütçe harcaması</li>
                    <li><strong>ROI:</strong> (Gelir - Harcama) / Harcama × 100</li>
                </ul>
            </div>
            <div>
                <strong style="font-size:var(--tx-xs);display:block;margin-bottom:6px;">🔗 Hızlı Bağlantılar</strong>
                <ul style="margin:0;padding-left:16px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);line-height:1.8;">
                    <li>Kampanyalar → Aktif kampanyaları yönet</li>
                    <li>Pipeline → Aday dönüşüm hunisini izle</li>
                    <li>KPI Raporu → Detaylı performans analizi</li>
                    <li>Lead Kaynakları → Hangi kanal daha iyi çalışıyor?</li>
                    <li>Otomasyon → Workflow durumlarını kontrol et</li>
                </ul>
            </div>
        </div>
        <div style="margin-top:10px;padding:10px;background:color-mix(in srgb,var(--u-brand,#1e40af) 5%,var(--u-card,#fff));border-radius:8px;font-size:var(--tx-xs);color:var(--u-muted,#64748b);">
            💡 <strong>İpucu:</strong> Dashboard verileri önbellekten gelir. Anlık veri için sayfayı yenile veya KPI raporuna geç.
        </div>
    </div>
</details>

</div>
</div>{{-- /dash-main-content --}}

@push('scripts')
<script>
(function(){
    var _loaded = false;
    var _csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
    window.switchMkdTab = function(tab, btn) {
        var main = document.getElementById('dash-main-content');
        var blt  = document.getElementById('panel-blt');
        var tM   = document.getElementById('tab-mktg');
        var tB   = document.getElementById('tab-blt');
        var activeStyle   = 'background:var(--u-brand,#1e40af);color:#fff;border-color:var(--u-brand,#1e40af);';
        var inactiveStyle = 'background:var(--u-card,#fff);color:var(--u-muted,#64748b);border-color:var(--u-line,#e2e8f0);';
        if (tab === 'blt') {
            if (main) main.style.display = 'none';
            if (blt)  blt.style.display  = 'block';
            if (tM)   tM.style.cssText += inactiveStyle;
            if (tB)   tB.style.cssText += activeStyle;
            if (!_loaded) {
                blt.innerHTML = '<div style="padding:32px;text-align:center;color:var(--u-muted);">Yükleniyor...</div>';
                fetch('/bulletins/partial', { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text()).then(function(html) { blt.innerHTML = html; _loaded = true; });
            }
        } else {
            if (main) main.style.display = 'block';
            if (blt)  blt.style.display  = 'none';
            if (tM)   tM.style.cssText += activeStyle;
            if (tB)   tB.style.cssText += inactiveStyle;
        }
    };
    if (window.location.hash === '#duyurular') {
        document.addEventListener('DOMContentLoaded', function(){
            switchMkdTab('blt', document.getElementById('tab-blt'));
        });
    }
})();
</script>
@endpush

{{-- ── Derinlemesine Analitikler (audit gap fix) ── --}}
@if(!empty($mktgAnalytics))
<div style="margin-top:20px;">
    <div style="font-size:14px;font-weight:700;color:var(--u-text,#111);margin-bottom:14px;">📊 Derinlemesine Analitikler</div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
        {{-- Email Kampanya Performansı --}}
        @if(!empty($mktgAnalytics['email']))
        @php $em = $mktgAnalytics['email']; @endphp
        <div style="background:var(--u-card,#fff);border:1px solid var(--u-line,#e2e8f0);border-radius:10px;padding:18px 20px;">
            <div style="font-size:13px;font-weight:700;color:var(--u-text,#111);margin-bottom:12px;">📧 Email Kampanya Performansı</div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:10px;">
                <div style="text-align:center;padding:8px;background:var(--u-bg,#f8fafc);border-radius:6px;">
                    <div style="font-size:18px;font-weight:800;color:#3b82f6;">%{{ $em['open_rate'] }}</div>
                    <div style="font-size:10px;color:var(--u-muted,#64748b);">Açılma Oranı</div>
                </div>
                <div style="text-align:center;padding:8px;background:var(--u-bg,#f8fafc);border-radius:6px;">
                    <div style="font-size:18px;font-weight:800;color:#16a34a;">%{{ $em['click_rate'] }}</div>
                    <div style="font-size:10px;color:var(--u-muted,#64748b);">Tıklama Oranı</div>
                </div>
                <div style="text-align:center;padding:8px;background:var(--u-bg,#f8fafc);border-radius:6px;">
                    <div style="font-size:18px;font-weight:800;color:#8b5cf6;">{{ $em['registrations'] }}</div>
                    <div style="font-size:10px;color:var(--u-muted,#64748b);">Kayıt Dönüşüm</div>
                </div>
            </div>
            <div style="display:flex;gap:12px;font-size:11px;color:var(--u-muted,#64748b);">
                <span>{{ $em['campaigns'] }} kampanya</span>
                <span>{{ $em['sent'] }} gönderim</span>
                <span>{{ $em['bounced'] }} bounce</span>
            </div>
        </div>
        @endif

        {{-- Haftalık Lead Trendi --}}
        @if(!empty($mktgAnalytics['weeklyLeads']))
        <div style="background:var(--u-card,#fff);border:1px solid var(--u-line,#e2e8f0);border-radius:10px;padding:18px 20px;">
            <div style="font-size:13px;font-weight:700;color:var(--u-text,#111);margin-bottom:12px;">📈 Haftalık Lead Trendi</div>
            @php $mxW = max(1, max(array_column($mktgAnalytics['weeklyLeads'], 'count'))); @endphp
            <div style="display:flex;align-items:flex-end;gap:6px;height:80px;">
                @foreach($mktgAnalytics['weeklyLeads'] as $wk)
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;">
                        <span style="font-size:10px;font-weight:700;">{{ $wk['count'] }}</span>
                        <div style="width:100%;background:#3b82f6;border-radius:3px 3px 0 0;min-height:2px;height:{{ round($wk['count'] / $mxW * 60) }}px;"></div>
                        <span style="font-size:9px;color:var(--u-muted,#94a3b8);">{{ $wk['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Kaynak Kalite Skoru + Etkinlik Memnuniyeti --}}
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:16px;">
        @if(!empty($mktgAnalytics['sourceQuality']))
        <div style="background:var(--u-card,#fff);border:1px solid var(--u-line,#e2e8f0);border-radius:10px;padding:18px 20px;">
            <div style="font-size:13px;font-weight:700;color:var(--u-text,#111);margin-bottom:12px;">🎯 Kaynak Kalite Skoru</div>
            @foreach($mktgAnalytics['sourceQuality'] as $sq)
                <div style="display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid var(--u-line,#f1f5f9);font-size:12px;">
                    <span style="flex:1;font-weight:600;">{{ $sq['source'] }}</span>
                    <span style="color:var(--u-muted,#64748b);">{{ $sq['total'] }} lead</span>
                    <span style="color:#16a34a;font-weight:700;">{{ $sq['converted'] }} dönüşüm</span>
                    <span style="background:{{ $sq['rate'] >= 20 ? '#dcfce7' : ($sq['rate'] >= 5 ? '#fef9c3' : '#fef2f2') }};color:{{ $sq['rate'] >= 20 ? '#166534' : ($sq['rate'] >= 5 ? '#854d0e' : '#991b1b') }};padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;">%{{ $sq['rate'] }}</span>
                </div>
            @endforeach
        </div>
        @endif

        <div style="background:var(--u-card,#fff);border:1px solid var(--u-line,#e2e8f0);border-radius:10px;padding:18px 20px;">
            <div style="font-size:13px;font-weight:700;color:var(--u-text,#111);margin-bottom:12px;">🎪 Etkinlik Memnuniyeti</div>
            <div style="text-align:center;padding:20px 0;">
                <div style="font-size:36px;font-weight:800;color:#8b5cf6;">{{ $mktgAnalytics['eventSatisfaction'] ?? '-' }}</div>
                <div style="font-size:12px;color:var(--u-muted,#64748b);margin-top:4px;">/ 10 ortalama skor</div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection
