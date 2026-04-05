@extends('guest.layouts.app')

@section('title', 'Başvuru Takvimim')
@section('page_title', 'Başvuru Takvimim')
@section('page_subtitle', 'Almanya yolculuğunuzun adım adım haritası')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
/* ════════════════════════════════════════
   JOURNEY MAP v5
════════════════════════════════════════ */

/* ── Hero ────────────────────────── */
.jm-hero {
    background: var(--hero-gradient);
    border-radius: 20px; padding: 28px 32px;
    color:#fff; display:flex; align-items:center; gap:24px;
    margin-bottom:28px; position:relative; overflow:hidden;
}
.jm-hero::after {
    content:''; position:absolute; inset:0; pointer-events:none;
    background-image: radial-gradient(rgba(255,255,255,.06) 1px,transparent 1px);
    background-size:24px 24px;
}
.jm-ring-wrap { position:relative; flex-shrink:0; z-index:1; }
.jm-ring-svg  { transform:rotate(-90deg); display:block; }
.jm-ring-bg   { fill:none; stroke:rgba(255,255,255,.12); stroke-width:9; }
.jm-ring-fg   { fill:none; stroke:#34d399; stroke-width:9; stroke-linecap:round;
                filter:drop-shadow(0 0 6px rgba(52,211,153,.55));
                transition:stroke-dashoffset 1.2s cubic-bezier(.4,0,.2,1); }
.jm-ring-inner { position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center; }
.jm-ring-pct  { font-size:24px; font-weight:900; }
.jm-ring-lbl  { font-size:9px; opacity:.6; text-transform:uppercase; letter-spacing:.1em; margin-top:1px; }

.jm-hero-body { flex:1; z-index:1; }
.jm-hero-title{ font-size:19px; font-weight:900; margin-bottom:3px; }
.jm-hero-sub  { font-size:12px; opacity:.6; margin-bottom:16px; }
.jm-hero-kpis { display:flex; gap:0; }
.jm-hk { padding:0 18px 0 0; }
.jm-hk + .jm-hk { padding-left:18px; border-left:1px solid rgba(255,255,255,.12); }
.jm-hk-val { font-size:22px; font-weight:900; line-height:1; }
.jm-hk-lbl { font-size:10px; opacity:.55; margin-top:2px; }

.jm-hero-next {
    z-index:1; flex-shrink:0;
    background:rgba(255,255,255,.09); border:1px solid rgba(255,255,255,.18);
    border-radius:14px; padding:16px 20px; min-width:186px;
    backdrop-filter:blur(8px);
}
.jm-hn-tag  { font-size:9px; opacity:.6; text-transform:uppercase; letter-spacing:.1em; margin-bottom:7px; }
.jm-hn-name { font-size:14px; font-weight:800; margin-bottom:4px; line-height:1.3; }
.jm-hn-date { font-size:11px; opacity:.6; margin-bottom:12px; }
.jm-hn-btn  {
    display:block; text-align:center;
    background:rgba(255,255,255,.16); color:#fff;
    border:1px solid rgba(255,255,255,.3); border-radius:8px;
    padding:7px 14px; font-size:12px; font-weight:700;
    text-decoration:none; transition:background .15s;
}
.jm-hn-btn:hover { background:rgba(255,255,255,.28); color:#fff; }

/* ── Category Selector ───────────── */
.jm-cats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 28px;
}
.jm-cat {
    border-radius: 18px;
    padding: 20px 16px 16px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: transform .18s, box-shadow .18s;
    /* default = light tinted */
    background: color-mix(in srgb, var(--jm-c) 10%, var(--u-card));
    border: 2px solid color-mix(in srgb, var(--jm-c) 25%, transparent);
}
.jm-cat:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px color-mix(in srgb, var(--jm-c) 22%, transparent);
}
.jm-cat.active {
    background: var(--jm-c);
    border-color: var(--jm-c);
    transform: translateY(-4px);
    box-shadow: 0 14px 36px color-mix(in srgb, var(--jm-c) 38%, transparent);
}

/* icon circle */
.jm-cat-icon {
    width: 44px; height: 44px; border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; margin-bottom: 11px;
    background: color-mix(in srgb, var(--jm-c) 18%, transparent);
    transition: background .15s;
}
.jm-cat.active .jm-cat-icon {
    background: rgba(255,255,255,.22);
}

