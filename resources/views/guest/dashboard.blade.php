@extends('guest.layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@push('head')
<script>if(localStorage.getItem('mentorde_design')==='minimalist'){document.documentElement.classList.add('jm-minimalist');}</script>
<style>
/* ── Aday Öğrenci Paneli v3 — gdb-* scoped ── */

/* ── Topbar greeting ── */
.gdb-greet { margin-bottom: 24px; }
.gdb-greet h2 { font-size: 22px; font-weight: 700; letter-spacing: -.3px; }
.gdb-greet p { font-size: 14px; color: var(--u-muted); margin-top: 2px; }

/* ── Journey Progress Card ── */
.gdb-journey {
    background: var(--u-card); border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,.06);
    margin-bottom: 24px; overflow: hidden;
    border: 1px solid var(--u-line);
}
.gdb-journey-top {
    padding: 20px 24px 16px;
    display: flex; align-items: center; justify-content: space-between;
}
.gdb-journey-title { display: flex; align-items: center; gap: 8px; }
.gdb-journey-title h3 { font-size: 15px; font-weight: 600; color: var(--u-text); }
.gdb-journey-tag {
    font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 10px;
    background: rgba(37,99,235,.08); color: var(--u-brand);
}
.gdb-journey-tag.complete { background: rgba(22,163,74,.08); color: var(--u-ok); }
.gdb-journey-pct {
    position: relative; width: 56px; height: 56px; flex-shrink: 0;
}
.gdb-journey-pct svg { transform: rotate(-90deg); }
.gdb-journey-pct .pct-track { fill: none; stroke: var(--u-line, #e5e7eb); stroke-width: 4; }
.gdb-journey-pct .pct-fill { fill: none; stroke: var(--u-brand, #2563eb); stroke-width: 4; stroke-linecap: round; transition: stroke-dashoffset .8s cubic-bezier(.4,0,.2,1); }
.gdb-journey-pct .pct-label {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; color: var(--u-text); letter-spacing: -0.5px;
}

/* Progress bar */
.gdb-bar-wrap { padding: 0 24px; margin-bottom: 16px; }
.gdb-bar { height: 8px; background: var(--u-bg); border-radius: 4px; overflow: hidden; }
.gdb-bar-fill {
    height: 100%; border-radius: 4px;
    background: linear-gradient(90deg, var(--u-brand), #14b8a6);
    transition: width .8s cubic-bezier(.4,0,.2,1);
    position: relative;
}
.gdb-bar-fill::after {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.3), transparent);
    animation: gdb-shimmer 2s infinite;
}
@keyframes gdb-shimmer { 0%{transform:translateX(-100%)} 100%{transform:translateX(100%)} }

/* Steps row */
.gdb-steps {
    display: grid; grid-template-columns: repeat(3, 1fr);
    border-top: 1px solid var(--u-line);
}
.gdb-step {
    padding: 16px 18px; display: flex; align-items: center; gap: 12px;
    border-right: 1px solid var(--u-line);
    transition: background .15s; cursor: pointer;
    text-decoration: none; color: var(--u-text);
    position: relative;
}
.gdb-step:last-child { border-right: none; }
.gdb-step:hover { background: var(--u-bg); text-decoration: none; color: var(--u-text); }
.gdb-step-num {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; flex-shrink: 0;
    border: 2px solid var(--u-line); background: var(--u-bg); color: var(--u-muted);
    transition: all .3s;
}
.gdb-step.done .gdb-step-num { background: var(--u-ok); border-color: var(--u-ok); color: #fff; }
.gdb-step.active .gdb-step-num {
    background: rgba(37,99,235,.08); border-color: var(--u-brand); color: var(--u-brand);
    box-shadow: 0 0 0 4px rgba(37,99,235,.08);
    animation: gdb-pulse 2s infinite;
}
@keyframes gdb-pulse {
    0%,100% { box-shadow: 0 0 0 4px rgba(37,99,235,.08); }
    50% { box-shadow: 0 0 0 8px rgba(37,99,235,.04); }
}
.gdb-step.locked .gdb-step-num { opacity: .4; }
.gdb-step-name { font-size: 13px; font-weight: 600; }
.gdb-step.done .gdb-step-name { color: var(--u-ok); }
.gdb-step.active .gdb-step-name { color: var(--u-brand); }
.gdb-step.locked .gdb-step-name { color: var(--u-muted); }
.gdb-step-status { font-size: 11px; color: var(--u-muted); margin-top: 1px; }
.gdb-step.active::after {
    content: ''; position: absolute; top: -1px; left: 50%; transform: translateX(-50%);
    border-left: 6px solid transparent; border-right: 6px solid transparent;
    border-top: 6px solid var(--u-brand);
}

/* ── Hero Task Card ── */
.gdb-hero {
    background: linear-gradient(135deg, #064e3b 0%, #065f46 30%, #16a34a 100%);
    color: #fff; border-radius: 16px; margin-bottom: 24px;
    box-shadow: 0 10px 40px rgba(0,0,0,.1), 0 0 0 1px rgba(22,163,74,.1);
    overflow: hidden; cursor: pointer;
    transition: transform .2s, box-shadow .2s;
    text-decoration: none; display: block;
}
.gdb-hero:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 60px rgba(0,0,0,.12), 0 0 0 1px rgba(22,163,74,.2);
    color: #fff; text-decoration: none;
}
.gdb-hero-inner { display: flex; align-items: stretch; }
.gdb-hero-left { flex: 1; padding: 24px 24px 24px 28px; display: flex; flex-direction: column; justify-content: center; }
.gdb-hero-badge {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11px; text-transform: uppercase; letter-spacing: 1px;
    color: rgba(255,255,255,.8); margin-bottom: 8px;
}
.gdb-hero-badge .pulse {
    width: 8px; height: 8px; border-radius: 50%;
    background: #34d399; box-shadow: 0 0 6px rgba(52,211,153,.6);
    animation: gdb-dot 1.5s infinite;
}
@keyframes gdb-dot { 0%,100%{opacity:1} 50%{opacity:.4} }
.gdb-hero-title { font-size: 20px; font-weight: 700; line-height: 1.3; margin-bottom: 6px; }
.gdb-hero-sub { font-size: 13px; color: rgba(255,255,255,.85); line-height: 1.5; margin-bottom: 14px; }
.gdb-hero-meta { display: flex; gap: 14px; flex-wrap: wrap; }
.gdb-hero-meta-item { display: flex; align-items: center; gap: 5px; font-size: 12px; color: rgba(255,255,255,.75); }
.gdb-hero-right {
    width: 140px; background: rgba(255,255,255,.06);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 18px; gap: 8px; border-left: 1px solid rgba(255,255,255,.06);
}
.gdb-hero-action-icon {
    width: 52px; height: 52px; background: rgba(255,255,255,.1);
    border-radius: 14px; display: flex; align-items: center; justify-content: center;
    font-size: 24px; transition: transform .2s;
}
.gdb-hero:hover .gdb-hero-action-icon { transform: scale(1.1); }
.gdb-hero-action-text { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .8px; color: rgba(255,255,255,.7); }

/* ── Grids ── */
.gdb-grid-3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 24px; }
.gdb-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 24px; }
@media(max-width:860px){ .gdb-grid-3, .gdb-grid-2 { grid-template-columns: 1fr; } }

/* ── Stat Card ── */
.gdb-stat {
    background: var(--u-card); border-radius: 12px; padding: 18px;
    box-shadow: 0 1px 3px rgba(0,0,0,.04); border: 1px solid var(--u-line);
    display: flex; align-items: flex-start; gap: 12px;
    transition: box-shadow .15s;
}
.gdb-stat:hover { box-shadow: 0 4px 12px rgba(0,0,0,.06); }
.gdb-stat-icon {
    width: 42px; height: 42px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.gdb-stat-icon.teal { background: rgba(13,148,136,.08); }
.gdb-stat-icon.blue { background: rgba(37,99,235,.08); }
.gdb-stat-icon.green { background: rgba(22,163,74,.08); }
.gdb-stat-icon.amber { background: rgba(217,119,6,.08); }
.gdb-stat-icon.purple { background: rgba(124,58,237,.08); }
.gdb-stat-label { font-size: 12px; color: var(--u-muted); margin-bottom: 3px; }
.gdb-stat-value { font-size: 20px; font-weight: 700; line-height: 1.2; letter-spacing: -.3px; }
.gdb-stat-sub { font-size: 11px; color: var(--u-muted); margin-top: 2px; }

/* ── Checklist Card ── */
.gdb-cl {
    background: var(--u-card); border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,.04); border: 1px solid var(--u-line);
    overflow: hidden;
}
.gdb-cl-head {
    padding: 14px 18px; border-bottom: 1px solid var(--u-line);
    display: flex; align-items: center; justify-content: space-between;
}
.gdb-cl-head h4 { font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.gdb-cl-head .count { font-size: 12px; color: var(--u-muted); font-weight: 400; }
.gdb-cl-item {
    padding: 11px 18px; display: flex; align-items: center; gap: 10px;
    border-bottom: 1px solid var(--u-line); font-size: 13px;
    transition: background .1s;
}
.gdb-cl-item:last-child { border-bottom: none; }
.gdb-cl-item:hover { background: var(--u-bg); }
.gdb-cl-check {
    width: 22px; height: 22px; border-radius: 50%; border: 2px solid var(--u-line);
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; flex-shrink: 0;
}
.gdb-cl-item.done .gdb-cl-check { background: var(--u-ok); border-color: var(--u-ok); color: #fff; }
.gdb-cl-item.active .gdb-cl-check { border-color: var(--u-brand); background: rgba(37,99,235,.06); color: var(--u-brand); }
.gdb-cl-item.locked .gdb-cl-check { opacity: .3; }
.gdb-cl-item.done .gdb-cl-text { color: var(--u-muted); text-decoration: line-through; }
.gdb-cl-item.locked .gdb-cl-text { color: var(--u-muted); }
.gdb-cl-text { flex: 1; }
.gdb-cl-tag { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 6px; }
.gdb-cl-tag.done { background: rgba(22,163,74,.08); color: var(--u-ok); }
.gdb-cl-tag.active { background: rgba(37,99,235,.06); color: var(--u-brand); }
.gdb-cl-tag.locked { background: var(--u-bg); color: var(--u-muted); }

/* ── Social Proof Bar ── */
.gdb-social {
    background: var(--u-card); border-radius: 12px; padding: 14px 18px;
    box-shadow: 0 1px 3px rgba(0,0,0,.04); border: 1px solid var(--u-line);
    display: flex; align-items: center; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;
}
.gdb-social-avatars { display: flex; }
.gdb-social-avatars span {
    width: 28px; height: 28px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 9px; font-weight: 700; color: #fff;
    border: 2px solid var(--u-card); margin-left: -6px;
}
.gdb-social-avatars span:first-child { margin-left: 0; }
.gdb-social-avatars span:nth-child(1) { background: #2563eb; }
.gdb-social-avatars span:nth-child(2) { background: #8b5cf6; }
.gdb-social-avatars span:nth-child(3) { background: #0d9488; }
.gdb-social-avatars span:nth-child(4) { background: #f59e0b; }
.gdb-social-avatars span:nth-child(5) { background: #10b981; }
.gdb-social-text { font-size: 13px; color: var(--u-muted); flex: 1; line-height: 1.4; }
.gdb-social-text strong { color: var(--u-text); }
.gdb-social-live {
    display: flex; align-items: center; gap: 5px;
    font-size: 11px; color: var(--u-ok); font-weight: 600; flex-shrink: 0;
}
.gdb-social-live-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--u-ok); animation: gdb-dot 1.5s infinite;
}

/* ── Tip Card ── */
.gdb-tip {
    background: rgba(37,99,235,.04); border: 1px solid rgba(37,99,235,.1);
    border-radius: 12px; padding: 16px 20px;
    display: flex; align-items: flex-start; gap: 12px; margin-bottom: 24px;
}
.gdb-tip-icon {
    width: 34px; height: 34px; background: rgba(37,99,235,.06); border-radius: 8px;
    display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;
}
.gdb-tip h5 { font-size: 13px; font-weight: 600; color: var(--u-text); margin-bottom: 2px; }
.gdb-tip p { font-size: 12px; color: var(--u-muted); line-height: 1.5; }

/* ── Advisor Sticky Card (fixed right) ── */
.gdb-advisor {
    position: fixed; bottom: 24px; right: 24px; z-index: 90;
    background: var(--u-card); border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0,0,0,.12); border: 1px solid var(--u-line);
    padding: 16px; width: 280px;
    display: flex; flex-direction: column; gap: 12px;
}
.gdb-advisor-top { display: flex; align-items: center; gap: 10px; }
.gdb-advisor-av {
    width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, #2563eb, #60a5fa);
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; color: #fff;
}
.gdb-advisor-name { font-size: 13px; font-weight: 600; color: var(--u-text); }
.gdb-advisor-role { font-size: 11px; color: var(--u-muted); }
.gdb-advisor-status {
    display: flex; align-items: center; gap: 4px;
    font-size: 10px; color: var(--u-ok); font-weight: 600;
}
.gdb-advisor-status-dot {
    width: 6px; height: 6px; border-radius: 50%; background: var(--u-ok);
}
.gdb-advisor-msg { font-size: 12px; color: var(--u-muted); line-height: 1.5; }
.gdb-advisor-cta {
    display: block; text-align: center;
    padding: 9px 16px; border-radius: 8px; font-size: 12px; font-weight: 600;
    background: var(--u-brand); color: #fff; text-decoration: none;
    transition: opacity .15s;
}
.gdb-advisor-cta:hover { opacity: .9; color: #fff; text-decoration: none; }
.gdb-advisor-close {
    position: absolute; top: 8px; right: 10px;
    background: none; border: none; font-size: 14px; color: var(--u-muted);
    cursor: pointer; padding: 2px; line-height: 1;
}
@media(max-width:600px){
    .gdb-advisor { width: 240px; right: 12px; bottom: 16px; padding: 12px; gap: 8px; }
    .gdb-advisor-av { width: 32px; height: 32px; font-size: 11px; }
    .gdb-advisor-name { font-size: 12px; }
    .gdb-advisor-role { font-size: 10px; }
    .gdb-advisor-msg { font-size: 11px; line-height: 1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .gdb-advisor-cta { padding: 7px 12px; font-size: 11px; }
}

/* ── Timeline ── */
.gdb-tl {
    background: var(--u-card); border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,.04); border: 1px solid var(--u-line);
    overflow: hidden; margin-bottom: 24px;
}
.gdb-tl-head { padding: 14px 18px; border-bottom: 1px solid var(--u-line); }
.gdb-tl-head h4 { font-size: 14px; font-weight: 600; }
.gdb-tl-item {
    padding: 10px 18px 10px 44px; position: relative;
    transition: background .1s;
}
.gdb-tl-item:hover { background: var(--u-bg); }
.gdb-tl-item::before {
    content: ''; position: absolute; left: 26px; top: 0; bottom: 0;
    width: 2px; background: var(--u-line);
}
.gdb-tl-item:first-child::before { top: 50%; }
.gdb-tl-item:last-child::before { bottom: 50%; }
.gdb-tl-dot {
    position: absolute; left: 20px; top: 50%; transform: translateY(-50%);
    width: 14px; height: 14px; border-radius: 50%;
    border: 2px solid var(--u-card); z-index: 1;
    background: var(--u-ok); box-shadow: 0 0 0 3px rgba(22,163,74,.08);
}
.gdb-tl-text { font-size: 13px; font-weight: 500; color: var(--u-text); }
.gdb-tl-time { font-size: 11px; color: var(--u-muted); margin-top: 1px; }

/* ── Quick Links ── */
.gdb-ql { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; margin-bottom: 24px; }
.gdb-ql-item {
    background: var(--u-card); border: 1px solid var(--u-line); border-radius: 8px;
    padding: 12px 14px; display: flex; align-items: center; gap: 8px;
    font-size: 13px; font-weight: 500; color: var(--u-muted);
    text-decoration: none; transition: all .15s;
}
.gdb-ql-item:hover { background: rgba(37,99,235,.04); border-color: var(--u-brand); color: var(--u-brand); text-decoration: none; }
@media(max-width:640px){ .gdb-ql { grid-template-columns: 1fr; } }

/* ── Celebration ── */
.gdb-celebrate {
    background: linear-gradient(135deg, #065f46, var(--u-ok));
    color: #fff; border-radius: 16px; padding: 36px 28px;
    text-align: center; margin-bottom: 24px; position: relative; overflow: hidden;
}
.gdb-celebrate-emoji { font-size: 48px; margin-bottom: 14px; }
.gdb-celebrate h3 { font-size: 24px; font-weight: 800; margin-bottom: 6px; }
.gdb-celebrate p { font-size: 14px; opacity: .85; max-width: 460px; margin: 0 auto; line-height: 1.5; }

/* ── Responsive ── */
@media(max-width:860px){
    .gdb-steps { grid-template-columns: 1fr; }
    .gdb-hero-inner { flex-direction: column; }
    .gdb-hero-right { width: 100%; flex-direction: row; padding: 12px 20px; border-left: none; border-top: 1px solid rgba(255,255,255,.06); }
}
@media(max-width:600px){
    .gdb-greet h2 { font-size: 18px; }
    .gdb-hero-title { font-size: 17px; }
    .gdb-hero-left { padding: 18px; }
    .gdb-journey-top { padding: 16px 18px 12px; }
    .gdb-bar-wrap { padding: 0 18px; }
    .gdb-step { padding: 12px 14px; }
}

/* ── Minimalist overrides ── */
.jm-minimalist .gdb-journey, .jm-minimalist .gdb-stat, .jm-minimalist .gdb-cl,
.jm-minimalist .gdb-tl, .jm-minimalist .gdb-social { box-shadow: none; }
</style>
@endpush

@section('content')
@php
    // ── Null-safe değişkenler ──
    $formCompleted        = (bool) ($formCompleted ?? false);
    $docsCompleted        = (bool) ($docsCompleted ?? false);
    $contractStatus       = (string) ($contractStatus ?? 'not_requested');
    $formRequiredTotal    = (int) ($formRequiredTotal ?? 0);
    $formRequiredFilled   = (int) ($formRequiredFilled ?? 0);
    $docsRequiredTotal    = (int) ($docsChecklistStats['required_total']   ?? 0);
    $docsRequiredUploaded = (int) ($docsChecklistStats['required_uploaded'] ?? 0);
    $docsPct              = (int) ($docsChecklistStats['percent'] ?? 0);
    $motivationMessage    = $motivationMessage ?? ['emoji' => '🚀', 'text' => 'Almanya yolculuğun başlıyor!'];

    $guestFirstName  = trim((string) ($guest?->first_name ?? ''));
    $selectedPkgCode = (string) ($guest?->selected_package_code ?? '');
    $packageSelected = $selectedPkgCode !== '';

    // ── Orantılı progress: Form %40 + Belge %40 + Paket %20 ──
    $formWeight = $formCompleted ? 40 : (($formRequiredTotal > 0) ? (int) round(40 * $formRequiredFilled / $formRequiredTotal) : 0);
    $docsWeight = $docsCompleted ? 40 : (($docsRequiredTotal > 0) ? (int) round(40 * $docsRequiredUploaded / $docsRequiredTotal) : 0);
    $pkgWeight  = $packageSelected ? 20 : 0;
    $overallPct = min(100, $formWeight + $docsWeight + $pkgWeight);

    $doneCount = (int) $formCompleted + (int) $docsCompleted + (int) $packageSelected;

    // ── State key (hero kart için) ──
    $stateKey = match(true) {
        ($overallPct >= 100 || ($formCompleted && $docsCompleted && $packageSelected)) => 'done',
        ($formCompleted && $docsCompleted) => 'pkg',
        $formCompleted                     => 'docs',
        default                            => 'fresh',
    };

    // ── Hero config ──
    $heroConfig = match($stateKey) {
        'fresh' => [
            'icon'   => '📋',
            'title'  => 'Başvuru formunu doldur',
            'sub'    => 'Kişisel bilgilerini, eğitim geçmişini ve tercihlerini girmen gerekiyor.',
            'url'    => route('guest.registration.form'),
            'action' => 'Başla →',
            'meta'   => ['⏱️ ~10 dakika', '💾 Otomatik kayıt'],
        ],
        'docs'  => [
            'icon'   => '📄',
            'title'  => 'Belgelerini yükle',
            'sub'    => 'Başvurun için gerekli belgeleri yükle. PDF veya fotoğraf formatında olabilir.',
            'url'    => route('guest.registration.documents'),
            'action' => 'Yükle →',
            'meta'   => ["📋 {$docsRequiredTotal} belge gerekli", '📸 Fotoğraf da olur'],
        ],
        'pkg'   => [
            'icon'   => '📦',
            'title'  => 'Sana uygun paketi seç',
            'sub'    => '3 farklı hizmet paketi arasından seçim yapabilirsin.',
            'url'    => route('guest.services'),
            'action' => 'Seç →',
            'meta'   => ['📦 3 paket', '🔄 Değiştirilebilir'],
        ],
        default => ['icon' => '🎉', 'title' => '', 'sub' => '', 'url' => '#', 'action' => '', 'meta' => []],
    };

    $greetTitle = match($stateKey) {
        'fresh' => "Merhaba {$guestFirstName} 👋",
        'docs'  => "Harika gidiyorsun {$guestFirstName}! 🎉",
        'pkg'   => "Son bir adım kaldı! 🎯",
        'done'  => "Tebrikler {$guestFirstName}! 🎓",
    };
    $greetSub = match($stateKey) {
        'fresh' => 'Almanya yolculuğun başlamak üzere. Adım adım seni yönlendireceğiz.',
        'docs'  => 'Başvuru formunu tamamladın — şimdi belgelerini yükleme zamanı.',
        'pkg'   => 'Form ve belgeler hazır — şimdi paketini seçelim.',
        'done'  => 'Tüm adımları tamamladın. Artık resmi ' . config('brand.name', 'MentorDE') . ' öğrencisisin!',
    };

    // ── Step states (Her adım paralel erişilebilir) ──
    $step1 = $formCompleted ? 'done' : 'active';
    $step2 = $docsCompleted ? 'done' : 'active';
    $step3 = $packageSelected ? 'done' : 'active';

    $step1Status = $formCompleted ? 'Tamamlandı ✓' : 'Şimdi başla';
    $step2Status = $docsCompleted ? 'Onaylandı ✓' : 'Şimdi yükle';
    $step3Status = $packageSelected ? 'Seçildi ✓' : 'Paket seç';
@endphp

{{-- ── Greeting ── --}}
<div class="gdb-greet">
    <h2>{{ $greetTitle }}</h2>
    <p>{{ $greetSub }}</p>
</div>

{{-- ── Journey Progress Card ── --}}
<div class="gdb-journey">
    <div class="gdb-journey-top">
        <div class="gdb-journey-title">
            <h3>Kayıt Süreci</h3>
            <span class="gdb-journey-tag {{ $stateKey === 'done' ? 'complete' : '' }}">
                {{ $stateKey === 'done' ? 'Tamamlandı' : 'Devam ediyor' }}
            </span>
        </div>
        @php $circumference = 2 * 3.14159 * 24; $offset = $circumference * (1 - $overallPct / 100); @endphp
        <div class="gdb-journey-pct">
            <svg width="56" height="56" viewBox="0 0 56 56">
                <circle class="pct-track" cx="28" cy="28" r="24"/>
                <circle class="pct-fill" cx="28" cy="28" r="24" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}"/>
            </svg>
            <div class="pct-label">{{ $overallPct }}%</div>
        </div>
    </div>
    <div class="gdb-bar-wrap">
        <div class="gdb-bar"><div class="gdb-bar-fill" style="width:{{ $overallPct }}%"></div></div>
    </div>
    <div class="gdb-steps">
        <a href="{{ route('guest.registration.form') }}" class="gdb-step {{ $step1 }}">
            <div class="gdb-step-num">{{ $formCompleted ? '✓' : '1' }}</div>
            <div><div class="gdb-step-name">Başvuru Formu</div><div class="gdb-step-status">{{ $step1Status }}</div></div>
        </a>
        <a href="{{ route('guest.registration.documents') }}" class="gdb-step {{ $step2 }}">
            <div class="gdb-step-num">{{ $docsCompleted ? '✓' : '2' }}</div>
            <div><div class="gdb-step-name">Belgeler</div><div class="gdb-step-status">{{ $step2Status }}</div></div>
        </a>
        <a href="{{ route('guest.services') }}" class="gdb-step {{ $step3 }}">
            <div class="gdb-step-num">{{ $packageSelected ? '✓' : '3' }}</div>
            <div><div class="gdb-step-name">Paket Seçimi</div><div class="gdb-step-status">{{ $step3Status }}</div></div>
        </a>
    </div>
</div>

{{-- ────── STATE-BASED CONTENT ────── --}}

@if($stateKey !== 'done')
{{-- ── Hero Task Card ── --}}
<a href="{{ $heroConfig['url'] }}" class="gdb-hero">
    <div class="gdb-hero-inner">
        <div class="gdb-hero-left">
            <div class="gdb-hero-badge"><span class="pulse"></span> {{ $stateKey === 'pkg' ? 'Son adım!' : 'Sıradaki adım' }}</div>
            <div class="gdb-hero-title">{{ $heroConfig['title'] }}</div>
            <div class="gdb-hero-sub">{{ $heroConfig['sub'] }}</div>
            <div class="gdb-hero-meta">
                @foreach($heroConfig['meta'] as $m)
                    <div class="gdb-hero-meta-item">{{ $m }}</div>
                @endforeach
            </div>
        </div>
        <div class="gdb-hero-right">
            <div class="gdb-hero-action-icon">{{ $heroConfig['icon'] }}</div>
            <div class="gdb-hero-action-text">{{ $heroConfig['action'] }}</div>
        </div>
    </div>
</a>
@else
{{-- ── Celebration ── --}}
<div class="gdb-celebrate">
    <div class="gdb-celebrate-emoji">🎉</div>
    <h3>Tebrikler {{ $guestFirstName }}!</h3>
    <p>Tüm adımları başarıyla tamamladın. Sözleşme süreci başlatıldı — danışmanın en kısa sürede seninle iletişime geçecek.</p>
</div>
@endif

{{-- ── Stats Row ── --}}
<div class="gdb-grid-3">
    <div class="gdb-stat">
        <div class="gdb-stat-icon {{ $stateKey === 'done' ? 'green' : 'blue' }}">📊</div>
        <div>
            <div class="gdb-stat-label">Tamamlanan adım</div>
            <div class="gdb-stat-value">{{ $doneCount }}<span style="font-size:14px;color:var(--u-muted);font-weight:400"> / 3</span></div>
            <div class="gdb-stat-sub">
                @if($doneCount === 0) Henüz başlamadın
                @elseif($doneCount === 3) Hepsi tamam!
                @else {{ 3 - $doneCount }} adım kaldı
                @endif
            </div>
        </div>
    </div>
    <div class="gdb-stat">
        <div class="gdb-stat-icon amber">⏱️</div>
        <div>
            <div class="gdb-stat-label">{{ $stateKey === 'done' ? 'Kayıt durumu' : 'Tahmini kalan süre' }}</div>
            <div class="gdb-stat-value">
                @if($stateKey === 'done') <span style="color:var(--u-ok);font-size:15px">✓ Tamamlandı</span>
                @elseif($stateKey === 'pkg') ~2 dk
                @elseif($stateKey === 'docs') ~7 dk
                @else ~17 dk
                @endif
            </div>
            <div class="gdb-stat-sub">{{ $stateKey === 'done' ? 'Tüm adımlar' : 'Kalan süreç için' }}</div>
        </div>
    </div>
    <div class="gdb-stat">
        <div class="gdb-stat-icon purple">💬</div>
        <div>
            <div class="gdb-stat-label">Yeni Mesaj</div>
            <div class="gdb-stat-value">{{ (int)($ticketSummary['unread_like'] ?? 0) }}</div>
            <div class="gdb-stat-sub">
                @if(($ticketSummary['unread_like'] ?? 0) > 0) Danışmandan mesaj var
                @else Okunmamış yok
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Checklists ── --}}
<div class="gdb-grid-2">
    <div class="gdb-cl">
        <div class="gdb-cl-head">
            <h4>📋 Yapılacaklar <span class="count">{{ $doneCount }}/3</span></h4>
        </div>
        <div class="gdb-cl-item {{ $formCompleted ? 'done' : 'active' }}">
            <div class="gdb-cl-check">{{ $formCompleted ? '✓' : '1' }}</div>
            <div class="gdb-cl-text">Başvuru formunu doldur</div>
            <span class="gdb-cl-tag {{ $formCompleted ? 'done' : 'active' }}">
                {{ $formCompleted ? 'Tamam' : 'Şimdi' }}
            </span>
        </div>
        <div class="gdb-cl-item {{ $docsCompleted ? 'done' : 'active' }}">
            <div class="gdb-cl-check">{{ $docsCompleted ? '✓' : '2' }}</div>
            <div class="gdb-cl-text">Belgelerini yükle</div>
            <span class="gdb-cl-tag {{ $docsCompleted ? 'done' : 'active' }}">
                {{ $docsCompleted ? 'Tamam' : 'Şimdi' }}
            </span>
        </div>
        <div class="gdb-cl-item {{ $packageSelected ? 'done' : (($formCompleted && $docsCompleted) ? 'active' : 'locked') }}">
            <div class="gdb-cl-check">{{ $packageSelected ? '✓' : '3' }}</div>
            <div class="gdb-cl-text">Paketini seç</div>
            <span class="gdb-cl-tag {{ $packageSelected ? 'done' : (($formCompleted && $docsCompleted) ? 'active' : 'locked') }}">
                {{ $packageSelected ? 'Tamam' : (($formCompleted && $docsCompleted) ? 'Şimdi' : 'Bekliyor') }}
            </span>
        </div>
    </div>

    <div class="gdb-cl">
        <div class="gdb-cl-head">
            <h4>📁 Gerekli Belgeler <span class="count">{{ $docsRequiredUploaded }}/{{ $docsRequiredTotal }}</span></h4>
        </div>
        @php $missingDocs = $missingRequiredDocuments ?? []; @endphp
        @if(!empty($missingDocs))
            @foreach(array_slice($missingDocs, 0, 4) as $doc)
                <div class="gdb-cl-item active">
                    <div class="gdb-cl-check">📄</div>
                    <div class="gdb-cl-text">{{ $doc['name'] ?? 'Belge' }}</div>
                    <span class="gdb-cl-tag active">Yükle</span>
                </div>
            @endforeach
        @else
            <div class="gdb-cl-item done">
                <div class="gdb-cl-check">✓</div>
                <div class="gdb-cl-text">Tüm belgeler yüklendi</div>
                <span class="gdb-cl-tag done">Tamam</span>
            </div>
        @endif
    </div>
</div>

{{-- ── Tip Card ── --}}
@php
    $tipStyle = match($stateKey) {
        'done' => 'background:linear-gradient(135deg,#d1fae5,#ecfdf5);border-color:rgba(16,185,129,.15);',
        default => '',
    };
    $tipIcon = match($stateKey) { 'fresh' => '💡', 'docs' => '📸', 'pkg' => '🎯', 'done' => '🎓' };
@endphp
<div class="gdb-tip" @if($tipStyle) style="{{ $tipStyle }}" @endif>
    <div class="gdb-tip-icon">{{ $tipIcon }}</div>
    <div>
        <h5>{{ match($stateKey) {
            'fresh' => 'Form otomatik kaydedilir',
            'docs'  => 'Telefonunla da yükleyebilirsin',
            'pkg'   => 'Son bir adım kaldı!',
            'done'  => 'Artık ' . config('brand.name', 'MentorDE') . ' öğrencisisin!',
        } }}</h5>
        <p>{{ $motivationMessage['text'] ?? '' }}</p>
    </div>
</div>

{{-- ── Advisor Sticky Card (fixed right) ── --}}
@if(!empty($seniorCard))
<div class="gdb-advisor" id="gdbAdvisor">
    <button class="gdb-advisor-close" id="gdbAdvisorClose" type="button">✕</button>
    <div class="gdb-advisor-top">
        <div class="gdb-advisor-av">{{ strtoupper(mb_substr($seniorCard['name'] ?? 'D', 0, 2)) }}</div>
        <div>
            <div class="gdb-advisor-name">{{ $seniorCard['name'] ?? '' }}</div>
            <div class="gdb-advisor-role">{{ $seniorCard['title'] ?? 'Eğitim Danışmanı' }}</div>
            <div class="gdb-advisor-status"><span class="gdb-advisor-status-dot"></span> Çevrimiçi</div>
        </div>
    </div>
    <div class="gdb-advisor-msg">{{ $seniorCard['message'] ?? '' }}</div>
    <a href="{{ route('guest.messages') }}" class="gdb-advisor-cta">Danışmana Yaz →</a>
</div>
@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var card = document.getElementById('gdbAdvisor');
    var btn = document.getElementById('gdbAdvisorClose');
    if(!card || !btn) return;
    btn.addEventListener('click', function(){ card.style.display = 'none'; });
})();
</script>
@endpush
@endif

@if($stateKey === 'done')
{{-- ── Quick Links ── --}}
<div class="gdb-ql">
    <a href="{{ route('guest.messages') }}" class="gdb-ql-item">💬 Danışmanınla konuş</a>
    <a href="{{ route('guest.contract') }}" class="gdb-ql-item">📜 Sözleşme durumu</a>
    <a href="{{ route('guest.discover') }}" class="gdb-ql-item">🌍 Almanya rehberi</a>
</div>
@endif

{{-- ── Social Proof ── --}}
<div class="gdb-social">
    <div class="gdb-social-avatars">
        <span>AK</span><span>EY</span><span>MG</span><span>ŞD</span><span>+</span>
    </div>
    <div class="gdb-social-text">
        <strong>{{ number_format((int)($socialProof['total_students'] ?? 0)) }}+ öğrenci</strong> {{ config('brand.name', 'MentorDE') }} üzerinden Almanya'ya başvurdu.
        <strong>{{ (int)($socialProof['total_unis'] ?? 50) }}+</strong> üniversite kabulü.
    </div>
    <div class="gdb-social-live"><span class="gdb-social-live-dot"></span> Canlı</div>
</div>

{{-- ── Başvuru Durumun ── --}}
@if(!empty($guestAnalytics))
<div style="background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:18px 20px;margin-bottom:16px;">
    <div style="font-size:13px;font-weight:700;color:var(--text,#111);margin-bottom:12px;">📊 Başvuru Durumun</div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
        <div style="text-align:center;padding:10px;background:var(--bg,#f8fafc);border-radius:8px;">
            <div style="font-size:20px;font-weight:800;color:#3b82f6;">{{ $guestAnalytics['daysSinceRegistration'] ?? 0 }}</div>
            <div style="font-size:10px;color:var(--muted,#64748b);">Gün (kayıttan beri)</div>
        </div>
        <div style="text-align:center;padding:10px;background:var(--bg,#f8fafc);border-radius:8px;">
            <div style="font-size:20px;font-weight:800;color:#16a34a;">{{ $guestAnalytics['docStats']['approved'] ?? 0 }}</div>
            <div style="font-size:10px;color:var(--muted,#64748b);">Onaylı Belge</div>
        </div>
        <div style="text-align:center;padding:10px;background:var(--bg,#f8fafc);border-radius:8px;">
            <div style="font-size:20px;font-weight:800;color:#f59e0b;">{{ $guestAnalytics['docStats']['uploaded'] ?? 0 }}</div>
            <div style="font-size:10px;color:var(--muted,#64748b);">İnceleme Bekleyen</div>
        </div>
        <div style="text-align:center;padding:10px;background:var(--bg,#f8fafc);border-radius:8px;">
            <div style="font-size:20px;font-weight:800;color:#ef4444;">{{ $guestAnalytics['docStats']['rejected'] ?? 0 }}</div>
            <div style="font-size:10px;color:var(--muted,#64748b);">Reddedilen</div>
        </div>
    </div>
</div>
@endif

{{-- ── Timeline ── --}}
@php $activityFeedSafe = $activityFeed ?? []; @endphp
@if(!empty($activityFeedSafe))
<div class="gdb-tl">
    <div class="gdb-tl-head"><h4>📅 Son Aktiviteler</h4></div>
    @foreach(array_slice($activityFeedSafe, 0, 4) as $act)
        <div class="gdb-tl-item">
            <div class="gdb-tl-dot"></div>
            <div class="gdb-tl-text">{{ $act['text'] ?? '' }}</div>
            <div class="gdb-tl-time">{{ $act['time_label'] ?? '' }}</div>
        </div>
    @endforeach
</div>
@endif

@endsection

{{-- ── Onboarding Modal ── --}}
@if(!empty($onboardingSteps) && ($onboardingPending ?? false))
@php
    $obTotal = count($onboardingSteps);
    $obDone  = collect($onboardingSteps)->where('done', true)->count();
    $obPct   = $obTotal > 0 ? (int) round($obDone / $obTotal * 100) : 0;
@endphp
<div id="ob-modal-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9000;align-items:center;justify-content:center;padding:16px;">
    <div style="background:var(--u-card,#fff);border-radius:20px;max-width:520px;width:100%;max-height:88vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 24px 60px rgba(0,0,0,.25);">
        <div style="padding:24px 28px 16px;border-bottom:1px solid var(--u-line,#e2e8f0);">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <div style="font-size:20px;font-weight:800;color:var(--u-text);">👋 Hoş Geldiniz!</div>
                    <div style="font-size:13px;color:var(--u-muted);margin-top:4px;">Birkaç adımda başlangıç rehberinizi tamamlayın.</div>
                </div>
                <button id="ob-close-btn" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--u-muted);padding:4px;">✕</button>
            </div>
            <div style="margin-top:14px;">
                <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:600;color:var(--u-muted);margin-bottom:5px;">
                    <span>İlerleme</span><span>{{ $obDone }}/{{ $obTotal }}</span>
                </div>
                <div style="height:6px;background:var(--u-line);border-radius:99px;overflow:hidden;">
                    <div style="height:100%;width:{{ $obPct }}%;background:var(--u-brand);border-radius:99px;"></div>
                </div>
            </div>
        </div>
        <div style="padding:16px 28px 24px;overflow-y:auto;flex:1;">
            @foreach($onboardingSteps as $obs)
            <a href="{{ $obs['url'] }}"
               style="display:flex;align-items:center;gap:14px;padding:12px;border:1px solid var(--u-line);border-radius:12px;margin-bottom:8px;text-decoration:none;color:var(--u-text);{{ $obs['done'] ? 'opacity:.6;' : '' }}">
                <div style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;
                    {{ $obs['done'] ? 'background:rgba(22,163,74,.12);' : 'background:rgba(37,99,235,.1);' }}">
                    {{ $obs['done'] ? '✓' : ($obs['icon'] ?? '•') }}
                </div>
                <div style="flex:1;">
                    <div style="font-size:14px;font-weight:600;{{ $obs['done'] ? 'text-decoration:line-through;' : '' }}">{{ $obs['label'] }}</div>
                </div>
                @if(!$obs['done'])
                <span style="font-size:12px;color:var(--u-brand);font-weight:600;">Başla →</span>
                @endif
            </a>
            @endforeach
        </div>
    </div>
</div>
@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var overlay = document.getElementById('ob-modal-overlay');
    if(!overlay) return;
    var closeBtn = document.getElementById('ob-close-btn');
    if(closeBtn) closeBtn.addEventListener('click', function(){ overlay.style.display = 'none'; });
    overlay.addEventListener('click', function(e){ if(e.target === overlay) overlay.style.display = 'none'; });
})();
</script>
@endpush
@endif
