@extends('guest.layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
/* ── Guest Dashboard — gd-* scoped ── */
.top { display: none !important; }

/* Hero */
.gd-hero {
    background: var(--hero-gradient);
    border-radius: 14px;
    padding: 28px 28px 28px 32px;
    position: relative; overflow: hidden;
    display: flex; align-items: center; justify-content: space-between; gap: 24px;
    margin-bottom: 20px; color: #fff;
}
.gd-hero::before {
    content: ''; position: absolute; top: -40px; right: -40px;
    width: 220px; height: 220px; border-radius: 50%;
    background: rgba(255,255,255,.08); pointer-events: none;
}
.gd-hero::after {
    content: ''; position: absolute; bottom: -60px; right: 110px;
    width: 160px; height: 160px; border-radius: 50%;
    background: rgba(255,255,255,.05); pointer-events: none;
}
.gd-hero-text { position: relative; z-index: 1; flex: 1; min-width: 0; }
.gd-hero-greeting { font-size: 13px; opacity: .85; margin-bottom: 4px; }
.gd-hero-title    { font-size: 26px; font-weight: 800; margin-bottom: 6px; letter-spacing: -.3px; line-height: 1.2; }
.gd-hero-sub      { font-size: 13px; opacity: .8; margin-bottom: 16px; line-height: 1.5; }
.gd-hero-cta {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 20px; border-radius: 8px;
    background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.3);
    color: #fff; font-size: 13px; font-weight: 600;
    text-decoration: none; backdrop-filter: blur(4px); transition: background .15s;
}
.gd-hero-cta:hover { background: rgba(255,255,255,.3); text-decoration: none; color: #fff; }

/* SVG Progress Ring */
.gd-ring { position: relative; width: 100px; height: 100px; flex-shrink: 0; z-index: 1; }
.gd-ring svg { transform: rotate(-90deg); }
.gd-ring-bg   { fill: none; stroke: rgba(255,255,255,.2); stroke-width: 8; }
.gd-ring-fill { fill: none; stroke: #fff; stroke-width: 8; stroke-linecap: round;
                stroke-dasharray: 251.2; transition: stroke-dashoffset 1s ease; }
.gd-ring-label {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    text-align: center; color: #fff;
}
.gd-ring-pct { font-size: 20px; font-weight: 800; line-height: 1; }
.gd-ring-lbl { font-size: 10px; opacity: .8; }

/* Timeline Card */
.gd-tl-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; padding: 20px 24px;
    margin-bottom: 20px; overflow-x: auto;
}
.gd-tl-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
.gd-tl-title { font-size: 15px; font-weight: 700; color: var(--u-text); }
.gd-tl-link  { font-size: 13px; color: var(--u-brand); text-decoration: none; font-weight: 500; }
.gd-tl-link:hover { text-decoration: underline; }
.gd-tl-steps { display: flex; align-items: flex-start; gap: 0; min-width: 520px; }
.gd-tl-step  { flex: 1; display: flex; flex-direction: column; align-items: center; position: relative; }
.gd-tl-step:not(:last-child)::after {
    content: ''; position: absolute;
    top: 15px; left: calc(50% + 15px);
    width: calc(100% - 30px); height: 2px;
    background: var(--u-line);
}
.gd-tl-step.done:not(:last-child)::after { background: var(--u-ok); }
.gd-tl-dot {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700;
    border: 2px solid var(--u-line);
    background: var(--u-card); color: var(--u-muted);
    position: relative; z-index: 1; flex-shrink: 0;
}
.gd-tl-step.done   .gd-tl-dot { background: var(--u-ok);    border-color: var(--u-ok);    color: #fff; }
.gd-tl-step.active .gd-tl-dot {
    background: var(--u-brand); border-color: var(--u-brand); color: #fff;
    box-shadow: 0 0 0 4px rgba(37,99,235,.15);
    animation: gd-pulse-ring 2s infinite;
}
@keyframes gd-pulse-ring {
    0%   { box-shadow: 0 0 0 0   rgba(37,99,235,.4); }
    70%  { box-shadow: 0 0 0 8px rgba(37,99,235,.0); }
    100% { box-shadow: 0 0 0 0   rgba(37,99,235,.0); }
}
.gd-tl-label {
    margin-top: 8px; font-size: 11px; font-weight: 600;
    color: var(--u-muted); text-align: center; line-height: 1.3;
}
.gd-tl-step.done   .gd-tl-label { color: var(--u-ok); }
.gd-tl-step.active .gd-tl-label { color: var(--u-brand); }

/* KPI Row */
.gd-kpi-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 20px; }
@media(max-width:860px){ .gd-kpi-row { grid-template-columns: repeat(2,1fr); } }
@media(max-width:480px){ .gd-kpi-row { grid-template-columns: 1fr; } }
.gd-kpi-card {
    background: var(--u-card); border: 1px solid var(--u-line); border-radius: 14px;
    padding: 18px 20px; display: flex; align-items: flex-start; gap: 14px;
    box-shadow: var(--u-shadow); transition: box-shadow .2s, transform .2s;
}
.gd-kpi-card:hover { box-shadow: var(--u-shadow-md); transform: translateY(-1px); }
.gd-kpi-icon {
    width: 44px; height: 44px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;
}
.gd-kpi-icon.blue   { background: rgba(37,99,235,.12); }
.gd-kpi-icon.green  { background: rgba(22,163,74,.12); }
.gd-kpi-icon.orange { background: rgba(217,119,6,.12); }
.gd-kpi-icon.purple { background: rgba(124,58,237,.12); }
.gd-kpi-val   { font-size: 30px; font-weight: 800; color: var(--u-text); line-height: 1; }
.gd-kpi-label { font-size: 16px; color: var(--u-muted); margin-top: 4px; }
.gd-kpi-trend { font-size: 14px; margin-top: 6px; font-weight: 600; }
.gd-kpi-trend.ok   { color: var(--u-ok); }
.gd-kpi-trend.warn { color: var(--u-warn); }
.gd-kpi-trend.info { color: var(--u-brand); }

/* Layout Grids */
.gd-col31 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; align-items: stretch; }
.gd-col2  { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.gd-col1-right { display: flex; flex-direction: column; gap: 16px; height: 100%; }
@media(max-width:860px){ .gd-col31, .gd-col2 { grid-template-columns: 1fr; } }
.gd-inner2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:stretch; }
.gd-inner2 > .gd-card { display:flex; flex-direction:column; height:100%; }
.gd-inner2 > .gd-card > .gd-card-body { flex:1; }
@media(max-width:640px){ .gd-inner2 { grid-template-columns:1fr; } }

/* Card */
.gd-card {
    background: var(--u-card); border: 1px solid var(--u-line);
    border-radius: 14px; overflow: hidden;
}
.gd-card-head {
    padding: 14px 18px; border-bottom: 1px solid var(--u-line);
    display: flex; align-items: center; justify-content: space-between;
}
.gd-card-title  { font-size: 17px; font-weight: 700; color: var(--u-text); }
.gd-card-action { font-size: 15px; color: var(--u-brand); text-decoration: none; font-weight: 600; }
.gd-card-action:hover { text-decoration: underline; }
.gd-card-body   { padding: 16px 18px; }

/* Doc Items */
.gd-doc-list { display: flex; flex-direction: column; gap: 8px; }
.gd-doc-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 12px; border: 1px solid var(--u-line);
    border-radius: 8px; background: var(--u-subtle, #f8fafc);
    transition: border-color .15s, background .15s;
}
.gd-doc-item:hover { border-color: var(--u-brand); background: rgba(37,99,235,.04); }
.gd-doc-icon {
    width: 36px; height: 36px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;
}
.gd-doc-icon.ok     { background: rgba(22,163,74,.12); }
.gd-doc-icon.warn   { background: rgba(217,119,6,.12); }
.gd-doc-icon.danger { background: rgba(220,38,38,.12); }
.gd-doc-name  { font-size: 13px; font-weight: 600; color: var(--u-text); }
.gd-doc-meta  { font-size: 11px; color: var(--u-muted); margin-top: 2px; }

