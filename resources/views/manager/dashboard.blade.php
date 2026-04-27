@extends('manager.layouts.app')

@section('title', 'Manager Dashboard')
@section('page_title', 'Manager Dashboard')

@push('head')
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ── Manager Dashboard Hero ── */
.mgd-hero {
    background: linear-gradient(to right, #1e293b 0%, #1e40af 60%, #3b5fcc 100%);
    border-radius: 0 0 16px 16px;
    padding: 32px 28px 24px;
    position: relative;
    overflow: hidden;
    margin: -20px -20px 20px 0;
}
.mgd-hero::before {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 240px; height: 240px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
    pointer-events: none;
}
.mgd-hero::after {
    content: '';
    position: absolute;
    bottom: -70px; left: 38%;
    width: 280px; height: 280px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    pointer-events: none;
}
.mgd-hero-top {
    display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
    position: relative; z-index: 1; margin-bottom: 16px;
}
.mgd-avatar {
    width: 60px; height: 60px; border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 2.5px solid rgba(255,255,255,.4);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 22px;
    flex-shrink: 0;
}
.mgd-hero-info { flex: 1; min-width: 180px; }
.mgd-hero-name { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 6px; }
.mgd-hero-badges { display: flex; gap: 6px; flex-wrap: wrap; }
.mgd-hero-badge {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 999px;
    padding: 3px 10px; font-size: 11px; color: #fff; font-weight: 600;
    display: inline-flex; align-items: center; line-height: 1;
}
.mgd-hero-badge.ok { background: rgba(134,239,172,.25); border-color: rgba(134,239,172,.5); }
.mgd-hero-badge.warn { background: rgba(253,186,116,.25); border-color: rgba(253,186,116,.5); }
.mgd-hero-badge.danger { background: rgba(252,165,165,.25); border-color: rgba(252,165,165,.5); }
.mgd-hero-stats { display: flex; gap: 20px; flex-wrap: wrap; margin-left: auto; flex-shrink: 0; }
.mgd-hstat { text-align: center; }
.mgd-hstat-val { font-size: 18px; font-weight: 700; color: #fff; line-height: 1; margin-bottom: 3px; }
.mgd-hstat-label { font-size: 11px; color: rgba(255,255,255,.65); font-weight: 500; }
.mgd-hstat-sep { width: 1px; background: rgba(255,255,255,.2); align-self: stretch; }
.mgd-hero-actions { display: flex; gap: 8px; flex-wrap: wrap; position: relative; z-index: 1; }
.mgd-hero-btn {
    padding: 7px 14px; border-radius: 8px; font-size: 13px; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer;
}
.mgd-hero-btn.primary { background: #fff; color: #1e40af; }
.mgd-hero-btn.ghost { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.3); }
/* hide default top bar on dashboard */
.top { display: none !important; }
/* primary butonları hero rengiyle uyumlu mavi yap */
.btn.primary, .btn.btn-primary, a.btn.btn-primary,
button.btn.btn-primary, button.btn.primary {
    background: #1e40af !important;
    border-color: #1e40af !important;
    color: #fff !important;
}
.btn.primary:hover, .btn.btn-primary:hover, a.btn.btn-primary:hover,
button.btn.btn-primary:hover, button.btn.primary:hover {
    background: #1d4ed8 !important;
    border-color: #1d4ed8 !important;
}
/* ── Hızlı Erişim ── */
.mgd-quick-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}
@media (max-width: 900px) { .mgd-quick-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 600px) { .mgd-quick-grid { grid-template-columns: repeat(2, 1fr); } }
.mgd-quick-link {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 8px; padding: 14px 8px 12px;
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 12px; text-decoration: none;
    color: #374151; font-size: 12px; font-weight: 600;
    text-align: center; line-height: 1.3;
    transition: background .15s, border-color .15s, transform .12s;
}
.mgd-quick-link:hover {
    background: var(--u-bg,#f1f5f9); border-color: var(--u-brand,#1e40af);
    color: var(--u-brand,#1e40af); transform: translateY(-2px);
    text-decoration: none;
}
/* progress bar */
.mgd-bar { height:5px; background:var(--border,#e2e8f0); border-radius:3px; overflow:hidden; margin-top:4px; }
.mgd-bar span { display:block; height:100%; border-radius:3px; }
.mgd-quick-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 800; color: #fff;
    flex-shrink: 0;
}
/* ── Manager palette: buton & badge overrides ── */
/* .btn.alt → daha belirgin kenarlık */
.btn.alt {
    background: #fff;
    border: 1.5px solid #cbd5e1 !important;
    color: #0f172a;
}
.btn.alt:hover { border-color: #1e40af !important; color: #1e40af; }

/* Bare .btn (modifier yok) → mavi primary */
.btn:not(.alt):not(.ok):not(.warn):not(.btn-primary):not(.btn-secondary) {
    background: #1e40af;
    color: #fff;
    border-color: #1e40af;
}
.btn:not(.alt):not(.ok):not(.warn):not(.btn-primary):not(.btn-secondary):hover {
    background: #1d4ed8;
    border-color: #1d4ed8;
}

/* Input / Select görünürlük */
.content input[type="text"],
.content input[type="email"],
.content input[type="date"],
.content input[type="number"],
.content select,
.content textarea {
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 7px;
    padding: 6px 10px;
    font-size: 13px;
    background: #fff;
    color: #0f172a;
    outline: none;
    transition: border-color .15s;
}
.content input:focus,
.content select:focus,
.content textarea:focus {
    border-color: #1e40af !important;
    box-shadow: 0 0 0 3px rgba(30,64,175,.1);
}

/* Badge renkleri */
.badge {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 2px 9px; border-radius: 999px;
    font-size: 11px; font-weight: 700; line-height: 1.5;
    white-space: nowrap;
}
.badge.ok      { background: rgba(22,163,74,.13);   color: #15803d; }
.badge.warn    { background: rgba(217,119,6,.13);   color: #b45309; }
.badge.danger  { background: rgba(220,38,38,.13);   color: #b91c1c; }
.badge.info    { background: rgba(8,145,178,.13);   color: #0e7490; }
.badge.pending { background: rgba(30,64,175,.10);   color: #1e40af; }
.badge.chip    { padding: 3px 10px; }
/* Manager blue pill — department & status chips */
.mgd-pill {
    display: inline-flex; align-items: center;
    padding: 2px 9px; border-radius: 999px;
    font-size: 10px; font-weight: 700; white-space: nowrap;
    background: rgba(30,64,175,.08);
    border: 1px solid rgba(30,64,175,.2);
    color: #1e40af;
}
</style>
@endpush

@section('content')
@php
    $mgdName    = auth()->user()?->name ?? 'Manager';
    $mgdInitials= strtoupper(substr($mgdName, 0, 2));
    $riskBadge  = match($stats['risk_level']) { 'low'=>'ok', 'good'=>'ok', 'medium'=>'warn', 'high'=>'danger', 'critical'=>'danger', default=>'ok' };
    $riskLabel  = match($stats['risk_level']) { 'low'=>'Düşük', 'good'=>'İyi', 'medium'=>'Orta', 'high'=>'Yüksek', 'critical'=>'Kritik', default=>strtoupper((string)($stats['risk_level'])) };
@endphp

{{-- ── Hero ── --}}
<div class="mgd-hero">
    <div class="mgd-hero-top">
        <div class="mgd-hero-info">
            <div class="mgd-hero-name">{{ $mgdName }}</div>
            <div class="mgd-hero-badges">
                <span class="mgd-hero-badge">{{ $stats['month_label'] }}</span>
                <span class="mgd-hero-badge {{ $riskBadge }}">Risk: {{ $riskLabel }}</span>
            </div>
        </div>
        <div class="mgd-hero-stats">
            <div class="mgd-hstat">
                <div class="mgd-hstat-val">{{ number_format($stats['monthly_revenue'], 0, ',', '.') }}</div>
                <div class="mgd-hstat-label">EUR Bu Ay</div>
            </div>
            <div class="mgd-hstat-sep"></div>
            <div class="mgd-hstat">
                <div class="mgd-hstat-val">{{ $stats['active_students'] }}</div>
                <div class="mgd-hstat-label">Aktif Öğrenci</div>
            </div>
            <div class="mgd-hstat-sep"></div>
            <div class="mgd-hstat">
                <div class="mgd-hstat-val">%{{ number_format($stats['conversion_rate'], 1, ',', '.') }}</div>
                <div class="mgd-hstat-label">Dönüşüm</div>
            </div>
        </div>
    </div>

    {{-- Aylık Hedef Progress Bar --}}
    @php
        $monthlyTarget = (float) (env('MONTHLY_REVENUE_TARGET') ?: 12000);
        $monthlyActual = (float) ($stats['monthly_revenue'] ?? 0);
        $targetPct     = $monthlyTarget > 0 ? min(100, round($monthlyActual / $monthlyTarget * 100, 1)) : 0;
        $targetColor   = $targetPct >= 100 ? '#16a34a' : ($targetPct >= 75 ? '#3b82f6' : ($targetPct >= 50 ? '#d97706' : '#dc2626'));
    @endphp
    <div style="margin-top:14px;padding:10px 14px;background:rgba(255,255,255,.12);border-radius:8px;">
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px;margin-bottom:6px;color:rgba(255,255,255,.9);">
            <span style="font-weight:700;text-transform:uppercase;letter-spacing:.3px;">🎯 Aylık Gelir Hedefi</span>
            <span><strong style="font-size:14px;">%{{ $targetPct }}</strong> · {{ number_format($monthlyActual, 0, ',', '.') }} / {{ number_format($monthlyTarget, 0, ',', '.') }} EUR</span>
        </div>
        <div style="height:6px;background:rgba(255,255,255,.2);border-radius:999px;overflow:hidden;">
            <div style="height:100%;width:{{ $targetPct }}%;background:{{ $targetColor }};border-radius:999px;transition:width .4s;"></div>
        </div>
    </div>
</div>

{{-- ── Müdahale Gerekli Widget (Lead Pipeline Oversight'a kısayollar) ── --}}
@php
    $iv = $interventions ?? ['unassigned'=>0,'hot_no_contact'=>0,'overdue'=>0,'total_active'=>0];
    $hasAlerts = $iv['unassigned'] > 0 || $iv['hot_no_contact'] > 0 || $iv['overdue'] > 0;
@endphp
@if($iv['total_active'] > 0)
<div style="margin-bottom:18px;padding:14px 16px;background:{{ $hasAlerts ? '#fef2f2' : '#f0fdf4' }};border:1px solid {{ $hasAlerts ? '#fecaca' : '#bbf7d0' }};border-radius:10px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:{{ $hasAlerts ? '12px' : '0' }};">
        <div>
            <div style="font-size:13px;font-weight:800;color:{{ $hasAlerts ? '#991b1b' : '#166534' }};display:flex;align-items:center;gap:8px;">
                <span style="font-size:18px;">{{ $hasAlerts ? '⚠' : '✓' }}</span>
                {{ $hasAlerts ? 'Müdahale Gerekli — Ekibinin Pipeline\'ında Risk Var' : 'Pipeline Sağlıklı — Müdahale Gerektiren Vaka Yok' }}
            </div>
            <div style="font-size:11px;color:{{ $hasAlerts ? '#7f1d1d' : '#15803d' }};margin-top:3px;">
                {{ $iv['total_active'] }} aktif lead · Senior'lar bağımsız çalışır, sen sadece sorunlu vakalara müdahale et
            </div>
        </div>
        <a href="/manager/pipeline/oversight" style="font-size:11px;font-weight:700;padding:7px 14px;border-radius:6px;background:{{ $hasAlerts ? '#dc2626' : '#16a34a' }};color:#fff;text-decoration:none;">
            🛰 Oversight Paneline Git →
        </a>
    </div>

    @if($hasAlerts)
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;">
        @if($iv['unassigned'] > 0)
        <a href="/manager/pipeline/oversight?risk=unassigned" style="display:block;padding:11px 14px;background:#fff;border:1px solid #fecaca;border-radius:8px;text-decoration:none;color:inherit;transition:transform .1s,box-shadow .1s;" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 12px rgba(220,38,38,.12)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
            <div style="font-size:10px;font-weight:700;color:#991b1b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Atanmamış</div>
            <div style="font-size:24px;font-weight:800;color:#dc2626;line-height:1;">{{ $iv['unassigned'] }}</div>
            <div style="font-size:10px;color:#7f1d1d;margin-top:4px;">Senior atanmasını bekliyor →</div>
        </a>
        @endif

        @if($iv['hot_no_contact'] > 0)
        <a href="/manager/pipeline/oversight?risk=hot_no_contact" style="display:block;padding:11px 14px;background:#fff;border:1px solid #fecaca;border-radius:8px;text-decoration:none;color:inherit;transition:transform .1s,box-shadow .1s;" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 12px rgba(220,38,38,.12)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
            <div style="font-size:10px;font-weight:700;color:#991b1b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">🔥 Hot — Kontak Yok</div>
            <div style="font-size:24px;font-weight:800;color:#dc2626;line-height:1;">{{ $iv['hot_no_contact'] }}</div>
            <div style="font-size:10px;color:#7f1d1d;margin-top:4px;">Acil senior aksiyon gerekli →</div>
        </a>
        @endif

        @if($iv['overdue'] > 0)
        <a href="/manager/pipeline/oversight?risk=overdue" style="display:block;padding:11px 14px;background:#fff;border:1px solid #fde68a;border-radius:8px;text-decoration:none;color:inherit;transition:transform .1s,box-shadow .1s;" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 12px rgba(217,119,6,.12)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
            <div style="font-size:10px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">⏰ Geciken (5+ gün)</div>
            <div style="font-size:24px;font-weight:800;color:#d97706;line-height:1;">{{ $iv['overdue'] }}</div>
            <div style="font-size:10px;color:#92400e;margin-top:4px;">5+ gün hareketsiz lead →</div>
        </a>
        @endif
    </div>
    @endif
</div>
@endif

{{-- ── Tab Bar ── --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;border-bottom:2px solid var(--u-line,#e2e8f0);padding-bottom:0;">
    <button id="tab-main" onclick="switchMgrTab('main',this)"
        style="padding:10px 20px;font-size:13px;font-weight:700;background:none;border:none;border-bottom:3px solid var(--c-accent,#1e40af);margin-bottom:-2px;cursor:pointer;color:var(--c-accent,#1e40af);">
        📊 Genel Bakış
    </button>
    <button id="tab-blt" onclick="switchMgrTab('blt',this)"
        style="padding:10px 20px;font-size:13px;font-weight:700;background:none;border:none;border-bottom:3px solid transparent;margin-bottom:-2px;cursor:pointer;color:var(--u-muted,#64748b);display:flex;align-items:center;gap:6px;">
        📢 Duyurular
        @if(($bulletinUnread ?? 0) > 0)
        <span style="background:#ef4444;color:#fff;font-size:10px;font-weight:800;border-radius:999px;padding:1px 7px;line-height:16px;">{{ $bulletinUnread }}</span>
        @endif
    </button>
</div>
<div id="panel-blt" style="display:none;"></div>

<div id="dash-main-content">

{{-- Filtre + Preset tek bar --}}
<div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:10px 14px;margin-bottom:12px;">
    <form method="GET" action="/manager/dashboard">
        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            {{-- Presets --}}
            @foreach ($presets as $preset)
            <a href="/manager/dashboard?start_date={{ $preset['start_date'] }}&end_date={{ $preset['end_date'] }}&senior_email={{ urlencode($filters['senior_email']) }}"
               style="font-size:var(--tx-xs);padding:4px 10px;border-radius:6px;border:1px solid var(--border,#e2e8f0);background:var(--bg,#f8fafc);color:var(--text,#0f172a);text-decoration:none;font-weight:600;white-space:nowrap;">
               {{ $preset['label'] }}
            </a>
            @endforeach
            <span style="width:1px;height:20px;background:var(--border,#e2e8f0);flex-shrink:0;"></span>
            {{-- Tarih --}}
            <input type="date" name="start_date" value="{{ $filters['start_date'] }}"
                style="padding:5px 8px;border:1px solid var(--border,#e2e8f0);border-radius:6px;font-size:var(--tx-xs);color:var(--text,#0f172a);background:var(--surface,#fff);">
            <span style="font-size:var(--tx-xs);color:var(--muted,#64748b);">–</span>
            <input type="date" name="end_date" value="{{ $filters['end_date'] }}"
                style="padding:5px 8px;border:1px solid var(--border,#e2e8f0);border-radius:6px;font-size:var(--tx-xs);color:var(--text,#0f172a);background:var(--surface,#fff);">
            {{-- Advisory --}}
            <select name="senior_email" style="padding:5px 8px;border:1px solid var(--border,#e2e8f0);border-radius:6px;font-size:var(--tx-xs);color:var(--text,#0f172a);background:var(--surface,#fff);flex:1;min-width:140px;max-width:240px;">
                <option value="">Tüm Advisoryler</option>
                @foreach ($seniors as $senior)
                <option value="{{ $senior->email }}" {{ $filters['senior_email'] === $senior->email ? 'selected' : '' }}>{{ $senior->name }}</option>
                @endforeach
            </select>
            {{-- Aksiyonlar --}}
            <button type="submit" style="padding:5px 14px;background:#1e40af;color:#fff;border:none;border-radius:6px;font-size:var(--tx-xs);font-weight:600;cursor:pointer;">Uygula</button>
            <a href="/manager/dashboard" style="padding:5px 10px;border:1px solid var(--border,#e2e8f0);border-radius:6px;font-size:var(--tx-xs);color:var(--muted,#64748b);text-decoration:none;">Sıfırla</a>
            <a href="/manager/dashboard/export-csv?start_date={{ urlencode($filters['start_date']) }}&end_date={{ urlencode($filters['end_date']) }}&senior_email={{ urlencode($filters['senior_email']) }}"
               style="padding:5px 10px;border:1px solid var(--border,#e2e8f0);border-radius:6px;font-size:var(--tx-xs);color:var(--muted,#64748b);text-decoration:none;">CSV</a>
            <a href="/manager/dashboard/report-print?start_date={{ urlencode($filters['start_date']) }}&end_date={{ urlencode($filters['end_date']) }}&senior_email={{ urlencode($filters['senior_email']) }}"
               target="_blank" style="padding:5px 10px;border:1px solid var(--border,#e2e8f0);border-radius:6px;font-size:var(--tx-xs);color:var(--muted,#64748b);text-decoration:none;">PDF</a>
        </div>
    </form>
</div>

{{-- Açık Lise Uyarısı --}}
@if ($acikLiseGuests->isNotEmpty())
@php $alKey = 'acik_lise_dismissed_' . $acikLiseGuests->pluck('id')->sort()->implode('_'); @endphp
@if(!session($alKey))
{{-- Compact banner (dismissible → collapses to notification chip) --}}
<div id="al-banner" style="display:flex;align-items:center;gap:10px;padding:8px 14px;margin-bottom:10px;background:#fffbeb;border:1px solid #fde68a;border-left:4px solid #f59e0b;border-radius:10px;font-size:var(--tx-xs);">
    <span style="font-size:var(--tx-base);flex-shrink:0;">⚠️</span>
    <div style="flex:1;min-width:0;">
        <strong style="color:#78350f;">Açık Lise Başvurusu — {{ $acikLiseGuests->count() }} Kayıt:</strong>
        <span style="color:#92400e;margin-left:4px;">
            @foreach($acikLiseGuests as $i => $g)
                <a href="/manager/guests/{{ $g->id }}" style="color:#b45309;font-weight:600;text-decoration:none;">{{ $g->first_name }} {{ $g->last_name }}</a>{{ !$loop->last ? ', ' : '' }}
            @endforeach
        </span>
        <span style="color:#a16207;margin-left:6px;">— Studienkolleg / denklik belgesi gerekebilir.</span>
    </div>
    <button onclick="alDismiss()" title="Gördüm, kapat"
        style="flex-shrink:0;background:none;border:1px solid #fcd34d;border-radius:6px;padding:3px 10px;font-size:var(--tx-xs);font-weight:700;color:#92400e;cursor:pointer;">
        ✓ Gördüm
    </button>
</div>
@endif

{{-- Notification chip (shown after dismiss until page reload) --}}
<div id="al-chip" style="display:none;margin-bottom:10px;">
    <span style="display:inline-flex;align-items:center;gap:6px;background:#fef3c7;border:1px solid #fde68a;border-radius:999px;padding:4px 12px;font-size:var(--tx-xs);font-weight:700;color:#92400e;cursor:pointer;" onclick="alExpand()">
        ⚠️ Açık Lise: {{ $acikLiseGuests->count() }} kayıt
        <span style="font-size:var(--tx-xs);opacity:.7;">↓ göster</span>
    </span>
</div>

<script>
(function(){
    var BANNER = document.getElementById('al-banner');
    var CHIP   = document.getElementById('al-chip');
    if (!BANNER || !CHIP) return;

    // If already dismissed this session (sessionStorage)
    if (sessionStorage.getItem('al_dismissed')) {
        BANNER.style.display = 'none';
        CHIP.style.display   = 'block';
    }

    window.alDismiss = function() {
        sessionStorage.setItem('al_dismissed', '1');
        BANNER.style.transition = 'opacity .3s, max-height .3s';
        BANNER.style.opacity    = '0';
        setTimeout(function() {
            BANNER.style.display = 'none';
            CHIP.style.display   = 'block';
        }, 300);
    };
    window.alExpand = function() {
        sessionStorage.removeItem('al_dismissed');
        CHIP.style.display   = 'none';
        BANNER.style.display = 'flex';
        BANNER.style.opacity = '1';
    };
}());
</script>
@endif

{{-- KPI Kartları — 4'lü grid (sayısal göstergeler) --}}
@php
    $sched       = $opsStatus['scheduler']     ?? ['exists' => false];
    $mvp         = $opsStatus['mvp_smoke']      ?? ['exists' => false];
    $apiReg      = $opsStatus['api_regression'] ?? ['exists' => false];
    $selfHeal    = $opsStatus['self_heal']       ?? ['exists' => false];
    $criticalChk = $opsStatus['critical_check'] ?? ['exists' => false];
    $anyFail     = ($mvp['is_fail'] ?? false) || ($apiReg['is_fail'] ?? false) || ($selfHeal['is_fail'] ?? false) || ($criticalChk['is_fail'] ?? false);
    $anyStale    = ($sched['is_stale'] ?? true) || ($mvp['is_stale'] ?? true)  || ($apiReg['is_stale'] ?? true)  || ($selfHeal['is_stale'] ?? true)  || ($criticalChk['is_stale'] ?? true);
    $opsLabel    = $anyFail ? 'FAIL' : ($anyStale ? 'UYARI' : 'OK');
    $opsClass    = $anyFail ? 'danger' : ($anyStale ? 'warn' : 'ok');
@endphp

{{-- KPI Strip — kompakt 5 chip (Bildirim + Ops kaldırıldı, sidebar'da zaten var) --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:12px;">

    <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:10px 12px;border-top:3px solid #1e40af;">
        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Aylık Gelir</div>
        <div style="font-size:var(--tx-lg);font-weight:800;color:var(--text,#0f172a);line-height:1;">{{ number_format($stats['monthly_revenue'], 0, ',', '.') }}</div>
        <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-top:2px;">EUR · {{ $stats['month_label'] }}</div>
    </div>

    <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:10px 12px;border-top:3px solid #1e40af;">
        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Aktif Öğrenci</div>
        <div style="font-size:var(--tx-lg);font-weight:800;color:var(--text,#0f172a);line-height:1;">{{ $stats['active_students'] }}</div>
        <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-top:2px;">student_revenues</div>
    </div>

    <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:10px 12px;border-top:3px solid #1e40af;">
        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Dönüşüm</div>
        <div style="font-size:var(--tx-lg);font-weight:800;color:var(--text,#0f172a);line-height:1;">%{{ number_format($stats['conversion_rate'], 1, ',', '.') }}</div>
        <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-top:2px;">açık / aktif</div>
    </div>

    <div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:10px 12px;border-top:3px solid #1e40af;">
        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">Açık Tahsilat</div>
        <div style="font-size:var(--tx-lg);font-weight:800;color:var(--text,#0f172a);line-height:1;">{{ number_format($stats['open_pending_amount'], 0, ',', '.') }}</div>
        <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-top:2px;">EUR · pending</div>
    </div>

    @php $highRiskCount = ($riskyStudents ?? collect())->count(); $riskTop5 = ($riskyStudents ?? collect())->take(5); @endphp
    <div id="riskKpiTile" style="position:relative;background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:10px;padding:10px 12px;border-top:3px solid {{ $stats['risk_score'] > 50 ? '#dc2626' : ($stats['risk_score'] > 20 ? '#d97706' : '#16a34a') }};cursor:pointer;transition:border-color .12s;" onmouseover="this.style.borderColor='#dc2626';" onmouseout="this.style.borderColor='var(--border,#e2e8f0)';" onclick="toggleRiskPopover(event)">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:6px;margin-bottom:3px;">
            <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Risk Skoru</div>
            @if($highRiskCount > 0)
                <span style="font-size:10px;font-weight:700;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;padding:1px 6px;border-radius:999px;">{{ $highRiskCount }} riskli</span>
            @endif
        </div>
        <div style="font-size:var(--tx-lg);font-weight:800;color:{{ $stats['risk_score'] > 50 ? '#dc2626' : ($stats['risk_score'] > 20 ? '#d97706' : '#16a34a') }};line-height:1;">{{ $stats['risk_score'] }}</div>
        <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-top:2px;">overdue %{{ number_format($stats['risk_breakdown']['overdue_rate'], 1) }} · tıkla ↓</div>

        {{-- Popover (default hidden) --}}
        @if($riskTop5->isNotEmpty())
        <div id="riskPopover" style="display:none;position:absolute;top:calc(100% + 6px);right:0;z-index:100;background:#fff;border:1px solid var(--u-line,#e5e9f0);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);padding:10px;width:320px;cursor:default;" onclick="event.stopPropagation();">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid var(--u-line,#e5e9f0);">
                <span style="font-size:11px;font-weight:700;color:var(--u-muted,#64748b);text-transform:uppercase;letter-spacing:.3px;">⚠️ Yüksek Risk ({{ $highRiskCount }})</span>
                <a href="/manager/students?risk=high" style="font-size:10px;color:#1e40af;font-weight:600;text-decoration:none;">Tümü →</a>
            </div>
            @foreach($riskTop5 as $rs)
            @php
                $score = (int) ($rs->current_score ?? 0);
                $color = $score >= 70 ? '#dc2626' : ($score >= 50 ? '#d97706' : '#6b7280');
            @endphp
            <a href="/manager/students/{{ urlencode($rs->student_id ?? '') }}" style="display:flex;align-items:center;gap:8px;padding:5px 4px;font-size:12px;text-decoration:none;color:inherit;border-radius:5px;transition:background .1s;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='';">
                <span style="color:{{ $color }};font-weight:700;min-width:24px;font-size:11px;">{{ $score }}</span>
                <span style="flex:1;min-width:0;color:var(--u-text,#0f172a);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $rs->student?->name ?? 'Bilinmiyor' }}</span>
                <span style="width:50px;height:3px;background:var(--u-line,#e5e9f0);border-radius:999px;overflow:hidden;flex-shrink:0;">
                    <span style="display:block;height:100%;width:{{ min($score, 100) }}%;background:{{ $color }};"></span>
                </span>
            </a>
            @endforeach
        </div>
        @endif
    </div>
    <script nonce="{{ $cspNonce ?? '' }}">
    (function(){
        window.toggleRiskPopover = function(e) {
            e.stopPropagation();
            var pop = document.getElementById('riskPopover');
            if (!pop) return;
            pop.style.display = pop.style.display === 'block' ? 'none' : 'block';
        };
        document.addEventListener('click', function(e) {
            var tile = document.getElementById('riskKpiTile');
            if (tile && !tile.contains(e.target)) {
                var pop = document.getElementById('riskPopover');
                if (pop) pop.style.display = 'none';
            }
        });
    })();
    </script>

    {{-- Bildirim ve Ops tile'ları kaldırıldı. --}}

</div>

{{-- ── Bugün Aksiyon Merkezi (sadece sıfırdan farklı uyarılar) ── --}}
@php
    $actionItems = array_filter([
        $stats['overdue_outcomes'] > 0 ? ['cnt' => $stats['overdue_outcomes'], 'label' => 'Geciken outcome', 'href' => '/manager/requests', 'cls' => 'danger', 'icon' => '⏰'] : null,
        $stats['pending_approvals'] > 0 ? ['cnt' => $stats['pending_approvals'], 'label' => 'Bekleyen onay', 'href' => '/manager/requests', 'cls' => 'warn', 'icon' => '📋'] : null,
        $stats['upcoming_outcomes'] > 0 ? ['cnt' => $stats['upcoming_outcomes'], 'label' => '7 gün içinde deadline', 'href' => '/manager/requests', 'cls' => 'warn', 'icon' => '📅'] : null,
        $stats['notification_failed'] > 0 ? ['cnt' => $stats['notification_failed'], 'label' => 'Bildirim hatası', 'href' => '/manager/notification-stats', 'cls' => 'danger', 'icon' => '🔔'] : null,
    ]);
@endphp
@if(!empty($actionItems))
<div style="background:linear-gradient(135deg,#fef2f2 0%,#fff 100%);border:1px solid #fecaca;border-left:4px solid #dc2626;border-radius:10px;padding:12px 16px;margin-bottom:12px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <span style="font-size:11px;font-weight:700;color:#991b1b;text-transform:uppercase;letter-spacing:.3px;flex-shrink:0;">⚡ Aksiyon Bekliyor</span>
    @foreach($actionItems as $item)
    <a href="{{ $item['href'] }}" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:#fff;border:1px solid {{ $item['cls'] === 'danger' ? '#fecaca' : '#fde68a' }};border-radius:20px;text-decoration:none;color:var(--u-text,#0f172a);font-size:12px;transition:all .12s;" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 2px 8px rgba(0,0,0,.08)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
        <span>{{ $item['icon'] }}</span>
        <strong style="color:{{ $item['cls'] === 'danger' ? '#dc2626' : '#d97706' }};font-weight:800;">{{ $item['cnt'] }}</strong>
        <span>{{ $item['label'] }}</span>
        <span style="color:var(--u-muted,#64748b);font-size:10px;">→</span>
    </a>
    @endforeach
</div>
@endif

{{-- Risk widget kaldırıldı — KPI tile'daki "N riskli" badge'ine tıklayarak popover açılıyor --}}

{{-- Hızlı Erişim grid kaldırıldı --}}

{{-- ── Analitik Kısayolları ── --}}
<div style="background:var(--u-card,#fff);border:1px solid var(--u-line,#e2e8f0);border-radius:12px;padding:14px 16px;margin-bottom:12px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">
            📊 Analitik Panelleri
        </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:10px;">
        <a href="/manager/conversion-funnel" style="text-decoration:none;background:var(--u-bg,#f8fafc);border:1px solid var(--u-line,#e2e8f0);border-left:3px solid #8b5cf6;border-radius:9px;padding:14px 10px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;transition:transform .15s,box-shadow .15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(139,92,246,.15)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
            <div style="font-size:20px;margin-bottom:4px;">🎯</div>
            <div style="font-size:11px;font-weight:700;color:var(--u-text,#0f172a);">Dönüşüm Hunisi</div>
            <div style="font-size:10px;color:var(--u-muted);">Lead → Öğrenci</div>
        </a>
        <a href="/manager/senior-performance" style="text-decoration:none;background:var(--u-bg,#f8fafc);border:1px solid var(--u-line,#e2e8f0);border-left:3px solid #0ea5e9;border-radius:9px;padding:14px 10px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;transition:transform .15s,box-shadow .15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(14,165,233,.15)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
            <div style="font-size:20px;margin-bottom:4px;">👤</div>
            <div style="font-size:11px;font-weight:700;color:var(--u-text,#0f172a);">Danışman Perf.</div>
            <div style="font-size:10px;color:var(--u-muted);">Skor + leaderboard</div>
        </a>
        <a href="/manager/staff/performance" style="text-decoration:none;background:var(--u-bg,#f8fafc);border:1px solid var(--u-line,#e2e8f0);border-left:3px solid #6366f1;border-radius:9px;padding:14px 10px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;transition:transform .15s,box-shadow .15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(99,102,241,.15)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
            <div style="font-size:20px;margin-bottom:4px;">🏢</div>
            <div style="font-size:11px;font-weight:700;color:var(--u-text,#0f172a);">Personel Perf.</div>
            <div style="font-size:10px;color:var(--u-muted);">Staff KPI</div>
        </a>
        <a href="/manager/ticket-analytics" style="text-decoration:none;background:var(--u-bg,#f8fafc);border:1px solid var(--u-line,#e2e8f0);border-left:3px solid #f59e0b;border-radius:9px;padding:14px 10px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;transition:transform .15s,box-shadow .15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(245,158,11,.15)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
            <div style="font-size:20px;margin-bottom:4px;">🎫</div>
            <div style="font-size:11px;font-weight:700;color:var(--u-text,#0f172a);">Ticket Analitik</div>
            <div style="font-size:10px;color:var(--u-muted);">SLA + yanıt süresi</div>
        </a>
        <a href="/manager/feedback-analytics" style="text-decoration:none;background:var(--u-bg,#f8fafc);border:1px solid var(--u-line,#e2e8f0);border-left:3px solid #ec4899;border-radius:9px;padding:14px 10px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;transition:transform .15s,box-shadow .15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(236,72,153,.15)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
            <div style="font-size:20px;margin-bottom:4px;">💬</div>
            <div style="font-size:11px;font-weight:700;color:var(--u-text,#0f172a);">Geri Bildirim</div>
            <div style="font-size:10px;color:var(--u-muted);">NPS + memnuniyet</div>
        </a>
        <a href="/manager/contract-analytics" style="text-decoration:none;background:var(--u-bg,#f8fafc);border:1px solid var(--u-line,#e2e8f0);border-left:3px solid #0891b2;border-radius:9px;padding:14px 10px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;transition:transform .15s,box-shadow .15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(8,145,178,.15)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
            <div style="font-size:20px;margin-bottom:4px;">📝</div>
            <div style="font-size:11px;font-weight:700;color:var(--u-text,#0f172a);">Sözleşme Analitik</div>
            <div style="font-size:10px;color:var(--u-muted);">İmza + risk</div>
        </a>
        <a href="/manager/revenue-analytics" style="text-decoration:none;background:var(--u-bg,#f8fafc);border:1px solid var(--u-line,#e2e8f0);border-left:3px solid #16a34a;border-radius:9px;padding:14px 10px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;transition:transform .15s,box-shadow .15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(22,163,74,.15)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
            <div style="font-size:20px;margin-bottom:4px;">💰</div>
            <div style="font-size:11px;font-weight:700;color:var(--u-text,#0f172a);">Gelir Analitik</div>
            <div style="font-size:10px;color:var(--u-muted);">Paket + senior bazlı</div>
        </a>
        <a href="/manager/notification-stats" style="text-decoration:none;background:var(--u-bg,#f8fafc);border:1px solid var(--u-line,#e2e8f0);border-left:3px solid #64748b;border-radius:9px;padding:14px 10px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;transition:transform .15s,box-shadow .15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(100,116,139,.15)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
            <div style="font-size:20px;margin-bottom:4px;">🔔</div>
            <div style="font-size:11px;font-weight:700;color:var(--u-text,#0f172a);">Bildirim İst.</div>
            <div style="font-size:10px;color:var(--u-muted);">Kanal + kategori</div>
        </a>
    </div>
</div>

{{-- ── Personel Özeti ── --}}
@if(isset($staffMetrics) && $staffMetrics['total'] > 0)
@php
    $sm = $staffMetrics;
    $smMonthNames = ['01'=>'Ocak','02'=>'Şubat','03'=>'Mart','04'=>'Nisan','05'=>'Mayıs','06'=>'Haziran',
                     '07'=>'Temmuz','08'=>'Ağustos','09'=>'Eylül','10'=>'Ekim','11'=>'Kasım','12'=>'Aralık'];
    $smMonth = $smMonthNames[substr($sm['period'],5,2)] ?? substr($sm['period'],5,2);
    $smYear  = substr($sm['period'],0,4);
@endphp
<div style="background:var(--u-card,#fff);border:1px solid var(--u-line,#e2e8f0);border-radius:12px;padding:14px 16px;margin-bottom:12px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--u-muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">
            👥 Personel Özeti — {{ $smMonth }} {{ $smYear }}
        </div>
        <a href="/manager/staff/leaderboard" style="font-size:11px;color:#1e40af;font-weight:700;text-decoration:none;">
            🏆 Leaderboard →
        </a>
    </div>

    <div style="display:flex;flex-direction:column;gap:8px;">

        {{-- 4 KPI chip --}}
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
            <div style="background:var(--u-bg,#f8fafc);border:1px solid var(--u-line,#e2e8f0);border-radius:9px;padding:10px 12px;border-top:3px solid #1e40af;">
                <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Toplam Personel</div>
                <div style="font-size:22px;font-weight:800;color:var(--u-text,#0f172a);line-height:1;">{{ $sm['total'] }}</div>
                <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">
                    <span style="color:#16a34a;font-weight:700;">{{ $sm['active'] }} aktif</span>
                    @if($sm['passive'] > 0) · <span style="color:#dc2626;">{{ $sm['passive'] }} pasif</span>@endif
                </div>
            </div>
            <div style="background:var(--u-bg,#f8fafc);border:1px solid var(--u-line,#e2e8f0);border-radius:9px;padding:10px 12px;border-top:3px solid #6366f1;">
                <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Görev Tamamlandı</div>
                <div style="font-size:22px;font-weight:800;color:var(--u-text,#0f172a);line-height:1;">{{ $sm['total_tasks'] }}</div>
                <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">bu ay toplam</div>
            </div>
            <div style="background:var(--u-bg,#f8fafc);border:1px solid var(--u-line,#e2e8f0);border-radius:9px;padding:10px 12px;border-top:3px solid #0891b2;">
                <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Ticket Çözüldü</div>
                <div style="font-size:22px;font-weight:800;color:var(--u-text,#0f172a);line-height:1;">{{ $sm['total_tickets'] }}</div>
                <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">bu ay toplam</div>
            </div>
            <div style="background:var(--u-bg,#f8fafc);border:1px solid var(--u-line,#e2e8f0);border-radius:9px;padding:10px 12px;border-top:3px solid #d97706;">
                <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Toplam Saat</div>
                <div style="font-size:22px;font-weight:800;color:var(--u-text,#0f172a);line-height:1;">{{ number_format($sm['total_hours'], 1) }}h</div>
                <div style="font-size:10px;color:var(--u-muted);margin-top:3px;">ort. skor: <strong style="color:{{ $sm['avg_score'] >= 70 ? '#16a34a' : ($sm['avg_score'] >= 40 ? '#3b82f6' : '#f59e0b') }}">{{ number_format($sm['avg_score'], 0) }}</strong></div>
            </div>
        </div>

        {{-- Top 3 --}}
        @if($sm['top3']->isNotEmpty())
        <div style="background:var(--u-bg,#f8fafc);border:1px solid var(--u-line,#e2e8f0);border-radius:9px;padding:10px 14px;">
            <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">🏅 Bu Ay En İyi 3 Personel</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                @foreach($sm['top3'] as $i => $r)
                @php
                    $medals = ['🥇','🥈','🥉'];
                    $scColor = $r->score >= 70 ? '#16a34a' : ($r->score >= 40 ? '#3b82f6' : '#f59e0b');
                @endphp
                <a href="/manager/staff/{{ $r->user->id }}"
                   style="display:flex;align-items:center;gap:8px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e2e8f0);border-radius:8px;padding:7px 12px;text-decoration:none;flex:1;min-width:160px;">
                    <span style="font-size:18px;line-height:1;">{{ $medals[$i] }}</span>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:12px;font-weight:700;color:var(--u-text,#0f172a);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $r->user->name ?: $r->user->email }}</div>
                        <div style="font-size:10px;color:var(--u-muted);">{{ $r->actuals['tasks_done'] }} görev · {{ $r->actuals['tickets_resolved'] }} ticket</div>
                    </div>
                    <span style="font-size:15px;font-weight:900;color:{{ $scColor }};flex-shrink:0;">{{ $r->score }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>
@endif

{{-- Task Board + Mesaj --}}
<div class="grid2" style="margin-bottom:12px;">
    <section class="card" style="padding:14px 16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Task Board</div>
            <a class="btn" href="/tasks" style="font-size:var(--tx-xs);padding:4px 12px;white-space:nowrap;flex-shrink:0;background:#1e40af;color:#fff;border-color:#1e40af;">Task Board →</a>
        </div>
        <div style="display:flex;align-items:baseline;gap:6px;margin-bottom:4px;">
            <span style="font-size:var(--tx-xl);font-weight:800;color:var(--text,#0f172a);line-height:1;">{{ $taskOverview['todo'] ?? 0 }}</span>
            <span style="font-size:var(--tx-xs);color:var(--muted,#64748b);">todo</span>
        </div>
        <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-bottom:8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            devam: {{ $taskOverview['in_progress'] ?? 0 }} &nbsp;·&nbsp;
            bloke: {{ $taskOverview['blocked'] ?? 0 }} &nbsp;·&nbsp;
            <span style="{{ ($taskOverview['overdue'] ?? 0) > 0 ? 'color:#dc2626;font-weight:700;' : '' }}">gecikmiş: {{ $taskOverview['overdue'] ?? 0 }}</span>
        </div>
        @if(!empty($taskDepartmentOverview))
        <div style="display:flex;flex-wrap:wrap;gap:4px;">
            @foreach($taskDepartmentOverview as $dep)
            <span style="font-size:var(--tx-xs);padding:2px 9px;border:1px solid rgba(30,64,175,.2);border-radius:999px;color:#1e40af;background:rgba(30,64,175,.07);white-space:nowrap;font-weight:600;">{{ $dep['label'] }}: <strong>{{ $dep['open'] }}</strong></span>
            @endforeach
        </div>
        @endif
    </section>

    <section class="card" style="padding:14px 16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Mesaj Merkezi</div>
            <a class="btn" href="/messages-center" style="font-size:var(--tx-xs);padding:4px 12px;white-space:nowrap;flex-shrink:0;background:#1e40af;color:#fff;border-color:#1e40af;">Mesajlar →</a>
        </div>
        <div style="display:flex;align-items:baseline;gap:6px;margin-bottom:4px;">
            <span style="font-size:var(--tx-xl);font-weight:800;color:var(--text,#0f172a);line-height:1;">{{ $messageOverview['threads_open'] ?? 0 }}</span>
            <span style="font-size:var(--tx-xs);color:var(--muted,#64748b);">açık thread</span>
        </div>
        <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            unread: {{ $messageOverview['unread_for_advisor'] ?? 0 }} &nbsp;·&nbsp;
            katılımcı: {{ $messageOverview['unread_for_participant'] ?? 0 }} &nbsp;·&nbsp;
            <span style="{{ ($messageOverview['sla_overdue'] ?? 0) > 0 ? 'color:#dc2626;font-weight:700;' : '' }}">SLA: {{ $messageOverview['sla_overdue'] ?? 0 }}</span>
        </div>
    </section>
</div>

{{-- Portal Preview ve Snapshot Oluştur formu kaldırıldı.
     Preview'a ihtiyaç varsa /manager/students|seniors|dealers listelerinden erişilir.
     Snapshot rapor üretimi /manager/reports sayfasına taşınmalı (henüz yok, eklenecek). --}}

{{-- Acil & Durum detay (yukardaki aksiyon merkezi özetin kompakt versiyonu) --}}
<div class="grid2" style="margin-bottom:12px;">

    <section class="card" style="padding:14px 16px;">
        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;">Durum Paneli (Tüm Kategoriler)</div>
        @php
            $urgencies = [
                [
                    'count'  => $stats['overdue_outcomes'],
                    'label'  => 'Geciken outcome',
                    'sub'    => 'Deadline geçmiş, aksiyon gerekiyor',
                    'cls'    => $stats['overdue_outcomes'] > 0 ? 'danger' : 'ok',
                    'href'   => '/manager/requests',
                ],
                [
                    'count'  => $stats['pending_approvals'],
                    'label'  => 'Bekleyen onay',
                    'sub'    => 'Field Rule Engine kayıtları',
                    'cls'    => $stats['pending_approvals'] > 0 ? 'warn' : 'ok',
                    'href'   => '/manager/requests',
                ],
                [
                    'count'  => $stats['upcoming_outcomes'],
                    'label'  => '7 gün içinde deadline',
                    'sub'    => 'Yaklaşan process outcome\'lar',
                    'cls'    => $stats['upcoming_outcomes'] > 0 ? 'warn' : 'ok',
                    'href'   => '/manager/requests',
                ],
                [
                    'count'  => $stats['active_campaigns'],
                    'label'  => 'Aktif kampanya',
                    'sub'    => 'Şu anda çalışan marketing kampanyaları',
                    'cls'    => $stats['active_campaigns'] > 0 ? 'ok' : 'warn',
                    'href'   => '/manager/dashboard',
                ],
                [
                    'count'  => $stats['notification_failed'],
                    'label'  => 'Bildirim hatası',
                    'sub'    => 'Failed queue — tekrar denenmeli',
                    'cls'    => $stats['notification_failed'] > 0 ? 'danger' : 'ok',
                    'href'   => '/manager/dashboard',
                ],
            ];
        @endphp
        <div style="display:flex;flex-direction:column;gap:5px;">
            @foreach($urgencies as $u)
                <div style="display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:6px;background:var(--bg,#f8fafc);border:1px solid var(--border,#e2e8f0);">
                    <span style="flex-shrink:0;min-width:28px;height:24px;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;font-size:var(--tx-xs);font-weight:800;
                        @if($u['cls']==='danger') background:#fef2f2;color:#dc2626;
                        @elseif($u['cls']==='warn') background:#fefce8;color:#d97706;
                        @else background:#f0fdf4;color:#16a34a; @endif
                    ">{{ $u['count'] > 0 ? $u['count'] : '✓' }}</span>
                    <div style="flex:1;min-width:0;overflow:hidden;">
                        <div style="font-size:var(--tx-xs);font-weight:600;color:var(--text,#0f172a);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $u['label'] }}</div>
                        <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $u['sub'] }}</div>
                    </div>
                    <a href="{{ $u['href'] }}" style="font-size:var(--tx-xs);font-weight:600;color:#1e40af;white-space:nowrap;flex-shrink:0;text-decoration:none;">Gör →</a>
                </div>
            @endforeach
        </div>
    </section>

    <section class="card" style="padding:14px 16px;">
        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;">Onay ve İşlem Özeti</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div style="background:var(--bg,#f8fafc);border-radius:8px;padding:10px 12px;border-left:3px solid {{ $stats['pending_approvals'] > 0 ? '#d97706' : '#16a34a' }};">
                <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Bekleyen Onay</div>
                <div style="font-size:var(--tx-xl);font-weight:800;color:{{ $stats['pending_approvals'] > 0 ? '#d97706' : 'var(--text,#0f172a)' }};line-height:1;">{{ $stats['pending_approvals'] }}</div>
                <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-top:3px;">Field Rule Engine</div>
            </div>
            <div style="background:var(--bg,#f8fafc);border-radius:8px;padding:10px 12px;border-left:3px solid #64748b;">
                <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Arşivlenen</div>
                <div style="font-size:var(--tx-xl);font-weight:800;color:var(--text,#0f172a);line-height:1;">{{ $stats['archived_approvals'] }}</div>
                <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-top:3px;">Toplu arşivleme</div>
            </div>
        </div>
    </section>

</div>

{{-- Dönüşüm Funnel & Advisory Performans --}}
<div class="grid2" style="margin-bottom:12px;">

    <section class="card" style="padding:14px 16px;">
        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;">🔄 Dönüşüm Funnel</div>
        <div style="display:grid;grid-template-columns:1fr 120px;gap:14px;align-items:center;">
            <div>
                @foreach ($funnel as $row)
                    <div style="margin-bottom:7px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:3px;">
                            <span style="font-size:var(--tx-xs);color:var(--text,#0f172a);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;min-width:0;">{{ $row['label'] }}</span>
                            <span style="font-size:var(--tx-xs);white-space:nowrap;flex-shrink:0;">
                                <strong style="color:var(--text,#0f172a);">{{ $row['count'] }}</strong>
                                <span style="color:var(--muted,#64748b);margin-left:3px;">%{{ number_format(min($row['rate'],999), 1, ',', '.') }}</span>
                            </span>
                        </div>
                        <div style="height:5px;background:var(--border,#e2e8f0);border-radius:3px;overflow:hidden;">
                            <div style="height:100%;width:{{ min(100, max(0, $row['rate'])) }}%;background:linear-gradient(90deg,#1e40af,#3b82f6);border-radius:3px;transition:width .4s;"></div>
                        </div>
                    </div>
                @endforeach
                <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-top:6px;">Baz: aktif öğrenci sayısı</div>
            </div>
            <div style="position:relative;height:120px;width:120px;">
                <canvas id="mgd-funnel-donut"></canvas>
            </div>
        </div>
    </section>
    <script>
    (function(){
        var funnelRaw = @json($funnel ?? []);
        if (!funnelRaw.length) return;
        var ctx = document.getElementById('mgd-funnel-donut');
        if (!ctx) return;
        var colors = ['#1e40af','#2563eb','#3b82f6','#60a5fa','#93c5fd','#bfdbfe'];
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: funnelRaw.map(function(r){ return r.label; }),
                datasets: [{
                    data: funnelRaw.map(function(r){ return Math.max(1, parseInt(r.count)||0); }),
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function(){ return ''; },
                            label: function(c){ return c.label + ': ' + c.parsed; }
                        }
                    }
                }
            }
        });
    }());
    </script>

    <section class="card" style="padding:14px 16px;">
        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;">👥 Advisory Performans</div>
        @if ($seniorPerformance->isEmpty())
            <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);">Eğitim Danışmanı/mentor rolünde kullanıcı bulunamadı.</div>
        @else
            {{-- Bar chart --}}
            <div style="position:relative;height:110px;margin-bottom:12px;">
                <canvas id="mgd-senior-chart"></canvas>
            </div>
            {{-- Tablo --}}
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:var(--tx-xs);">
                    <thead>
                        <tr style="background:var(--bg,#f8fafc);border-bottom:1px solid var(--border,#e2e8f0);">
                            <th style="padding:5px 8px;text-align:left;font-weight:700;color:var(--muted,#64748b);font-size:var(--tx-xs);text-transform:uppercase;letter-spacing:.04em;">Advisory</th>
                            <th style="padding:5px 8px;text-align:center;font-weight:700;color:var(--muted,#64748b);font-size:var(--tx-xs);text-transform:uppercase;">Çözülen</th>
                            <th style="padding:5px 8px;text-align:center;font-weight:700;color:var(--muted,#64748b);font-size:var(--tx-xs);text-transform:uppercase;">Not</th>
                            <th style="padding:5px 8px;text-align:left;font-weight:700;color:var(--muted,#64748b);font-size:var(--tx-xs);text-transform:uppercase;">Son Aktivite</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($seniorPerformance as $row)
                            <tr style="border-bottom:1px solid var(--border,#e2e8f0);">
                                <td style="padding:5px 8px;max-width:140px;overflow:hidden;">
                                    <div style="font-weight:600;font-size:var(--tx-xs);color:var(--text,#0f172a);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $row['name'] }}</div>
                                    <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $row['email'] }}</div>
                                </td>
                                <td style="padding:5px 8px;text-align:center;font-weight:700;color:var(--text,#0f172a);">{{ $row['resolved_approvals'] }}</td>
                                <td style="padding:5px 8px;text-align:center;font-weight:700;color:var(--text,#0f172a);">{{ $row['notes_written'] }}</td>
                                <td style="padding:5px 8px;font-size:var(--tx-xs);color:var(--muted,#64748b);white-space:nowrap;">{{ $row['last_action_at'] ?? '–' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
    <script>
    (function(){
        var sData = @json($seniorPerformance ?? []);
        var ctx = document.getElementById('mgd-senior-chart');
        if (!ctx || !sData.length) return;
        var names    = sData.map(function(r){ return (r.name||'').split(' ')[0]; });
        var resolved = sData.map(function(r){ return parseInt(r.resolved_approvals)||0; });
        var notes    = sData.map(function(r){ return parseInt(r.notes_written)||0; });
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: names,
                datasets: [
                    { label: 'Çözülen', data: resolved, backgroundColor: 'rgba(30,64,175,0.8)', borderRadius: 4, borderSkipped: false },
                    { label: 'Not',     data: notes,    backgroundColor: 'rgba(96,165,250,0.7)', borderRadius: 4, borderSkipped: false }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { font: { size: 10 }, boxWidth: 10, padding: 8 } }
                },
                scales: {
                    x: { ticks: { font: { size: 9 } }, grid: { display: false } },
                    y: { ticks: { font: { size: 9 }, precision: 0 }, grid: { color: 'rgba(0,0,0,0.05)' }, beginAtZero: true }
                }
            }
        });
    }());
    </script>

</div>

{{-- Gelir & Approval Trendi — Chart.js --}}
<section class="card" style="margin-bottom:12px;padding:14px 16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <div style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">📈 Gelir &amp; Approval Trendi</div>
        <div style="display:flex;gap:12px;">
            <span style="display:flex;align-items:center;gap:4px;font-size:var(--tx-xs);color:var(--muted,#64748b);">
                <span style="width:12px;height:12px;border-radius:3px;background:#1e40af;display:inline-block;"></span> Gelir (EUR)
            </span>
            <span style="display:flex;align-items:center;gap:4px;font-size:var(--tx-xs);color:var(--muted,#64748b);">
                <span style="width:12px;height:3px;background:#d97706;display:inline-block;border-radius:2px;"></span> Approval
            </span>
        </div>
    </div>
    <div style="position:relative;height:240px;">
        <canvas id="mgd-trend-chart"></canvas>
    </div>
</section>
<script>
(function(){
    var trendRaw = @json($trend ?? []);
    console.log('[mgd-trend] trendRaw:', trendRaw);
    if (!trendRaw || !trendRaw.length) { console.warn('[mgd-trend] no data'); return; }
    var labels   = trendRaw.map(function(r){ return r.label; });
    var revenues = trendRaw.map(function(r){ return parseFloat(r.revenue)||0; });
    var approvals= trendRaw.map(function(r){ return parseInt(r.approval_count)||0; });
    var ctx = document.getElementById('mgd-trend-chart');
    if (!ctx) { console.warn('[mgd-trend] canvas not found'); return; }
    if (typeof Chart === 'undefined') { console.warn('[mgd-trend] Chart.js not loaded'); return; }
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Gelir (EUR)',
                    data: revenues,
                    backgroundColor: 'rgba(30,64,175,0.75)',
                    borderRadius: 5,
                    borderSkipped: false,
                    yAxisID: 'yRev',
                    order: 2,
                },
                {
                    type: 'line',
                    label: 'Approval',
                    data: approvals,
                    borderColor: '#d97706',
                    backgroundColor: 'rgba(217,119,6,0.08)',
                    pointBackgroundColor: '#d97706',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    borderWidth: 2.5,
                    tension: 0.35,
                    fill: true,
                    yAxisID: 'yApp',
                    order: 1,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx){
                            if (ctx.dataset.yAxisID === 'yRev')
                                return ' Gelir: ' + ctx.parsed.y.toLocaleString('tr-TR') + ' EUR';
                            return ' Approval: ' + ctx.parsed.y;
                        }
                    }
                }
            },
            scales: {
                yRev: {
                    position: 'left',
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: { font: { size: 10 }, callback: function(v){ return v.toLocaleString('tr-TR'); } }
                },
                yApp: {
                    position: 'right',
                    grid: { display: false },
                    ticks: { font: { size: 10 } },
                    beginAtZero: true,
                },
                x: { ticks: { font: { size: 10 } } }
            }
        }
    });
}());
</script>

{{-- Gelir Tahmin Bölümü --}}
@php
    $trendCollection = collect($trend ?? []);
    $last3 = $trendCollection->sortByDesc('label')->take(3);
    $avgRevenue = $last3->count() > 0 ? $last3->avg('revenue') : 0;
    $totalEarned   = (float) ($stats['monthly_revenue']     ?? 0);
    $totalPending  = (float) ($stats['open_pending_amount'] ?? 0);
    $totalRevenue  = $totalEarned + $totalPending;
    $collectionPct = $totalRevenue > 0 ? min(100, round($totalEarned / $totalRevenue * 100)) : 0;
    $forecast = [
        ['month' => \Carbon\Carbon::now()->addMonth(1)->format('Y-m'),  'amount' => $avgRevenue],
        ['month' => \Carbon\Carbon::now()->addMonths(2)->format('Y-m'), 'amount' => $avgRevenue * 0.95],
        ['month' => \Carbon\Carbon::now()->addMonths(3)->format('Y-m'), 'amount' => $avgRevenue * 0.90],
    ];
    $maxForecast = max(1, collect($forecast)->max('amount'));
@endphp
<section class="card" style="margin-bottom:12px;padding:14px 16px;">
    <div style="display:flex;align-items:baseline;gap:10px;margin-bottom:12px;flex-wrap:wrap;">
        <span style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Gelir Tahmini (3 Aylık)</span>
        <span class="muted" style="font-size:var(--tx-xs);">Son 3 ay ort. <strong style="color:var(--text,#0f172a);">{{ number_format($avgRevenue, 2, ',', '.') }} EUR</strong></span>
    </div>
    <div class="grid2">
        <div>
            <div class="muted" style="margin-bottom:8px;font-size:var(--tx-xs);">Tahmin Bandı</div>
            @foreach($forecast as $fc)
                <div style="margin-bottom:10px;">
                    <div style="display:flex;justify-content:space-between;font-size:var(--tx-sm);margin-bottom:4px;">
                        <span>{{ $fc['month'] }}</span>
                        <strong>~{{ number_format($fc['amount'], 0, ',', '.') }} EUR</strong>
                    </div>
                    <div style="height:6px;background:var(--u-line,#e5e9f0);border-radius:3px;overflow:hidden;">
                        <div style="height:100%;width:{{ $maxForecast > 0 ? round($fc['amount']/$maxForecast*100) : 0 }}%;background:var(--u-brand,#1f6fd9);opacity:0.7;border-radius:3px;"></div>
                    </div>
                </div>
            @endforeach
        </div>
        <div>
            <div class="muted" style="margin-bottom:8px;font-size:var(--tx-xs);">Tahsilat Durumu (Bu Ay)</div>
            <div style="margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;font-size:var(--tx-sm);margin-bottom:4px;">
                    <span>Tahsil Edilen</span>
                    <strong style="color:var(--u-ok,#16a34a);">{{ number_format($totalEarned, 2, ',', '.') }} EUR (%{{ $collectionPct }})</strong>
                </div>
                <div class="mgd-bar"><span style="width:{{ $collectionPct }}%;background:var(--u-ok,#16a34a);"></span></div>
            </div>
            @if($totalPending > 0)
            <div style="margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;font-size:var(--tx-sm);margin-bottom:4px;">
                    <span>Bekleyen Tahsilat</span>
                    <strong style="color:var(--u-warn,#d97706);">{{ number_format($totalPending, 2, ',', '.') }} EUR</strong>
                </div>
                <div style="height:6px;background:var(--u-line,#e5e9f0);border-radius:3px;overflow:hidden;">
                    <div style="height:100%;width:{{ $totalRevenue > 0 ? min(100, round($totalPending/$totalRevenue*100)) : 0 }}%;background:var(--u-warn,#d97706);border-radius:3px;"></div>
                </div>
            </div>
            @endif
            <div class="muted" style="font-size:var(--tx-xs);margin-top:8px;">
                Toplam hedef: {{ number_format($totalRevenue, 2, ',', '.') }} EUR
                @if($totalPending > 0) · {{ number_format($totalPending, 2, ',', '.') }} EUR bekleniyor @endif
            </div>
        </div>
    </div>
</section>

{{-- Bekleyen Sözleşme Talepleri --}}
@if(($pendingContracts ?? collect())->isNotEmpty())
<section class="card" style="margin-bottom:12px;padding:14px 16px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Bekleyen Sözleşme Talepleri</span>
            <span class="badge warn">{{ $pendingContracts->count() }}</span>
        </div>
        <a class="btn alt" style="font-size:var(--tx-xs);padding:4px 12px;" href="/manager/contract-template">Sözleşme Yönetimi →</a>
    </div>
    <div class="list">
        @foreach($pendingContracts as $row)
        <div class="item" style="display:flex;justify-content:space-between;gap:10px;">
            <div>
                <strong>{{ $row->first_name }} {{ $row->last_name }}</strong>
                <span class="muted" style="margin-left:6px;">{{ $row->email }}</span>
                @if($row->assigned_senior_email)
                    <div class="muted" style="font-size:var(--tx-xs);">Danışman: {{ $row->assigned_senior_email }}</div>
                @endif
            </div>
            <div style="text-align:right;flex-shrink:0;">
                <span class="badge {{ $row->contract_status === 'signed_uploaded' ? 'info' : 'warn' }}">
                    {{ $row->contract_status === 'signed_uploaded' ? 'İmzalı Yüklendi' : 'Talep Geldi' }}
                </span>
                <div class="muted" style="font-size:var(--tx-xs);margin-top:3px;">{{ optional($row->contract_requested_at)->format('d.m.Y H:i') }}</div>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif

{{-- Onay Bekleyenler & Geciken Outcomlar --}}
<div class="grid2" style="margin-bottom:12px;">

    <section class="card" style="padding:12px 14px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Onay Bekleyenler</span>
            <a href="/config" style="font-size:var(--tx-xs);font-weight:600;color:#1e40af;text-decoration:none;padding:3px 8px;border:1px solid rgba(30,64,175,.25);border-radius:6px;background:rgba(30,64,175,.06);white-space:nowrap;">Config →</a>
        </div>
        @forelse ($pendingApprovals as $row)
            <div style="padding:6px 8px;border-radius:6px;background:var(--bg,#f8fafc);border:1px solid var(--border,#e2e8f0);margin-bottom:5px;font-size:var(--tx-xs);">
                <div style="display:flex;align-items:center;gap:6px;">
                    <strong>#{{ $row->id }}</strong>
                    <span class="muted">rule:{{ $row->rule_id }} · {{ $row->triggered_field }}</span>
                </div>
                <div class="muted" style="font-size:var(--tx-xs);margin-top:2px;">
                    student:{{ $row->student_id ?: '–' }} · {{ optional($row->created_at)->format('d.m.Y H:i') }}
                </div>
            </div>
        @empty
            <div class="muted" style="font-size:var(--tx-xs);padding:8px 0;text-align:center;">Onay bekleyen kayıt yok.</div>
        @endforelse
    </section>

    <section class="card" style="padding:12px 14px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Geciken Outcome Kayıtları</span>
            <a href="/config" style="font-size:var(--tx-xs);font-weight:600;color:#1e40af;text-decoration:none;padding:3px 8px;border:1px solid rgba(30,64,175,.25);border-radius:6px;background:rgba(30,64,175,.06);white-space:nowrap;">Config →</a>
        </div>
        @forelse ($overdueOutcomes as $row)
            <div style="padding:6px 8px;border-radius:6px;background:var(--bg,#f8fafc);border:1px solid var(--border,#e2e8f0);margin-bottom:5px;font-size:var(--tx-xs);">
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                    <strong>#{{ $row->id }}</strong>
                    <span class="badge danger" style="font-size:var(--tx-xs);padding:1px 6px;">Gecikmiş</span>
                    <span class="muted">{{ $row->process_step }}</span>
                </div>
                <div class="muted" style="font-size:var(--tx-xs);margin-top:2px;">
                    {{ $row->outcome_type }} · deadline: {{ $row->deadline }}
                </div>
            </div>
        @empty
            <div class="muted" style="font-size:var(--tx-xs);padding:8px 0;text-align:center;">Geciken outcome kaydı yok.</div>
        @endforelse
    </section>

</div>

{{-- Son Rapor Snapshotları tablosu kaldırıldı — reports sayfasına taşınacak.
     Aşağıdaki kodu görmüyorsan normaldir; tam blok silindi. --}}
@if(false)
<section class="card" style="padding:14px 16px;">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
        <span style="font-size:var(--tx-xs);font-weight:700;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.04em;">Son Rapor Snapshotları</span>
        <span class="muted" style="font-size:var(--tx-xs);">· {{ method_exists($recentReports, 'total') ? $recentReports->total() : count($recentReports) }} kayıt</span>
    </div>

    {{-- Snapshot Filtresi --}}
    <form method="GET" action="/manager/dashboard" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-bottom:10px;">
        <input type="hidden" name="start_date" value="{{ $filters['start_date'] }}">
        <input type="hidden" name="end_date" value="{{ $filters['end_date'] }}">
        <input type="hidden" name="senior_email" value="{{ $filters['senior_email'] }}">
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="muted" style="font-size:var(--tx-xs);font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Tip</label>
            <select name="snapshot_type" style="font-size:var(--tx-xs);">
                <option value="">tüm tipler</option>
                @foreach (['manual','weekly','monthly','quarterly','yearly'] as $t)
                    <option value="{{ $t }}" {{ $snapshotFilters['snapshot_type'] === $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="muted" style="font-size:var(--tx-xs);font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Dönem ≥</label>
            <input type="date" name="snapshot_start" value="{{ $snapshotFilters['snapshot_start'] }}" style="font-size:var(--tx-xs);">
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="muted" style="font-size:var(--tx-xs);font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Dönem ≤</label>
            <input type="date" name="snapshot_end" value="{{ $snapshotFilters['snapshot_end'] }}" style="font-size:var(--tx-xs);">
        </div>
        <div style="display:flex;flex-direction:column;gap:3px;">
            <label class="muted" style="font-size:var(--tx-xs);font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Durum</label>
            <select name="snapshot_send_status" style="font-size:var(--tx-xs);">
                <option value="">tüm durumlar</option>
                <option value="draft" {{ $snapshotFilters['snapshot_send_status'] === 'draft' ? 'selected' : '' }}>draft</option>
                <option value="sent"  {{ $snapshotFilters['snapshot_send_status'] === 'sent'  ? 'selected' : '' }}>sent</option>
            </select>
        </div>
        <button class="btn alt" type="submit" style="font-size:var(--tx-xs);padding:5px 12px;">Filtrele</button>
        <a class="btn" href="/manager/dashboard?start_date={{ urlencode($filters['start_date']) }}&end_date={{ urlencode($filters['end_date']) }}&senior_email={{ urlencode($filters['senior_email']) }}" style="font-size:var(--tx-xs);padding:5px 12px;">Temizle</a>
    </form>

    {{-- Toplu Aksiyon --}}
    <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
        <form method="POST" action="/manager/dashboard/snapshot/mark-sent-bulk">
            @csrf
            <input type="hidden" name="start_date" value="{{ $filters['start_date'] }}">
            <input type="hidden" name="end_date" value="{{ $filters['end_date'] }}">
            <input type="hidden" name="senior_email" value="{{ $filters['senior_email'] }}">
            <input type="hidden" name="snapshot_type" value="{{ $snapshotFilters['snapshot_type'] }}">
            <input type="hidden" name="snapshot_start" value="{{ $snapshotFilters['snapshot_start'] }}">
            <input type="hidden" name="snapshot_end" value="{{ $snapshotFilters['snapshot_end'] }}">
            <input type="hidden" name="snapshot_send_status" value="{{ $snapshotFilters['snapshot_send_status'] }}">
            <button class="btn alt" type="submit" onclick="return confirm('Filtreye uyan snapshotlar gönderildi olarak işaretlensin mi?')">Toplu Gönderildi İşaretle</button>
        </form>
        <form method="POST" action="/manager/dashboard/snapshot/mark-draft-bulk">
            @csrf
            <input type="hidden" name="start_date" value="{{ $filters['start_date'] }}">
            <input type="hidden" name="end_date" value="{{ $filters['end_date'] }}">
            <input type="hidden" name="senior_email" value="{{ $filters['senior_email'] }}">
            <input type="hidden" name="snapshot_type" value="{{ $snapshotFilters['snapshot_type'] }}">
            <input type="hidden" name="snapshot_start" value="{{ $snapshotFilters['snapshot_start'] }}">
            <input type="hidden" name="snapshot_end" value="{{ $snapshotFilters['snapshot_end'] }}">
            <input type="hidden" name="snapshot_send_status" value="{{ $snapshotFilters['snapshot_send_status'] }}">
            <button class="btn alt" type="submit" onclick="return confirm('Filtreye uyan snapshotlar draft durumuna alınsın mı?')">Toplu Drafta Al</button>
        </form>
    </div>

    {{-- Snapshot Tablosu --}}
    <div style="overflow-x:auto;margin:0 -16px;padding:0 16px;">
        <table style="width:100%;border-collapse:collapse;font-size:var(--tx-xs);">
            <thead>
                <tr style="background:var(--u-bg,#f5f7fa);">
                    <th style="padding:6px 10px;text-align:left;">ID</th>
                    <th style="padding:6px 10px;text-align:left;">Tip</th>
                    <th style="padding:6px 10px;text-align:left;">Dönem</th>
                    <th style="padding:6px 10px;text-align:left;">Advisory</th>
                    <th style="padding:6px 10px;text-align:left;">Alıcılar</th>
                    <th style="padding:6px 10px;text-align:left;">Durum</th>
                    <th style="padding:6px 10px;text-align:left;">Oluşturan</th>
                    <th style="padding:6px 10px;text-align:left;">Tarih</th>
                    <th style="padding:6px 10px;">Aksiyon</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentReports as $row)
                    @php $isSent = ($row->send_status ?? 'draft') === 'sent'; @endphp
                    <tr style="border-bottom:1px solid var(--u-line,#e5e9f0);">
                        <td style="padding:6px 10px;">#{{ $row->id }}</td>
                        <td style="padding:6px 10px;">{{ $row->report_type }}</td>
                        <td style="padding:6px 10px;" class="muted">
                            {{ optional($row->period_start)->toDateString() }}
                            – {{ optional($row->period_end)->toDateString() }}
                        </td>
                        <td style="padding:6px 10px;" class="muted">{{ $row->senior_email ?: 'tüm' }}</td>
                        <td style="padding:6px 10px;" class="muted">
                            {{ is_array($row->sent_to) && count($row->sent_to) ? implode(', ', $row->sent_to) : '–' }}
                        </td>
                        <td style="padding:6px 10px;">
                            <span class="badge {{ $isSent ? 'ok' : 'pending' }}">{{ $row->send_status ?? 'draft' }}</span>
                            @if($row->sent_at)
                                <div class="muted" style="font-size:var(--tx-xs);">{{ optional($row->sent_at)->format('d.m.Y H:i') }}</div>
                            @endif
                        </td>
                        <td style="padding:6px 10px;" class="muted">{{ $row->created_by ?: '–' }}</td>
                        <td style="padding:6px 10px;" class="muted">{{ optional($row->created_at)->format('d.m.Y H:i') }}</td>
                        <td style="padding:6px 10px;">
                            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                <a class="btn alt" style="font-size:var(--tx-xs);padding:3px 7px;" href="/manager/dashboard/snapshot/{{ $row->id }}">Detay</a>
                                <a class="btn alt" style="font-size:var(--tx-xs);padding:3px 7px;" href="/manager/dashboard/snapshot/{{ $row->id }}/print" target="_blank">PDF</a>
                                <a class="btn alt" style="font-size:var(--tx-xs);padding:3px 7px;" href="/manager/dashboard/snapshot/{{ $row->id }}/export-csv">CSV</a>
                                @if(!$isSent)
                                    <form method="POST" action="/manager/dashboard/snapshot/{{ $row->id }}/mark-sent" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn ok" style="font-size:var(--tx-xs);padding:3px 7px;">Gönderildi</button>
                                    </form>
                                @else
                                    <form method="POST" action="/manager/dashboard/snapshot/{{ $row->id }}/mark-draft" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn alt" style="font-size:var(--tx-xs);padding:3px 7px;">Geri Al</button>
                                    </form>
                                @endif
                                <form method="POST" action="/manager/dashboard/snapshot/{{ $row->id }}" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn" style="font-size:var(--tx-xs);padding:3px 7px;background:var(--u-danger,#d33c3c);color:#fff;"
                                        onclick="return confirm('Bu snapshot silinsin mi?')">Sil</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding:16px;text-align:center;" class="muted">Snapshot yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($recentReports, 'links'))
        <div style="margin-top:10px;">{{ $recentReports->links() }}</div>
    @endif
</section>
@endif
{{-- /Son Rapor Snapshotları (disabled) --}}
</div>{{-- /dash-main-content --}}

@push('scripts')
<script>
(function(){
    var _loaded = false;
    window.switchMgrTab = function(tab, btn) {
        var main = document.getElementById('dash-main-content');
        var blt  = document.getElementById('panel-blt');
        var tM   = document.getElementById('tab-main');
        var tB   = document.getElementById('tab-blt');
        var acc  = '#1e40af';
        if (tab === 'blt') {
            if (main) main.style.display = 'none';
            if (blt)  blt.style.display  = 'block';
            tM.style.borderBottomColor = 'transparent'; tM.style.color = 'var(--u-muted,#64748b)';
            tB.style.borderBottomColor = acc;           tB.style.color = acc;
            if (!_loaded) {
                blt.innerHTML = '<div style="padding:32px;text-align:center;color:var(--u-muted,#64748b);">Yükleniyor...</div>';
                fetch('/bulletins/partial', { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text()).then(function(html) { blt.innerHTML = html; _loaded = true; });
            }
        } else {
            if (main) main.style.display = 'block';
            if (blt)  blt.style.display  = 'none';
            tM.style.borderBottomColor = acc;           tM.style.color = acc;
            tB.style.borderBottomColor = 'transparent'; tB.style.color = 'var(--u-muted,#64748b)';
        }
    };
    if (window.location.hash === '#duyurular')
        document.addEventListener('DOMContentLoaded', function(){ switchMgrTab('blt'); });
})();
</script>
@endpush

{{-- ── Manager Analytics (audit gap fix) ── --}}
@if(!empty($managerAnalytics))
<div style="margin-top:20px;">
    <div style="font-size:14px;font-weight:700;color:var(--u-text,#111);margin-bottom:14px;">📊 Platform Analitikleri</div>

    {{-- Platform toplamları --}}
    @if(!empty($managerAnalytics['platformTotals']))
    @php $pt = $managerAnalytics['platformTotals']; @endphp
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:16px;">
        <div class="card" style="text-align:center;padding:14px;border-top:3px solid #3b82f6;">
            <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Toplam Aday</div>
            <div style="font-size:22px;font-weight:800;color:#3b82f6;margin:4px 0;">{{ $pt['total_guests'] }}</div>
        </div>
        <div class="card" style="text-align:center;padding:14px;border-top:3px solid #16a34a;">
            <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Öğrenci</div>
            <div style="font-size:22px;font-weight:800;color:#16a34a;margin:4px 0;">{{ $pt['total_students'] }}</div>
        </div>
        <div class="card" style="text-align:center;padding:14px;border-top:3px solid #f59e0b;">
            <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Aktif Bayi</div>
            <div style="font-size:22px;font-weight:800;color:#f59e0b;margin:4px 0;">{{ $pt['total_dealers'] }}</div>
        </div>
        <div class="card" style="text-align:center;padding:14px;border-top:3px solid #8b5cf6;">
            <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Danışman</div>
            <div style="font-size:22px;font-weight:800;color:#8b5cf6;margin:4px 0;">{{ $pt['total_seniors'] }}</div>
        </div>
        <div class="card" style="text-align:center;padding:14px;border-top:3px solid #ec4899;">
            <div style="font-size:10px;font-weight:700;color:var(--u-muted);text-transform:uppercase;">Toplam Gelir</div>
            <div style="font-size:22px;font-weight:800;color:#ec4899;margin:4px 0;">{{ number_format($pt['total_revenue'], 0, ',', '.') }}€</div>
        </div>
    </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
        {{-- Gelir Tahmini --}}
        @if(!empty($managerAnalytics['revenueForecast']))
        @php $rf = $managerAnalytics['revenueForecast']; @endphp
        <div class="card" style="padding:18px 20px;">
            <div style="font-size:13px;font-weight:700;margin-bottom:12px;">📈 Gelir Tahmini (90 Gün)</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                <div style="background:var(--u-bg,#f8fafc);border-radius:8px;padding:12px;text-align:center;">
                    <div style="font-size:20px;font-weight:800;color:#3b82f6;">{{ number_format($rf['avg_monthly'], 0, ',', '.') }}€</div>
                    <div style="font-size:10px;color:var(--u-muted);">Aylık Ortalama</div>
                </div>
                <div style="background:var(--u-bg,#f8fafc);border-radius:8px;padding:12px;text-align:center;">
                    <div style="font-size:20px;font-weight:800;color:#16a34a;">{{ number_format($rf['forecast_90d'], 0, ',', '.') }}€</div>
                    <div style="font-size:10px;color:var(--u-muted);">90 Gün Projeksiyon</div>
                </div>
            </div>
            <div style="font-size:11px;color:var(--u-muted);">Son 3 ay ortalamasına dayalı basit projeksiyon</div>
        </div>
        @endif

        {{-- Dealer Risk Skoru --}}
        @if(!empty($managerAnalytics['dealerRisk']))
        <div class="card" style="padding:18px 20px;">
            <div style="font-size:13px;font-weight:700;margin-bottom:12px;">⚠️ Dealer Aktivite Riski</div>
            @foreach(array_slice($managerAnalytics['dealerRisk'], 0, 6) as $dr)
                <div style="display:flex;align-items:center;gap:8px;padding:4px 0;border-bottom:1px solid var(--u-line,#f1f5f9);font-size:12px;">
                    <span style="width:8px;height:8px;border-radius:50%;background:{{ $dr['risk'] === 'high' ? '#ef4444' : ($dr['risk'] === 'medium' ? '#f59e0b' : '#16a34a') }};flex-shrink:0;"></span>
                    <span style="flex:1;font-weight:600;">{{ $dr['name'] }}</span>
                    <span style="color:var(--u-muted);">{{ $dr['total_leads'] }} lead</span>
                    <span style="font-size:11px;color:{{ $dr['risk'] === 'high' ? '#ef4444' : '#64748b' }};">{{ $dr['days_inactive'] }}g inaktif</span>
                </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endif

@endsection

@push('scripts')
<script defer src="{{ Vite::asset('resources/js/csv-field.js') }}"></script>
<script defer src="{{ Vite::asset('resources/js/manager-dashboard.js') }}"></script>
@endpush