.jm-cat-name {
    font-size: 13px; font-weight: 800; color: var(--u-text);
    margin-bottom: 3px; line-height: 1.2;
}
.jm-cat.active .jm-cat-name { color: #fff; }

.jm-cat-count {
    font-size: 11px;
    color: var(--jm-c);
    font-weight: 600;
}
.jm-cat.active .jm-cat-count { color: rgba(255,255,255,.75); }

/* mini progress bar */
.jm-cat-bar {
    height: 4px; border-radius: 99px; margin-top: 10px;
    background: color-mix(in srgb, var(--jm-c) 18%, transparent);
    overflow: hidden;
}
.jm-cat.active .jm-cat-bar { background: rgba(255,255,255,.2); }
.jm-cat-bar-fill {
    height: 100%; border-radius: 99px;
    background: var(--jm-c);
    transition: width .5s;
}
.jm-cat.active .jm-cat-bar-fill { background: #fff; }

/* all-done check badge */
.jm-cat-done {
    position:absolute; top:10px; right:10px;
    width:22px; height:22px; border-radius:50%;
    background:#fff; color: var(--jm-c);
    font-size:11px; font-weight:900;
    display:flex; align-items:center; justify-content:center;
    box-shadow:0 2px 6px rgba(0,0,0,.15);
}
.jm-cat.active .jm-cat-done { background:rgba(255,255,255,.3); color:#fff; }

/* ── Timeline Panel ──────────────── */
.jm-panel { display:none; animation: jm-in .22s ease; }
.jm-panel.active { display:block; }
@keyframes jm-in { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }

.jm-panel-hdr {
    display:flex; align-items:center; gap:14px;
    margin-bottom:22px; padding-bottom:16px;
    border-bottom:2px solid color-mix(in srgb,var(--jm-c) 20%,var(--u-line));
}
.jm-panel-hdr-icon {
    width:46px; height:46px; border-radius:13px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:22px;
    background: color-mix(in srgb,var(--jm-c) 14%,transparent);
    color:var(--jm-c);
}
.jm-panel-hdr-name { font-size:18px; font-weight:900; color:var(--u-text); }
.jm-panel-hdr-sub  { font-size:12px; color:var(--u-muted); margin-top:2px; }
.jm-panel-hdr-right { margin-left:auto; display:flex; flex-direction:column; align-items:flex-end; gap:4px; }
.jm-ph-pbar-wrap { width:110px; height:7px; background:var(--u-line); border-radius:99px; overflow:hidden; }
.jm-ph-pbar-fill { height:100%; border-radius:99px; background:var(--jm-c); }
.jm-ph-pct       { font-size:12px; font-weight:700; color:var(--jm-c); }

/* horizontal timeline */
.jm-tl {
    display: flex;
    align-items: flex-start;
    gap: 0;
    overflow-x: auto;
    padding-bottom: 12px;
}
.jm-tl::-webkit-scrollbar { height: 4px; }
.jm-tl::-webkit-scrollbar-track { background: var(--u-line); border-radius: 99px; }
.jm-tl::-webkit-scrollbar-thumb { background: var(--jm-c); border-radius: 99px; opacity:.5; }

.jm-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    min-width: 160px;
    position: relative;
}

/* connector line between items */
.jm-item + .jm-item::before {
    content: '';
    position: absolute;
    top: 22px;
    left: calc(-50%);
    right: calc(50%);
    height: 3px;
    background: linear-gradient(90deg, color-mix(in srgb,var(--jm-c) 30%,var(--u-line)), var(--jm-c));
    z-index: 0;
}
.jm-item.idone + .jm-item::before { background: #34d399; }

.jm-bullet {
    width: 44px; height: 44px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 900; color: #fff;
    box-shadow: 0 4px 14px rgba(0,0,0,.22);
    z-index: 2; position: relative;
    transition: transform .15s;
    flex-shrink: 0;
    margin-bottom: 12px;
}
.jm-item:hover .jm-bullet { transform: scale(1.12); }
.jm-bullet.bdone    { background: linear-gradient(135deg,#059669,#34d399); }
.jm-bullet.boverdue { background: linear-gradient(135deg,#d97706,#fbbf24); }
.jm-bullet.burgent  { background: linear-gradient(135deg,var(--jm-c),color-mix(in srgb,var(--jm-c) 65%,#7c3aed)); }
.jm-bullet.bwait    { background: linear-gradient(135deg,#94a3b8,#cbd5e1); }

.jm-card {
    background: var(--u-card); border: 1.5px solid var(--u-line);
    border-radius: 14px; padding: 14px 16px;
    width: 100%; text-align: center;
    transition: box-shadow .15s, transform .15s;
    position: relative; overflow: hidden;
}
.jm-card::before {
    content: ''; position: absolute; left: 0; right: 0; top: 0; height: 3px;
}
.jm-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.1); transform: translateY(-3px); }

.jm-card.cdone    { border-color: rgba(52,211,153,.35); }
.jm-card.cdone::before    { background: #34d399; }
.jm-card.coverdue { border-color: rgba(251,191,36,.45); }
.jm-card.coverdue::before { background: #fbbf24; }
.jm-card.curgent  { border-color: var(--jm-c); box-shadow: 0 0 0 3px color-mix(in srgb,var(--jm-c) 14%,transparent); }
.jm-card.curgent::before  { background: var(--jm-c); }
.jm-card.cwait::before    { background: var(--u-line); }

.jm-item.idone .jm-card { opacity: .72; }

.jm-card-title { font-size: 14px; font-weight: 900; color: var(--u-text); margin-bottom: 6px; line-height: 1.3; }
.jm-card-date  { font-size: 11px; color: var(--u-muted); margin-bottom: 9px; display: flex; align-items: center; justify-content: center; gap: 3px; font-weight: 500; }
.jm-card-foot  { display: flex; gap: 4px; flex-wrap: wrap; justify-content: center; }

.jm-chip {
    display:inline-flex; align-items:center; gap:3px;
    font-size:11px; font-weight:700; padding:4px 10px; border-radius:99px;
}
.jm-chip.cdone    { background:rgba(52,211,153,.12); color:#059669; }
.jm-chip.coverdue { background:rgba(251,191,36,.15);  color:#d97706; }
.jm-chip.curgent  { background:color-mix(in srgb,var(--jm-c) 14%,transparent); color:var(--jm-c); }
.jm-chip.cwait    { background:var(--u-line); color:var(--u-muted); }
.jm-chip-d        { font-size:10px; font-weight:600; padding:3px 9px; border-radius:99px; background:var(--u-line); color:var(--u-muted); }
.jm-chip-d.soon   { background:rgba(251,191,36,.15); color:#d97706; }
.jm-chip-d.late   { background:rgba(239,68,68,.1); color:#dc2626; }

/* ── ICS ─────────────────────────── */
.jm-ics-row { display:flex; justify-content:flex-end; margin-top:28px; }
.jm-ics-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 16px; border-radius:99px;
    border:1.5px solid var(--u-line); background:var(--u-card);
    font-size:12px; color:var(--u-muted); text-decoration:none;
    transition:all .15s;
}
.jm-ics-btn:hover { border-color:var(--u-brand); color:var(--u-brand); }

/* empty */
.jm-empty { text-align:center; padding:60px 20px; color:var(--u-muted); }
.jm-empty-icon { font-size:44px; margin-bottom:10px; opacity:.5; }

/* ════════════════════════════════════════
   MINIMALİST OVERRIDES
   html.jm-minimalist → premium etkileri kaldır, düz/sade stil uygula
════════════════════════════════════════ */
.jm-minimalist .jm-hero {
    background: linear-gradient(to right, var(--theme-hero-from-guest) 0%, var(--theme-hero-to-guest) 100%);
    border-radius: 12px;
    color: #fff;
    box-shadow: none;
}
.jm-minimalist .jm-hero::after { display: none; }
.jm-minimalist .jm-ring-bg  { stroke: rgba(255,255,255,.25); }
.jm-minimalist .jm-ring-fg  { stroke: #fff; filter: none; }
.jm-minimalist .jm-ring-pct { color: #fff; }
.jm-minimalist .jm-hk-val   { color: #fff !important; }
.jm-minimalist .jm-hero-next {
    background: var(--u-card, #fff);
    border: 1px solid rgba(0,0,0,.10);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.jm-minimalist .jm-hn-tag  { color: var(--u-muted, #6b7280); }
.jm-minimalist .jm-hn-name { color: var(--u-text, #111); }
.jm-minimalist .jm-hn-date { color: var(--u-muted, #6b7280); }
.jm-minimalist .jm-hn-btn  {
    background: var(--u-card, #fff);
    color: var(--u-text, #111);
    border: 1px solid var(--u-line, #e5e7eb);
}
.jm-minimalist .jm-hn-btn:hover { background: #e2e5ec; color: var(--u-text, #111); }

.jm-minimalist .jm-cat { box-shadow: none !important; }
.jm-minimalist .jm-cat:hover { box-shadow: none !important; }
.jm-minimalist .jm-cat.active { box-shadow: none !important; }

.jm-minimalist .jm-panel-hdr {
    border-bottom: 1px solid var(--u-line, #e5e7eb);
}
.jm-minimalist .jm-panel-hdr-icon { box-shadow: none; }
.jm-minimalist .jm-ph-pbar-fill { background: var(--u-text, #111); }
.jm-minimalist .jm-ph-pct       { color: var(--u-text, #111); }

.jm-minimalist .jm-item + .jm-item::before {
    background: var(--u-line, #e5e7eb);
}
.jm-minimalist .jm-item.idone + .jm-item::before {
    background: var(--u-ok, #16a34a);
}
.jm-minimalist .jm-bullet { box-shadow: none; }
.jm-minimalist .jm-bullet.bdone    { background: var(--u-ok,    #16a34a); }
.jm-minimalist .jm-bullet.boverdue { background: var(--u-warn,  #d97706); }
.jm-minimalist .jm-bullet.burgent  { background: var(--u-brand, #2563eb); }
.jm-minimalist .jm-bullet.bwait    { background: var(--u-muted, #94a3b8); }
.jm-minimalist .jm-item:hover .jm-bullet { transform: none; }

.jm-minimalist .jm-card:hover {
    box-shadow: none !important;
    transform: none !important;
}
.jm-minimalist .jm-card.curgent {
    box-shadow: none !important;
}

.jm-minimalist .jm-chip.cdone    { background: rgba(22,163,74,.10);  color: var(--u-ok,   #16a34a); }
.jm-minimalist .jm-chip.coverdue { background: rgba(217,119,6,.10);  color: var(--u-warn, #d97706); }
.jm-minimalist .jm-chip.curgent  { background: rgba(37,99,235,.10);  color: var(--u-brand,#2563eb); }
.jm-minimalist .jm-chip.cwait    { background: var(--u-line, #e5e7eb); color: var(--u-muted, #6b7280); }
.jm-minimalist .jm-chip-d.soon   { background: rgba(217,119,6,.10);  color: #d97706; }
.jm-minimalist .jm-chip-d.late   { background: rgba(220,38,38,.10);  color: #dc2626; }

.jm-minimalist .jm-tl::-webkit-scrollbar-thumb { background: var(--u-line, #e5e7eb); }
</style>
@endpush

@section('content')
@php
    $now = now();

    $catCfg = [
        'registration' => ['label'=>'Kayıt',      'icon'=>'📋', 'color'=>'#6366f1', 'order'=>1],
        'documents'    => ['label'=>'Belgeler',    'icon'=>'📄', 'color'=>'#f59e0b', 'order'=>2],
        'contract'     => ['label'=>'Sözleşme',    'icon'=>'✍️',  'color'=>'#3b82f6', 'order'=>3],
        'university'   => ['label'=>'Üniversite',  'icon'=>'🎓', 'color'=>'#8b5cf6', 'order'=>4],
        'visa'         => ['label'=>'Vize',        'icon'=>'🛂', 'color'=>'#0891b2', 'order'=>5],
        'travel'       => ['label'=>'Seyahat',     'icon'=>'✈️',  'color'=>'#f43f5e', 'order'=>6],
        'arrival'      => ['label'=>'Varış',       'icon'=>'🏁', 'color'=>'#16a34a', 'order'=>7],
    ];

    $total    = $milestones->count();
    $done     = $milestones->filter(fn($m)=>$m->isCompleted())->count();
    $overdue  = $milestones->filter(fn($m)=>$m->isOverdue())->count();
    $upcoming = $milestones->filter(fn($m)=>!$m->isCompleted()&&!$m->isOverdue())->count();
    $pct      = $total > 0 ? round($done/$total*100) : 0;

    $stageOrder = ['registration','documents','contract','university','visa','travel','arrival'];
    $grouped = $milestones->groupBy('category')
                 ->sortBy(fn($g,$k)=>$catCfg[$k]['order']??99);
    $allCats = $grouped->keys()->toArray();

    $next = $milestones->filter(fn($m)=>!$m->isCompleted())->sortBy('target_date')->first();
    $firstActiveCat = $milestones->filter(fn($m)=>!$m->isCompleted())
        ->sortBy(fn($m)=>array_search($m->category,$stageOrder))
        ->first()?->category ?? ($allCats[0]??'registration');

    $rC = 326.73;
    $rO = $rC - ($pct/100*$rC);

    $btnMap = [
        'registration' => route('guest.registration.form'),
        'documents'    => route('guest.registration.documents'),
        'contract'     => route('guest.contract'),
    ];
@endphp

{{-- ══ HERO ══ --}}
<div class="jm-hero">
    <div class="jm-ring-wrap">
        <svg class="jm-ring-svg" width="106" height="106" viewBox="0 0 120 120">
            <circle class="jm-ring-bg" cx="60" cy="60" r="52"/>
            <circle class="jm-ring-fg" cx="60" cy="60" r="52"
                    stroke-dasharray="{{ $rC }}" stroke-dashoffset="{{ $rO }}"/>
        </svg>
        <div class="jm-ring-inner">
            <div class="jm-ring-pct">%{{ $pct }}</div>
            <div class="jm-ring-lbl">İlerleme</div>
        </div>
    </div>

    <div class="jm-hero-body">
        <div class="jm-hero-title">Almanya Yolculuğun</div>
        <div class="jm-hero-sub">{{ $done }} tamamlandı · {{ $total-$done }} devam ediyor</div>
        <div class="jm-hero-kpis">
            <div class="jm-hk">
                <div class="jm-hk-val" style="color:#34d399">{{ $done }}</div>
                <div class="jm-hk-lbl">Tamamlandı</div>
            </div>
            <div class="jm-hk">
                <div class="jm-hk-val" style="color:#fbbf24">{{ $overdue }}</div>
                <div class="jm-hk-lbl">Gecikmiş</div>
            </div>
            <div class="jm-hk">
                <div class="jm-hk-val" style="color:#7dd3fc">{{ $upcoming }}</div>
                <div class="jm-hk-lbl">Bekliyor</div>
            </div>
        </div>
    </div>

    @if($next)
    @php
        $nDays = (int)$now->diffInDays($next->target_date,false);
        $nMeta = $catCfg[$next->category]??['icon'=>'📌'];
        $nBtn  = $btnMap[$next->category]??null;
    @endphp
    <div class="jm-hero-next">
        <div class="jm-hn-tag">Sıradaki Adım</div>
        <div class="jm-hn-name">{{ $nMeta['icon'] }} {{ $next->label }}</div>
        <div class="jm-hn-date">
            🗓 {{ $next->target_date->format('d M Y') }}
            @if($nDays>=0) · <strong>{{ $nDays }}g kaldı</strong>
            @else · <strong style="color:#fbbf24">{{ abs($nDays) }}g geçti</strong>
            @endif
        </div>
        @if($nBtn)<a href="{{ $nBtn }}" class="jm-hn-btn">Hemen Başla →</a>@endif
    </div>
    @endif
</div>

{{-- ══ CATEGORY CARDS ══ --}}
<div class="jm-cats">
    @foreach($grouped as $cat => $items)
    @php
        $cfg     = $catCfg[$cat]??['label'=>$cat,'icon'=>'📌','color'=>'#6366f1'];
        $cDone   = $items->filter(fn($m)=>$m->isCompleted())->count();
        $cTot    = $items->count();
        $cPct    = $cTot>0 ? round($cDone/$cTot*100) : 0;
        $allDone = $cDone===$cTot && $cTot>0;
    @endphp
    <div class="jm-cat {{ $cat===$firstActiveCat?'active':'' }}"
         id="cat-{{ $cat }}"
         style="--jm-c:{{ $cfg['color'] }}"
         onclick="switchCat('{{ $cat }}')">
        @if($allDone)<div class="jm-cat-done">✓</div>@endif
        <div class="jm-cat-icon">{{ $cfg['icon'] }}</div>
        <div class="jm-cat-name">{{ $cfg['label'] }}</div>
        <div class="jm-cat-count">{{ $cDone }}/{{ $cTot }} tamamlandı</div>
        <div class="jm-cat-bar">
            <div class="jm-cat-bar-fill" style="width:{{ $cPct }}%"></div>
        </div>
    </div>
    @endforeach
</div>

{{-- ══ PANELS ══ --}}
@foreach($grouped as $cat => $items)
@php
    $cfg   = $catCfg[$cat]??['label'=>$cat,'icon'=>'📌','color'=>'#6366f1'];
    $cDone = $items->filter(fn($m)=>$m->isCompleted())->count();
    $cTot  = $items->count();
    $cPct  = $cTot>0 ? round($cDone/$cTot*100) : 0;
@endphp
<div class="jm-panel {{ $cat===$firstActiveCat?'active':'' }}"
     id="panel-{{ $cat }}"
     style="--jm-c:{{ $cfg['color'] }}">

    <div class="jm-panel-hdr">
        <div class="jm-panel-hdr-icon">{{ $cfg['icon'] }}</div>
        <div>
            <div class="jm-panel-hdr-name">{{ $cfg['label'] }}</div>
            <div class="jm-panel-hdr-sub">{{ $cDone }} / {{ $cTot }} adım tamamlandı</div>
        </div>
        <div class="jm-panel-hdr-right">
            <div class="jm-ph-pbar-wrap">
                <div class="jm-ph-pbar-fill" style="width:{{ $cPct }}%"></div>
            </div>
            <div class="jm-ph-pct">%{{ $cPct }}</div>
        </div>
    </div>

    @if($items->isEmpty())
        <div class="jm-empty">
            <div class="jm-empty-icon">📭</div>
            <div>Bu kategoride adım yok.</div>
        </div>
    @else
    <div class="jm-tl">
        @foreach($items as $m)
        @php
            $isC = $m->isCompleted();
            $isO = $m->isOverdue();
            $dL  = (int)$now->diffInDays($m->target_date,false);
            $isU = !$isC && !$isO && $dL<=7;

            $cls = $isC?'cdone':($isO?'coverdue':($isU?'curgent':'cwait'));
            $bCl = $isC?'bdone':($isO?'boverdue':($isU?'burgent':'bwait'));
            $chT = $isC?'✓ Tamamlandı':($isO?'⚠ Gecikmiş':($isU?'⏰ Yaklaşıyor':'Bekliyor'));
            $dT  = !$isC ? ($isO ? abs($dL).'g geçti' : $dL.'g kaldı') : '';
            $dCl = $isO?'late':($isU?'soon':'');
        @endphp
        <div class="jm-item {{ $isC?'idone':'' }}">
            <div class="jm-bullet {{ $bCl }}">{{ $isC?'✓':($m->sort_order+1) }}</div>
            <div class="jm-card {{ $cls }}">
                <div class="jm-card-title">{{ $m->label }}</div>
                <div class="jm-card-date">
                    <svg width="11" height="11" viewBox="0 0 16 16" fill="none"><rect x="1" y="3" width="14" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M5 1v4M11 1v4M1 7h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    {{ $m->target_date->format('d M Y') }}
                    @if($isC && $m->completed_at) &nbsp;·&nbsp; ✓ {{ $m->completed_at->format('d M') }}'de tamamlandı @endif
                </div>
                <div class="jm-card-foot">
                    <span class="jm-chip {{ $cls }}">{{ $chT }}</span>
                    @if($dT)<span class="jm-chip-d {{ $dCl }}">{{ $dT }}</span>@endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endforeach

<div class="jm-ics-row">
    <a href="{{ route('guest.timeline.export') }}" class="jm-ics-btn">
        <svg width="13" height="13" viewBox="0 0 16 16" fill="none"><rect x="1" y="3" width="14" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M5 1v4M11 1v4M1 7h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Takvime Aktar (.ics)
    </a>
</div>
@endsection

@push('scripts')
<script>
// Sync jm-minimalist class when design toggles at runtime
(function(){
    var _orig = window.__designToggle;
    window.__designToggle = function(){
        if(_orig) _orig.apply(this, arguments);
        setTimeout(function(){
            var isMin = localStorage.getItem('mentorde_design') === 'minimalist';
            document.documentElement.classList.toggle('jm-minimalist', isMin);
        }, 50);
    };
})();

function switchCat(cat) {
    document.querySelectorAll('.jm-panel').forEach(function(p){ p.classList.remove('active'); });
    document.querySelectorAll('.jm-cat').forEach(function(c){ c.classList.remove('active'); });
    var panel = document.getElementById('panel-' + cat);
    var card  = document.getElementById('cat-'   + cat);
    if (panel) panel.classList.add('active');
    if (card)  card.classList.add('active');
}
</script>
@endpush