/* Progress bar */
.gd-progress-bar  { width: 100%; height: 8px; background: var(--u-line); border-radius: 4px; overflow: hidden; margin: 10px 0 4px; }
.gd-progress-fill { height: 100%; border-radius: 4px; background: var(--u-brand); transition: width .6s ease; }
.gd-progress-fill.ok   { background: var(--u-ok); }
.gd-progress-fill.warn { background: var(--u-warn); }

/* Appointment item */
.gd-appt-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 12px; border: 1px solid var(--u-line);
    border-radius: 8px; background: var(--u-subtle, #f8fafc);
    margin-bottom: 8px;
}
.gd-appt-item:last-child { margin-bottom: 0; }
.gd-appt-date { width: 44px; text-align: center; border-right: 1px solid var(--u-line); padding-right: 12px; flex-shrink: 0; }
.gd-appt-day   { font-size: 22px; font-weight: 800; color: var(--u-brand); line-height: 1; }
.gd-appt-month { font-size: 10px; color: var(--u-muted); text-transform: uppercase; letter-spacing: .05em; }
.gd-appt-title { font-size: 13px; font-weight: 600; color: var(--u-text); }
.gd-appt-time  { font-size: 11px; color: var(--u-muted); margin-top: 2px; }

/* Quick action buttons */
.gd-qa-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.gd-qa-btn {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 12px; border-radius: 8px;
    border: 1px solid var(--u-line); background: var(--u-subtle, #f8fafc);
    text-decoration: none; color: var(--u-text);
    font-size: 12px; font-weight: 500; transition: all .15s;
}
.gd-qa-btn:hover { border-color: var(--u-brand); background: rgba(37,99,235,.05); color: var(--u-brand); text-decoration: none; }

/* Message thread */
.gd-msg-thread {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 0; border-bottom: 1px solid var(--u-line); cursor: pointer;
}
.gd-msg-thread:last-child { border-bottom: none; }
.gd-msg-av {
    width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 12px;
}
.gd-msg-name    { font-size: 13px; font-weight: 600; color: var(--u-text); }
.gd-msg-preview { font-size: 12px; color: var(--u-muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }
.gd-msg-time    { font-size: 11px; color: var(--u-muted); margin-left: auto; flex-shrink: 0; }
.gd-msg-dot     { width: 8px; height: 8px; border-radius: 50%; background: var(--u-brand); flex-shrink: 0; }

/* Cost row */
.gd-cost-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; border-bottom: 1px solid var(--u-line); }
.gd-cost-row:last-child { border-bottom: none; font-weight: 700; }
.gd-cost-label { font-size: 13px; color: var(--u-muted); }
.gd-cost-val   { font-size: 13px; font-weight: 600; color: var(--u-text); }
.gd-cost-total .gd-cost-label { color: var(--u-text); }
.gd-cost-total .gd-cost-val   { color: var(--u-brand); font-size: 16px; }

/* Banner */
.gd-banner {
    border-radius: 12px; padding: 14px 18px;
    display: flex; align-items: center; gap: 14px;
    margin-bottom: 14px; flex-wrap: wrap;
}
.gd-banner.info    { background: linear-gradient(90deg,#eff6ff,#dbeafe); border: 1.5px solid #93c5fd; }
.gd-banner.success { background: linear-gradient(90deg,#f0fdf4,#dcfce7); border: 1.5px solid #86efac; }
.gd-banner.warn    { background: #fffbeb; border: 1.5px solid #fde68a; }
.gd-banner-icon    { font-size: 26px; line-height: 1; flex-shrink: 0; }
.gd-banner-eyebrow { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #3b82f6; margin-bottom: 3px; }
.gd-banner-title   { font-weight: 700; font-size: 14px; color: var(--u-text); }
.gd-banner-sub     { font-size: 12px; color: var(--u-muted); }

/* Maliyet details toggle */
details[open] > summary span:first-child { transform: rotate(90deg); display: inline-block; }
details > summary::-webkit-details-marker { display: none; }

/* Guide link hover */
.gd-guide-link:hover {
    border-color: var(--u-brand) !important;
    background: rgba(37,99,235,.05) !important;
    transform: translateY(-2px);
    color: var(--u-brand) !important;
    text-decoration: none;
}

/* Service promo cards */
.gd-svc-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:1100px){ .gd-svc-grid { grid-template-columns:repeat(3,1fr); } }
@media(max-width:680px){  .gd-svc-grid { grid-template-columns:repeat(2,1fr); } }
.gd-svc-card {
    display:flex; flex-direction:column; gap:0;
    border-radius:16px; border:1.5px solid transparent;
    text-decoration:none; overflow:hidden;
    background:#fff;
    box-shadow:0 1px 4px rgba(0,0,0,.06);
    transition:transform .18s, box-shadow .18s;
}
.gd-svc-card:hover { transform:translateY(-4px); box-shadow:0 10px 28px rgba(0,0,0,.12); text-decoration:none; }
.gd-svc-accent { height:4px; width:100%; }
.gd-svc-body   { display:flex; flex-direction:column; gap:8px; padding:18px 16px 16px; flex:1; }
.gd-svc-icon   { width:46px; height:46px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; margin-bottom:4px; }
.gd-svc-title  { font-size:var(--tx-sm); font-weight:800; margin-bottom:2px; }
.gd-svc-desc   { font-size:var(--tx-xs); color:var(--u-muted,#6b7280); line-height:1.5; flex:1; }
.gd-svc-link   { font-size:var(--tx-xs); font-weight:700; margin-top:8px; display:inline-flex; align-items:center; gap:4px; }
.gd-svc-link::after { content:'→'; transition:transform .15s; }
.gd-svc-card:hover .gd-svc-link::after { transform:translateX(3px); }

.gd-svc-card.blue   { border-color:#bfdbfe; }
.gd-svc-card.blue   .gd-svc-accent { background:linear-gradient(90deg,#2563eb,#0891b2); }
.gd-svc-card.blue   .gd-svc-icon   { background:#dbeafe; }
.gd-svc-card.blue   .gd-svc-title  { color:#1d4ed8; }
.gd-svc-card.blue   .gd-svc-link   { color:#2563eb; }
.gd-svc-card.purple { border-color:#ddd6fe; }
.gd-svc-card.purple .gd-svc-accent { background:linear-gradient(90deg,#7c3aed,#a855f7); }
.gd-svc-card.purple .gd-svc-icon   { background:#ede9fe; }
.gd-svc-card.purple .gd-svc-title  { color:#6d28d9; }
.gd-svc-card.purple .gd-svc-link   { color:#7c3aed; }
.gd-svc-card.green  { border-color:#bbf7d0; }
.gd-svc-card.green  .gd-svc-accent { background:linear-gradient(90deg,#059669,#0891b2); }
.gd-svc-card.green  .gd-svc-icon   { background:#dcfce7; }
.gd-svc-card.green  .gd-svc-title  { color:#15803d; }
.gd-svc-card.green  .gd-svc-link   { color:#16a34a; }
.gd-svc-card.orange { border-color:#fed7aa; }
.gd-svc-card.orange .gd-svc-accent { background:linear-gradient(90deg,#d97706,#dc2626); }
.gd-svc-card.orange .gd-svc-icon   { background:#fef3c7; }
.gd-svc-card.orange .gd-svc-title  { color:#b45309; }
.gd-svc-card.orange .gd-svc-link   { color:#d97706; }
.gd-svc-card.red    { border-color:#fecdd3; }
.gd-svc-card.red    .gd-svc-accent { background:linear-gradient(90deg,#e11d48,#f59e0b); }
.gd-svc-card.red    .gd-svc-icon   { background:#fff1f2; }
.gd-svc-card.red    .gd-svc-title  { color:#be123c; }
.gd-svc-card.red    .gd-svc-link   { color:#e11d48; }

/* ⭐ Yıldız Oyuncu Kartı */
.gd-svc-card.star {
    border-color:#fbbf24;
    box-shadow:0 0 0 1px #fbbf24, 0 4px 20px rgba(251,191,36,.30);
    position:relative;
}
.gd-svc-card.star:hover {
    transform:translateY(-6px);
    box-shadow:0 0 0 2px #f59e0b, 0 14px 36px rgba(251,191,36,.40);
}
.gd-svc-card.star .gd-svc-accent {
    background:linear-gradient(90deg,#f59e0b,#e11d48,#f59e0b);
    background-size:200% 100%;
    animation:shimmer 2.4s linear infinite;
}
@keyframes shimmer { 0%{background-position:100% 0} 100%{background-position:-100% 0} }
.gd-svc-card.star .gd-svc-icon   { background:#fef9c3; }
.gd-svc-card.star .gd-svc-title  { color:#92400e; }
.gd-svc-card.star .gd-svc-link   { color:#d97706; }
.gd-svc-star-badge {
    position:absolute; top:10px; right:10px;
    background:linear-gradient(135deg,#f59e0b,#e11d48);
    color:#fff; font-size:10px; font-weight:800;
    padding:2px 7px; border-radius:20px;
    letter-spacing:.04em; line-height:1.6;
    box-shadow:0 2px 6px rgba(245,158,11,.4);
}

/* Social proof block */
.gd-social-proof {
    background:var(--hero-gradient);
    border-radius:14px; padding:18px; color:#fff; overflow:hidden; position:relative;
}
.gd-social-proof::before {
    content:''; position:absolute; top:-24px; right:-24px;
    width:110px; height:110px; border-radius:50%;
    background:rgba(255,255,255,.07); pointer-events:none;
}
.gd-sp-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:10px; }
.gd-sp-stat {
    text-align:center; background:rgba(255,255,255,.13);
    border-radius:10px; padding:10px 6px;
}
.gd-sp-val { font-size:20px; font-weight:800; line-height:1; }
.gd-sp-lbl { font-size:10px; opacity:.8; margin-top:3px; }
.gd-sp-cta {
    display:block; margin-top:12px; text-align:center;
    background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.3);
    border-radius:8px; padding:8px; font-size:12px;
    font-weight:600; color:#fff; text-decoration:none;
    transition:background .15s;
}
.gd-sp-cta:hover { background:rgba(255,255,255,.3); color:#fff; text-decoration:none; }

/* Referral promo banner */
.gd-ref-promo {
    background:linear-gradient(135deg,#065f46 0%,#059669 100%);
    border-radius:14px; padding:22px 24px; color:#fff;
    display:flex; align-items:center; gap:20px;
    overflow:hidden; position:relative;
}
.gd-ref-promo::before {
    content:''; position:absolute; right:-30px; top:-30px;
    width:140px; height:140px; border-radius:50%;
    background:rgba(255,255,255,.07); pointer-events:none;
}
.gd-ref-promo::after {
    content:''; position:absolute; left:60px; bottom:-40px;
    width:100px; height:100px; border-radius:50%;
    background:rgba(255,255,255,.05); pointer-events:none;
}
.gd-ref-emoji { font-size:44px; flex-shrink:0; position:relative; z-index:1; }
.gd-ref-body  { flex:1; min-width:0; position:relative; z-index:1; }
.gd-ref-eyebrow { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; opacity:.75; margin-bottom:4px; }
.gd-ref-title   { font-size:16px; font-weight:800; line-height:1.3; margin-bottom:4px; }
.gd-ref-sub     { font-size:12px; opacity:.85; margin-bottom:12px; }
.gd-ref-input   { display:flex; align-items:center; gap:8px; }
.gd-ref-code    { font-family:monospace; font-size:13px; font-weight:700; background:rgba(255,255,255,.2); border-radius:6px; padding:6px 10px; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.gd-ref-copy    { background:rgba(255,255,255,.9); color:#059669; border:none; border-radius:6px; padding:6px 14px; font-size:12px; font-weight:700; cursor:pointer; flex-shrink:0; }

/* Bottom strip */
.gd-bottom-grid { display:grid; grid-template-columns:2fr 1fr 1fr; gap:16px; margin-bottom:20px; }
@media(max-width:860px){ .gd-bottom-grid { grid-template-columns:1fr; } }

/* ════════════════════════════════════════
   MINIMALİST OVERRIDES
════════════════════════════════════════ */
.jm-minimalist .gd-hero::before,
.jm-minimalist .gd-hero::after { display: none; }
</style>
@endpush

@section('content')
@php
    $docsRequiredTotal    = (int) ($docsChecklistStats['required_total']   ?? 0);
    $docsRequiredUploaded = (int) ($docsChecklistStats['required_uploaded'] ?? 0);
    $docsPct = (int) ($docsChecklistStats['percent'] ?? 0);

    $doneCount  = collect($progressVisual ?? $progress ?? [])->where('done', true)->count();
    $totalSteps = max(1, count($progressVisual ?? $progress ?? []));
    $overallPct = (int) ($progressPercent ?? round($doneCount / $totalSteps * 100));

    // SVG ring: circumference = 2π×40 ≈ 251.2
    $ringOffset = round(251.2 - ($overallPct / 100 * 251.2));

    $guestFirstName = trim((string) ($guest?->first_name ?? ''));
    $guestFullName  = trim((string) ($guest?->first_name . ' ' . $guest?->last_name));
    $guestInitials  = strtoupper(substr($guestFirstName ?: 'GU', 0, 2));

    $heroTitle = match(true) {
        ($contractStatus ?? '') === 'approved'        => 'Kayıt Tamamlandı 🎓',
        ($contractStatus ?? '') === 'signed_uploaded' => 'Sözleşmeniz İnceleniyor',
        ($conversionReady ?? false)                   => 'Tüm Adımlar Tamamlandı!',
        $overallPct >= 80                             => 'Neredeyse Hazırsınız!',
        $overallPct >= 50                             => 'Güzel İlerliyorsunuz',
        $overallPct >= 20                             => 'Başvurunuz Devam Ediyor',
        default                                       => 'Almanya Yolculuğunuz Başlıyor',
    };
    $heroSub = $heroNextStep
        ? 'Sıradaki adımınız: ' . ($heroNextStep['label'] ?? '') . '. ' . ($motivationMessage['text'] ?? '')
        : ($motivationMessage['text'] ?? 'Danışmanınız size yardımcı olmak için burada.');
@endphp

{{-- ── HERO ── --}}
<div class="gd-hero">
    <div class="gd-hero-text">
        <div class="gd-hero-greeting">Merhaba{{ $guestFirstName !== '' ? ', ' . $guestFirstName : '' }}! 👋</div>
        <div class="gd-hero-title">{{ $heroTitle }}</div>
        <div class="gd-hero-sub">{{ $heroSub }}</div>
        @if($heroNextStep)
        <a href="{{ $heroNextStep['url'] }}" class="gd-hero-cta">
            <span>{{ $heroNextStep['icon'] }}</span>
            {{ $heroNextStep['cta_text'] }}
            <span style="opacity:.7;font-size:var(--tx-xs);"> · {{ $heroNextStep['estimated_time'] }}</span>
        </a>
        @else
        <span class="gd-hero-cta" style="cursor:default;">🎉 Başvurunuz tamamlandı!</span>
        @endif
    </div>
    <div class="gd-ring">
        <svg viewBox="0 0 100 100" width="100" height="100">
            <circle class="gd-ring-bg" cx="50" cy="50" r="40"/>
            <circle class="gd-ring-fill" cx="50" cy="50" r="40"
                    style="stroke-dashoffset:{{ $ringOffset }}"/>
        </svg>
        <div class="gd-ring-label">
            <div class="gd-ring-pct">{{ $overallPct }}%</div>
            <div class="gd-ring-lbl">Tamamlandı</div>
        </div>
    </div>
</div>

{{-- ── TIMELINE ── --}}
<div class="gd-tl-card">
    <div class="gd-tl-head">
        <div class="gd-tl-title">📋 Başvuru Süreci</div>
        @if($heroNextStep)
        <a href="{{ $heroNextStep['url'] }}" class="gd-tl-link">{{ $heroNextStep['cta_text'] }} →</a>
        @else
        <span class="badge ok" style="font-size:var(--tx-xs);">✓ Tüm Adımlar Tamamlandı</span>
        @endif
    </div>
    <div class="gd-tl-steps">
        @foreach($progressVisual as $step)
        @php $stepCls = $step['done'] ? 'done' : ($step['color'] === 'blue-pulse' ? 'active' : ''); @endphp
        <div class="gd-tl-step {{ $stepCls }}">
            <div class="gd-tl-dot">{{ $step['done'] ? '✓' : $step['icon'] }}</div>
            <div class="gd-tl-label">{{ $step['label'] }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- ── Onboarding Banner ── --}}
@if($onboardingPending ?? false)
<div class="gd-banner info" style="margin-bottom:16px;cursor:pointer;" onclick="
    if(document.getElementById('ob-modal-overlay')){
        document.getElementById('ob-modal-overlay').style.display='flex';
    }">
    <div class="gd-banner-icon">🚀</div>
    <div style="flex:1;">
        <div class="gd-banner-eyebrow">Hoş Geldiniz</div>
        <div class="gd-banner-title">Başlangıç adımlarınız hazır</div>
        <div class="gd-banner-sub">Başvuru sürecinizi hızlandırmak için birkaç kısa adımı tamamlayın.</div>
    </div>
    <button class="btn ok" style="font-size:var(--tx-sm);flex-shrink:0;" onclick="event.stopPropagation();
        if(document.getElementById('ob-modal-overlay')){
            document.getElementById('ob-modal-overlay').style.display='flex';
        }">Başlayın →</button>
</div>
@endif

{{-- ── Eksik Belge Uyarısı ── --}}
@php $missingDocList = $missingRequiredDocuments ?? []; @endphp
@if(count($missingDocList) > 0)
<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 16px;border:1.5px solid #fecaca;border-radius:12px;background:#fff5f5;margin-bottom:16px;">
    <span style="font-size:var(--tx-xl);">📋</span>
    <div style="flex:1;min-width:0;">
        <div style="font-size:var(--tx-sm);font-weight:700;color:#9f1c1c;">{{ count($missingDocList) }} zorunlu belge eksik</div>
        <div style="font-size:var(--tx-xs);color:#b91c1c;margin-top:2px;">
            {{ collect($missingDocList)->pluck('name')->take(3)->implode(', ') }}{{ count($missingDocList) > 3 ? ' ve daha fazlası...' : '' }}
        </div>
    </div>
    <a href="{{ route('guest.registration.documents') }}" class="btn warn" style="font-size:var(--tx-xs);padding:6px 16px;white-space:nowrap;flex-shrink:0;">Belgelere Git →</a>
</div>
@endif

{{-- ── 1. KPI ROW ── --}}
@php
    $selectedPkg = $selectedPackage ?? null;
    $pkgPrice    = (float) ($selectedPkg?->price ?? $selectedPkg?->price_amount ?? 0);
    $pkgName     = $selectedPkg?->name ?? null;
    $eurRate     = $eurTryRate ?? null;
    $dc          = $dashboardCity ?? [];
    $nextAppt    = ($upcomingAppointments ?? collect())->first();
@endphp
<div class="gd-kpi-row">
    {{-- Belgeler --}}
    <div class="gd-kpi-card">
        <div class="gd-kpi-icon blue">📄</div>
        <div>
            <div class="gd-kpi-val">{{ $docsRequiredUploaded }}/{{ $docsRequiredTotal }}</div>
            <div class="gd-kpi-label">Belge Tamamlandı</div>
            @if($docsRequiredTotal > $docsRequiredUploaded)
                <div class="gd-kpi-trend warn">△ {{ $docsRequiredTotal - $docsRequiredUploaded }} eksik</div>
                <div class="gd-progress-bar" style="margin-top:8px;">
                    <div class="gd-progress-fill {{ $docsPct >= 80 ? 'ok' : 'warn' }}" style="width:{{ $docsPct }}%"></div>
                </div>
            @else
                <div class="gd-kpi-trend ok">✓ Tüm belgeler tamam</div>
            @endif
        </div>
    </div>

    {{-- Sonraki Randevu --}}
    <div class="gd-kpi-card">
        <div class="gd-kpi-icon green">📅</div>
        <div>
            @if($nextAppt)
                <div class="gd-kpi-val">{{ \Carbon\Carbon::parse($nextAppt->scheduled_at)->format('d M') }}</div>
                <div class="gd-kpi-label">Sonraki Randevu</div>
                <div class="gd-kpi-trend info">{{ $nextAppt->meeting_platform ?? 'Online' }} · {{ \Carbon\Carbon::parse($nextAppt->scheduled_at)->format('H:i') }}</div>
            @else
                <div class="gd-kpi-val" style="font-size:var(--tx-lg);">—</div>
                <div class="gd-kpi-label">Sonraki Randevu</div>
                <div class="gd-kpi-trend" style="color:var(--u-muted);">Planlanmış randevu yok</div>
            @endif
        </div>
    </div>

    {{-- Toplam Ücret --}}
    <div class="gd-kpi-card">
        <div class="gd-kpi-icon orange">💰</div>
        <div>
            @if($pkgPrice > 0)
                <div class="gd-kpi-val">€ {{ number_format($pkgPrice, 0, ',', '.') }}</div>
                <div class="gd-kpi-label">Toplam Ücret</div>
                @if($eurRate)
                    <div class="gd-kpi-trend info">≈ ₺ {{ number_format($pkgPrice * $eurRate, 0, ',', '.') }}</div>
                @endif
            @else
                <div class="gd-kpi-val" style="font-size:var(--tx-lg);">—</div>
                <div class="gd-kpi-label">Toplam Ücret</div>
                <div class="gd-kpi-trend info"><a href="{{ route('guest.services') }}" style="color:var(--u-brand);">Paket seç →</a></div>
            @endif
        </div>
    </div>

    {{-- Yeni Mesaj --}}
    <div class="gd-kpi-card">
        <div class="gd-kpi-icon purple">💬</div>
        <div>
            <div class="gd-kpi-val">{{ (int)($ticketSummary['unread_like'] ?? 0) }}</div>
            <div class="gd-kpi-label">Yeni Mesaj</div>
            @if(($ticketSummary['unread_like'] ?? 0) > 0)
                <div class="gd-kpi-trend warn">↑ Danışmandan</div>
            @else
                <div class="gd-kpi-trend ok">✓ Okunmamış yok</div>
            @endif
        </div>
    </div>
</div>

{{-- ── Hizmetler Promo Şeridi ── --}}
<div class="gd-svc-grid">
    @foreach([
        ['cls'=>'blue',  'icon'=>'🎓','title'=>'Üniversite Başvurusu','desc'=>'Üniversite & bölüm seçimi, başvuru takibi',        'href'=>route('guest.university-guide'),   'star'=>false],
        ['cls'=>'purple','icon'=>'📋','title'=>'Belge Hazırlama',      'desc'=>'Motivasyon mektubu, CV, resmi çeviri',            'href'=>route('guest.document-guide'),     'star'=>false],
        ['cls'=>'star',  'icon'=>'⭐','title'=>'Başarı Hikayeleri',   'desc'=>'80+ öğrencinin gerçek Almanya yolculuğu',         'href'=>route('guest.success-stories'),    'star'=>true],
        ['cls'=>'orange','icon'=>'🏠','title'=>'Almanya\'da Yaşam',    'desc'=>'Konaklama, sigorta, banka, ulaşım rehberi',       'href'=>route('guest.living-guide'),       'star'=>false],
        ['cls'=>'green', 'icon'=>'🛂','title'=>'Vize & Sperrkonto',    'desc'=>'Vize başvurusu ve bloke hesap danışmanlığı',      'href'=>route('guest.vize-guide'),         'star'=>false],
    ] as $svc)
    <a href="{{ $svc['href'] }}" class="gd-svc-card {{ $svc['cls'] }}">
        @if($svc['star'])
        <span class="gd-svc-star-badge">⭐ Öne Çıkan</span>
        @endif
        <div class="gd-svc-accent"></div>
        <div class="gd-svc-body">
            <div class="gd-svc-icon">{{ $svc['icon'] }}</div>
            <div class="gd-svc-title">{{ $svc['title'] }}</div>
            <div class="gd-svc-desc">{{ $svc['desc'] }}</div>
            <div class="gd-svc-link">Keşfet</div>
        </div>
    </a>
    @endforeach
</div>

{{-- ── Keşfet Hızlı Linkler (Almanya'da Yaşam altı) ── --}}
<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px;padding:14px 18px;background:var(--u-card,#fff);border:1px solid var(--u-line,#e2e8f0);border-radius:12px;align-items:center;">
    <span style="font-size:.8rem;font-weight:700;color:var(--u-muted,#64748b);white-space:nowrap;">🧭 İçerik Keşfet:</span>
    <a href="{{ route('guest.discover') }}" style="padding:5px 12px;border-radius:16px;font-size:.8rem;font-weight:600;text-decoration:none;background:var(--u-bg,#f1f5f9);color:var(--u-text,#0f172a);border:1px solid var(--u-line,#e2e8f0);">🧭 Tüm İçerikler</a>
    <a href="{{ route('guest.discover', ['cat'=>'city-content']) }}" style="padding:5px 12px;border-radius:16px;font-size:.8rem;font-weight:600;text-decoration:none;background:#ecfeff;color:#155e75;border:1px solid #a5f3fc;">🏙 Şehir Rehberleri</a>
    <a href="{{ route('guest.discover', ['cat'=>'tips-tricks']) }}" style="padding:5px 12px;border-radius:16px;font-size:.8rem;font-weight:600;text-decoration:none;background:#fffbeb;color:#92400e;border:1px solid #fde68a;">💡 Pratik İpuçları</a>
    <a href="{{ route('guest.discover', ['cat'=>'careers']) }}" style="padding:5px 12px;border-radius:16px;font-size:.8rem;font-weight:600;text-decoration:none;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;">💼 Kariyer</a>
    <a href="{{ route('guest.discover', ['cat'=>'student-life']) }}" style="padding:5px 12px;border-radius:16px;font-size:.8rem;font-weight:600;text-decoration:none;background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;">🎓 Öğrenci Hayatı</a>
</div>

{{-- ── 2. ANA İÇERİK — Sol 2fr / Sağ 1fr ── --}}
<div class="gd-col31">

    {{-- SOL: Belgelerim + Mesajlar, Son Aktiviteler --}}
    <div style="display:flex;flex-direction:column;gap:16px;align-self:start;">

        {{-- Belgelerim + Son Mesajlar + Son Aktiviteler yan yana --}}
        <div class="gd-inner2" style="grid-template-columns:1fr 1fr {{ !empty($activityFeed) ? '1fr' : '' }};">

            {{-- Belgelerim --}}
            <div class="gd-card">
                <div class="gd-card-head">
                    <div class="gd-card-title">📁 Belgelerim</div>
                    <a href="{{ route('guest.registration.documents') }}" class="gd-card-action">Tümü →</a>
                </div>
                <div class="gd-card-body">
                    @php $recentDocs = $recentDocuments ?? collect(); @endphp
                    @if($recentDocs->isNotEmpty())
                        <div class="gd-doc-list">
                            @foreach($recentDocs->take(4) as $doc)
                                @php
                                    $docStatus = $doc->status ?? 'pending';
                                    $iconClass = match($docStatus) { 'approved' => 'ok', 'review', 'uploaded' => 'warn', default => 'danger' };
                                    $docIcon   = match($docStatus) { 'approved' => '✅', 'review', 'uploaded' => '⏳', default => '❌' };
                                @endphp
                                <div class="gd-doc-item">
                                    <div class="gd-doc-icon {{ $iconClass }}">{{ $docIcon }}</div>
                                    <div style="flex:1;min-width:0;">
                                        <div class="gd-doc-name">{{ $doc->name ?? $doc->document_name ?? 'Belge' }}</div>
                                        <div class="gd-doc-meta">{{ $doc->updated_at?->format('d M Y') ?? '' }}</div>
                                    </div>
                                    <span class="badge {{ $iconClass }}">{{ match($docStatus) { 'approved' => 'Onaylandı', 'review' => 'İncelemede', 'uploaded' => 'Yüklendi', default => 'Eksik' } }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="gd-doc-list">
                            <div class="gd-doc-item">
                                <div class="gd-doc-icon warn">📤</div>
                                <div style="flex:1;min-width:0;">
                                    <div class="gd-doc-name">Henüz belge yüklenmedi</div>
                                    <div class="gd-doc-meta">Belgelerinizi yükleyerek süreci başlatın</div>
                                </div>
                            </div>
                        </div>
                    @endif
                    @php
                        $displayPct = ($docsRequiredTotal > 0 && $docsRequiredUploaded > 0)
                            ? $docsPct
                            : ($recentDocs->isNotEmpty() ? $docsPct : 0);
                    @endphp
                    <div style="margin-top:12px;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                            <span style="font-size:var(--tx-xs);color:var(--u-muted);">Tamamlanma</span>
                            <span style="font-size:var(--tx-xs);font-weight:700;color:var(--u-text);">%{{ $displayPct }}</span>
                        </div>
                        <div class="gd-progress-bar">
                            <div class="gd-progress-fill {{ $displayPct >= 80 ? 'ok' : ($displayPct >= 40 ? 'warn' : '') }}" style="width:{{ $displayPct }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Son Mesajlar --}}
            <div class="gd-card">
                <div class="gd-card-head">
                    <div class="gd-card-title">💬 Mesajlar</div>
                    <a href="{{ route('guest.messages') }}" class="gd-card-action">Merkez →</a>
                </div>
                <div class="gd-card-body">
                    @if($assignedSenior)
                        <a href="{{ route('guest.messages') }}" class="gd-msg-thread" style="text-decoration:none;">
                            <div class="gd-msg-av" style="background:linear-gradient(135deg,#16a34a,#0891b2);">
                                {{ strtoupper(substr($assignedSenior->name ?? 'D', 0, 2)) }}
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div class="gd-msg-name">{{ $assignedSenior->name }} <span style="font-size:var(--tx-xs);font-weight:400;color:var(--u-muted);">(Danışman)</span></div>
                                <div class="gd-msg-preview">{{ $seniorCard['message'] ?? 'Herhangi bir sorunuz varsa yazabilirsiniz.' }}</div>
                            </div>
                            @if(($ticketSummary['unread_like'] ?? 0) > 0)
                                <div class="gd-msg-dot"></div>
                            @endif
                        </a>
                    @else
                        <div style="color:var(--u-muted);font-size:var(--tx-sm);padding:8px 0;">
                            Danışmanınız henüz atanmadı.<br>
                            <a href="{{ route('guest.messages') }}" style="color:var(--u-brand);font-weight:600;font-size:var(--tx-xs);">Mesaj bırak →</a>
                        </div>
                    @endif
                    <a href="{{ route('guest.messages') }}" class="gd-msg-thread" style="text-decoration:none;margin-top:4px;">
                        <div class="gd-msg-av" style="background:linear-gradient(135deg,#7c3aed,#2563eb);">MD</div>
                        <div style="flex:1;min-width:0;">
                            <div class="gd-msg-name">MentorDE Destek</div>
                            <div class="gd-msg-preview">Sorularınız için buradayız.</div>
                        </div>
                    </a>
                </div>
            </div>

            {{-- Son Aktiviteler --}}
            @if(!empty($activityFeed))
            <div class="gd-card">
                <div class="gd-card-head">
                    <div class="gd-card-title">🕐 Son Aktiviteler</div>
                </div>
                <div class="gd-card-body" style="padding:8px 14px;">
                    @foreach(array_slice($activityFeed, 0, 4) as $act)
                    <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--u-line);">
                        <div style="font-size:var(--tx-base);flex-shrink:0;width:20px;text-align:center;">{{ $act['icon'] }}</div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:var(--tx-xs);font-weight:600;color:var(--u-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $act['text'] }}</div>
                            <div style="font-size:var(--tx-xs);color:var(--u-muted);">{{ $act['time_label'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>{{-- /inner2 --}}

        {{-- ── Başarı Metrikleri ── --}}
        <div class="gd-social-proof" style="padding:18px 20px;">
            <div style="font-size:var(--tx-xs);font-weight:700;text-transform:uppercase;letter-spacing:.07em;opacity:.7;margin-bottom:14px;">🏆 MentorDE Başarısı</div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
                <div style="background:rgba(255,255,255,.14);border-radius:10px;padding:14px 8px;text-align:center;">
                    <div style="font-size:1.35rem;font-weight:800;line-height:1.1;">1.200+</div>
                    <div style="font-size:.65rem;opacity:.8;margin-top:5px;line-height:1.3;">Yerleşen Öğrenci</div>
                </div>
                <div style="background:rgba(255,255,255,.14);border-radius:10px;padding:14px 8px;text-align:center;">
                    <div style="font-size:1.35rem;font-weight:800;line-height:1.1;">4.9★</div>
                    <div style="font-size:.65rem;opacity:.8;margin-top:5px;line-height:1.3;">Memnuniyet</div>
                </div>
                <div style="background:rgba(255,255,255,.14);border-radius:10px;padding:14px 8px;text-align:center;">
                    <div style="font-size:1.35rem;font-weight:800;line-height:1.1;">50+</div>
                    <div style="font-size:.65rem;opacity:.8;margin-top:5px;line-height:1.3;">Partner Üniversite</div>
                </div>
                <div style="background:rgba(255,255,255,.14);border-radius:10px;padding:14px 8px;text-align:center;">
                    <div style="font-size:1.35rem;font-weight:800;line-height:1.1;">15 yıl</div>
                    <div style="font-size:.65rem;opacity:.8;margin-top:5px;line-height:1.3;">Deneyim</div>
                </div>
                <div style="background:rgba(255,255,255,.14);border-radius:10px;padding:14px 8px;text-align:center;">
                    <div style="font-size:1.35rem;font-weight:800;line-height:1.1;">%98</div>
                    <div style="font-size:.65rem;opacity:.8;margin-top:5px;line-height:1.3;">Vize Başarısı</div>
                </div>
                <div style="background:rgba(255,255,255,.14);border-radius:10px;padding:14px 8px;text-align:center;">
                    <div style="font-size:1.35rem;font-weight:800;line-height:1.1;">300+</div>
                    <div style="font-size:.65rem;opacity:.8;margin-top:5px;line-height:1.3;">Üniversite Seçeneği</div>
                </div>
                <div style="background:rgba(255,255,255,.14);border-radius:10px;padding:14px 8px;text-align:center;">
                    <div style="font-size:1.35rem;font-weight:800;line-height:1.1;">16+</div>
                    <div style="font-size:.65rem;opacity:.8;margin-top:5px;line-height:1.3;">Şehir Danışmanlığı</div>
                </div>
                <div style="background:rgba(255,255,255,.14);border-radius:10px;padding:14px 8px;text-align:center;">
                    <div style="font-size:1.35rem;font-weight:800;line-height:1.1;">24/7</div>
                    <div style="font-size:.65rem;opacity:.8;margin-top:5px;line-height:1.3;">Destek Hattı</div>
                </div>
            </div>
            <div style="margin-top:12px;text-align:right;">
                <a href="{{ route('guest.services') }}" class="gd-sp-cta">Hizmetlerimizi İncele →</a>
            </div>
        </div>

    </div>{{-- /sol --}}

    {{-- SAĞ: Danışman, CTA, Randevular --}}
    <div class="gd-col1-right">

        {{-- Danışman Profil Kartı --}}
        @if($seniorCard)
        @php
            $sc         = $seniorCard;
            $scInitials = strtoupper(mb_substr($sc['name'], 0, 2));
            $scTags     = $sc['expertise_tags'] ?? [];
            $scCount    = (int) ($sc['success_count'] ?? 0);
        @endphp
        <div class="gd-card">
            <div style="background:var(--hero-gradient);padding:16px 18px;border-radius:14px 14px 0 0;color:#fff;display:flex;align-items:center;gap:14px;">
                @if(!empty($sc['photo']))
                <img src="{{ $sc['photo'] }}" alt="{{ $sc['name'] }}" loading="lazy"
                     style="width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.4);flex-shrink:0;">
                @else
                <div style="width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:var(--tx-lg);font-weight:800;flex-shrink:0;border:2px solid rgba(255,255,255,.3);">
                    {{ $scInitials }}
                </div>
                @endif
                <div style="min-width:0;">
                    <div style="font-size:var(--tx-base);font-weight:800;line-height:1.2;">{{ $sc['name'] }}</div>
                    <div style="font-size:var(--tx-xs);opacity:.8;margin-top:2px;">{{ $sc['title'] }}</div>
                    @if($scCount > 0)
                    <div style="font-size:var(--tx-xs);opacity:.75;margin-top:3px;">🎓 {{ $scCount }} öğrenci yerleştirdi</div>
                    @endif
                </div>
            </div>
            <div class="gd-card-body">
                @if(!empty($scTags))
                <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px;">
                    @foreach($scTags as $tag)
                    <span class="badge info" style="font-size:var(--tx-xs);padding:2px 8px;">{{ $tag }}</span>
                    @endforeach
                </div>
                @endif
                @if(!empty($sc['bio']))
                <div style="font-size:var(--tx-xs);color:var(--u-muted);line-height:1.5;margin-bottom:10px;">{{ $sc['bio'] }}</div>
                @else
                <div style="font-size:var(--tx-xs);color:var(--u-muted);line-height:1.5;margin-bottom:10px;">{{ $sc['message'] }}</div>
                @endif
                <a href="{{ route('guest.messages') }}" class="btn ok" style="width:100%;text-align:center;display:block;font-size:var(--tx-xs);">
                    💬 Danışmanıma Yaz
                </a>
            </div>
        </div>
        @endif

        {{-- İlk Randevu CTA --}}
        @if($showAppointmentCta ?? false)
        <div style="padding:14px 16px;border:1.5px solid #bfdbfe;border-radius:12px;background:linear-gradient(135deg,#eff6ff,#dbeafe);display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span style="font-size:var(--tx-xl);">📅</span>
            <div style="flex:1;min-width:0;">
                <div style="font-size:var(--tx-sm);font-weight:700;color:#1e40af;">İlk görüşmenizi planlayın</div>
                <div style="font-size:var(--tx-xs);color:#3b82f6;margin-top:2px;">Danışmanınız atandı — hemen bir randevu oluşturun.</div>
            </div>
            <a href="{{ route('guest.messages') }}" class="btn alt" style="font-size:var(--tx-xs);padding:6px 14px;flex-shrink:0;white-space:nowrap;">
                Randevu Al →
            </a>
            <button type="button" onclick="localStorage.setItem('gd_appt_cta_closed','1');this.closest('div[style]').remove();"
                    style="background:none;border:none;color:#93c5fd;cursor:pointer;font-size:var(--tx-base);padding:0;flex-shrink:0;">✕</button>
        </div>
        @push('scripts')
        <script>if(localStorage.getItem('gd_appt_cta_closed')==='1'){var _a=document.querySelector('[style*="bfdbfe"]');if(_a)_a.remove();}</script>
        @endpush
        @endif

        {{-- Randevular --}}
        <div class="gd-card" style="flex:1;">
            <div class="gd-card-head">
                <div class="gd-card-title">📅 Randevular</div>
                <a href="{{ route('guest.messages') }}" class="gd-card-action">Tümü</a>
            </div>
            <div class="gd-card-body">
                @forelse($upcomingAppointments ?? [] as $appt)
                    <div class="gd-appt-item">
                        <div class="gd-appt-date">
                            <div class="gd-appt-day">{{ \Carbon\Carbon::parse($appt->scheduled_at)->format('d') }}</div>
                            <div class="gd-appt-month">{{ \Carbon\Carbon::parse($appt->scheduled_at)->isoFormat('MMM') }}</div>
                        </div>
                        <div style="flex:1;">
                            <div class="gd-appt-title">{{ $appt->title ?? 'Danışman Görüşmesi' }}</div>
                            <div class="gd-appt-time">{{ \Carbon\Carbon::parse($appt->scheduled_at)->format('H:i') }} · {{ $appt->meeting_platform ?? 'Online' }}</div>
                        </div>
                        <span class="badge {{ $loop->first ? 'info' : 'pending' }}">{{ $loop->first ? 'Yakında' : 'Planlandı' }}</span>
                    </div>
                @empty
                    <div style="text-align:center;padding:16px 0;color:var(--u-muted);font-size:var(--tx-sm);">
                        Planlanmış randevu yok.<br>
                        <a href="{{ route('guest.messages') }}" style="color:var(--u-brand);font-size:var(--tx-xs);font-weight:600;">Danışmanınızla iletişime geçin →</a>
                    </div>
                @endforelse
            </div>
        </div>

    </div>{{-- /sağ --}}

</div>{{-- /gd-col31 --}}


{{-- ── Alt Promosyon Şeridi ── --}}
<div class="gd-bottom-grid">

    {{-- Arkadaşını Davet Et — Promo --}}
    <div class="gd-ref-promo">
        <div class="gd-ref-emoji">🎁</div>
        <div class="gd-ref-body">
            <div class="gd-ref-eyebrow">Referans Programı</div>
            <div class="gd-ref-title">Arkadaşını Davet Et, İkisi de Kazansın!</div>
            <div class="gd-ref-sub">Her başarılı referans için özel indirim kazan.</div>
            @if(!empty($referralStats['referral_code']))
            <div class="gd-ref-input">
                <div class="gd-ref-code">{{ $referralStats['referral_code'] }}</div>
                <button onclick="copyRef()" class="gd-ref-copy">Kopyala</button>
            </div>
            <input id="refInput" type="hidden" value="{{ url('/apply?ref=' . ($referralStats['referral_code'] ?? '')) }}">
            @if(($referralStats['total_sent'] ?? 0) > 0)
            <div style="margin-top:8px;font-size:var(--tx-xs);opacity:.8;">
                {{ $referralStats['total_sent'] }} davet · <strong>{{ $referralStats['converted'] }} dönüşüm</strong>
            </div>
            @endif
            @else
            <button id="btnGenRef" onclick="generateRef()" class="gd-ref-copy" style="align-self:flex-start;">Link Oluştur →</button>
            <div id="refResult" style="display:none;margin-top:8px;font-family:monospace;font-size:var(--tx-xs);color:#fff;word-break:break-all;background:rgba(255,255,255,.15);padding:8px;border-radius:8px;"></div>
            @endif
        </div>
    </div>

    {{-- Rozetlerim --}}
    <div class="gd-card">
        <div class="gd-card-head" style="padding-top:20px;">
            <div class="gd-card-title">🏅 Rozetlerim</div>
            @if(($totalPoints ?? 0) > 0)
                <span class="badge info" style="font-size:var(--tx-xs);">{{ $totalPoints }} puan</span>
            @endif
        </div>
        <div class="gd-card-body" style="display:flex;align-items:center;justify-content:center;min-height:120px;">
            @if(($achievements ?? collect())->isNotEmpty())
                <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;">
                    @foreach($achievements as $ach)
                    <span title="{{ $ach['desc_tr'] ?? '' }}" style="font-size:var(--tx-xl);line-height:1;">{{ $ach['icon'] ?? '🏅' }}</span>
                    @endforeach
                </div>
            @else
                <div style="text-align:center;">
                    <div style="font-size:36px;margin-bottom:8px;">🥇</div>
                    <div style="font-size:var(--tx-sm);color:var(--u-muted);line-height:1.5;">Adımları tamamlayarak<br>rozet kazan!</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Aylık Maliyet --}}
    <div class="gd-card">
        <div class="gd-card-head">
            <div class="gd-card-title">💶 Aylık Maliyet</div>
            <a href="{{ route('guest.cost-calculator') }}" class="gd-card-action">Hesapla →</a>
        </div>
        <div class="gd-card-body">
            <div style="display:flex;align-items:baseline;gap:6px;margin-bottom:4px;">
                <span style="font-size:var(--tx-2xl);font-weight:800;color:var(--u-text);">€ {{ number_format($dc['monthly'] ?? 0, 0, ',', '.') }}</span>
                <span style="font-size:var(--tx-xs);color:var(--u-muted);">/ ay</span>
            </div>
            <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:10px;">{{ $dc['label'] ?? 'Şehir seçilmedi' }}</div>
            @if(!empty($dc['eur_rate']))
            <div style="background:rgba(37,99,235,.07);border-radius:8px;padding:8px 10px;margin-bottom:10px;">
                <div style="font-size:var(--tx-sm);font-weight:700;color:var(--u-brand);">≈ ₺ {{ number_format(($dc['monthly'] ?? 0) * $dc['eur_rate'], 0, ',', '.') }}</div>
                <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-top:1px;">1€ = {{ number_format($dc['eur_rate'], 2) }}₺ (bugün)</div>
            </div>
            @endif
            <div style="display:flex;flex-direction:column;gap:4px;">
                @foreach([['🏠','Kira','rent'],['🍽','Yemek','food'],['🚌','Ulaşım','transport']] as [$ico,$lbl,$key])
                <div style="display:flex;justify-content:space-between;font-size:var(--tx-xs);">
                    <span style="color:var(--u-muted);">{{ $ico }} {{ $lbl }}</span>
                    <span style="font-weight:600;color:var(--u-text);">€ {{ number_format($dc[$key] ?? 0, 0, ',', '.') }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>{{-- /gd-bottom-grid --}}

@push('scripts')
<script>
(function(){
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    document.querySelectorAll('[data-banner-id]').forEach(function(el){
        el.addEventListener('click', function(){
            var id = el.dataset.bannerId;
            if(!id) return;
            fetch('/guest/banner/' + id + '/click', {
                method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}
            }).catch(function(){});
        });
    });
})();
</script>
@endpush

@endsection

@push('welcome-modal')
    @include('partials.welcome-video-modal', ['wvPortal' => 'guest'])
@endpush

{{-- ── Onboarding Modal ── --}}
@if(!empty($onboardingSteps) && ($onboardingPending ?? false))
@php
    $obTotal   = count($onboardingSteps);
    $obDone    = collect($onboardingSteps)->where('done', true)->count();
    $obPct     = $obTotal > 0 ? (int) round($obDone / $obTotal * 100) : 0;
    $guestId   = $guest?->id ?? 'g';
@endphp
<div id="ob-modal-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9000;align-items:center;justify-content:center;padding:16px;">
    <div style="background:var(--u-card,#fff);border-radius:20px;max-width:520px;width:100%;max-height:88vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 24px 60px rgba(0,0,0,.25);">

        {{-- Header --}}
        <div style="background:var(--hero-gradient);padding:24px 24px 20px;color:#fff;position:relative;">
            <button onclick="obGuestModalDismiss()" aria-label="Kapat"
                    style="position:absolute;top:14px;right:14px;background:rgba(255,255,255,.2);border:none;color:#fff;border-radius:8px;width:30px;height:30px;font-size:var(--tx-base);cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;">✕</button>
            <div style="font-size:var(--tx-2xl);margin-bottom:6px;">🚀</div>
            <div style="font-size:var(--tx-lg);font-weight:800;margin-bottom:4px;">Portala Hoş Geldiniz!</div>
            <div style="font-size:var(--tx-sm);opacity:.85;">Almanya yolculuğunuza başlamak için birkaç adımı tamamlayın.</div>
            <div style="margin-top:14px;">
                <div style="display:flex;justify-content:space-between;font-size:var(--tx-xs);margin-bottom:5px;">
                    <span>{{ $obDone }} / {{ $obTotal }} adım tamamlandı</span>
                    <span>%{{ $obPct }}</span>
                </div>
                <div style="height:6px;background:rgba(255,255,255,.25);border-radius:999px;overflow:hidden;">
                    <div id="ob-guest-fill" style="height:100%;background:#fff;border-radius:999px;transition:width .4s;width:{{ $obPct }}%;"></div>
                </div>
            </div>
        </div>

        {{-- Steps --}}
        <div style="overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:10px;">
            @foreach($onboardingSteps as $step)
            <div id="ob-g-step-{{ $step['code'] }}"
                 style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border-radius:12px;border:1px solid {{ $step['done'] ? 'var(--u-ok,#22c55e)' : 'var(--u-line,#e5e7eb)' }};background:{{ $step['done'] ? 'rgba(34,197,94,.06)' : 'var(--u-subtle,#f8fafc)' }};opacity:{{ $step['done'] ? '.8' : '1' }};">
                <div style="width:34px;height:34px;border-radius:50%;background:{{ $step['done'] ? 'var(--u-ok,#22c55e)' : 'var(--u-line,#e5e7eb)' }};color:{{ $step['done'] ? '#fff' : 'var(--u-text,#374151)' }};display:flex;align-items:center;justify-content:center;font-size:{{ $step['done'] ? '14px' : '16px' }};flex-shrink:0;" id="ob-g-num-{{ $step['code'] }}">
                    {{ $step['done'] ? '✓' : $step['icon'] }}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:var(--tx-sm);font-weight:600;color:var(--u-text,#111827);{{ $step['done'] ? 'text-decoration:line-through;color:#9ca3af;' : '' }}">{{ $step['label'] }}</div>
                    @if(!$step['done'])
                    <div style="display:flex;gap:6px;margin-top:8px;">
                        <button class="btn ok" style="font-size:var(--tx-xs);padding:5px 14px;" onclick="obGuestComplete('{{ $step['code'] }}')">Tamamlandı</button>
                        <button class="btn" style="background:#f3f4f6;color:#6b7280;font-size:var(--tx-xs);padding:5px 14px;" onclick="obGuestSkip('{{ $step['code'] }}')">Atla</button>
                        <a href="{{ $step['url'] }}" class="btn alt" style="font-size:var(--tx-xs);padding:5px 14px;">Git →</a>
                    </div>
                    @else
                    <span class="badge ok" style="font-size:var(--tx-xs);margin-top:4px;display:inline-block;" id="ob-g-badge-{{ $step['code'] }}">✓ Tamamlandı</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Footer --}}
        <div style="padding:12px 20px 16px;border-top:1px solid var(--u-line,#e5e7eb);display:flex;gap:8px;align-items:center;justify-content:flex-end;">
            <button onclick="obGuestModalDismiss()" style="font-size:var(--tx-xs);color:var(--u-muted,#6b7280);background:none;border:none;cursor:pointer;padding:4px 8px;">Daha sonra hatırlat</button>
            <a href="{{ route('guest.onboarding') }}" class="btn alt" style="font-size:var(--tx-xs);">Tam sayfada aç →</a>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function(){
    var KEY = 'ob_g_dismissed_{{ $guestId }}';
    var overlay = document.getElementById('ob-modal-overlay');
    if (!sessionStorage.getItem(KEY)) {
        setTimeout(function(){ overlay.style.display = 'flex'; }, 600);
    }
})();

function obGuestModalDismiss() {
    sessionStorage.setItem('ob_g_dismissed_{{ $guestId }}', '1');
    document.getElementById('ob-modal-overlay').style.display = 'none';
}

var _obGuestDone = {{ $obDone }};
var _obGuestTotal = {{ $obTotal }};

function _obGuestUpdateProgress() {
    _obGuestDone++;
    var pct = Math.round(_obGuestDone / _obGuestTotal * 100);
    var fill = document.getElementById('ob-guest-fill');
    if (fill) fill.style.width = pct + '%';
    if (_obGuestDone >= _obGuestTotal) {
        setTimeout(function(){
            document.getElementById('ob-modal-overlay').style.display = 'none';
            window.location.reload();
        }, 800);
    }
}

async function obGuestComplete(code) {
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    await fetch('/guest/onboarding/' + code + '/complete', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
    });
    var el = document.getElementById('ob-g-step-' + code);
    if (el) {
        var numEl = document.getElementById('ob-g-num-' + code);
        if (numEl) { numEl.textContent = '✓'; numEl.style.background = 'var(--u-ok,#22c55e)'; numEl.style.color = '#fff'; }
        el.querySelector('.btn.ok')?.closest('div')?.remove();
        var badge = document.createElement('span');
        badge.className = 'badge ok';
        badge.style.cssText = 'font-size:11px;margin-top:4px;display:inline-block;';
        badge.textContent = '✓ Tamamlandı';
        el.querySelector('[style*="font-size:14px"]')?.after(badge);
        el.style.borderColor = 'var(--u-ok,#22c55e)';
        el.style.background = 'rgba(34,197,94,.06)';
    }
    _obGuestUpdateProgress();
}

async function obGuestSkip(code) {
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    await fetch('/guest/onboarding/' + code + '/skip', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
    });
    var el = document.getElementById('ob-g-step-' + code);
    if (el) {
        var numEl = document.getElementById('ob-g-num-' + code);
        if (numEl) { numEl.textContent = '↷'; }
        el.querySelector('.btn.ok')?.closest('div')?.remove();
        var badge = document.createElement('span');
        badge.className = 'badge';
        badge.style.cssText = 'font-size:11px;margin-top:4px;display:inline-block;';
        badge.textContent = 'Atlandı';
        el.querySelector('[style*="font-size:14px"]')?.after(badge);
    }
    _obGuestUpdateProgress();
}
</script>
@endpush
@endif

@push('scripts')
<script>
/* Eşit yükseklik — gd-inner2 içindeki kartları JS ile hizala */
function equalizeInnerCards() {
    document.querySelectorAll('.gd-inner2').forEach(function(grid) {
        var cards = grid.querySelectorAll(':scope > .gd-card');
        cards.forEach(function(c) { c.style.minHeight = ''; });
        var maxH = 0;
        cards.forEach(function(c) { if (c.offsetHeight > maxH) maxH = c.offsetHeight; });
        cards.forEach(function(c) { c.style.minHeight = maxH + 'px'; });
    });
}
document.addEventListener('DOMContentLoaded', equalizeInnerCards);
window.addEventListener('resize', equalizeInnerCards);
</script>
@endpush

@push('scripts')
<script>
function copyRef() {
    var v = document.getElementById('refInput')?.value;
    if (!v) return;
    navigator.clipboard.writeText(v).then(function(){ Alpine.store('toast').success('Link kopyalandı!'); });
}
function generateRef() {
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch('/guest/referral/create', { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} })
        .then(function(r){ return r.json(); }).then(function(d){
            if (d.ok) {
                document.getElementById('btnGenRef').style.display='none';
                var r = document.getElementById('refResult');
                r.style.display='block';
                r.textContent = d.url;
            }
        });
}
(function(){
    var _orig=window.__designToggle;
    window.__designToggle=function(){
        if(_orig)_orig.apply(this,arguments);
        setTimeout(function(){
            document.documentElement.classList.toggle('jm-minimalist',localStorage.getItem('mentorde_design')==='minimalist');
        },50);
    };
})();
</script>
@endpush
