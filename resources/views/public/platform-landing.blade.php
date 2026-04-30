<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    $brand = $brandName ?? config('brand.name', 'MentorDE');
@endphp
<title>{{ $brand }} Platform — Almanya Eğitim Danışmanlığı için End-to-End SaaS</title>
<meta name="description" content="6 portal · 28+ modül · AI asistan · 13K+ Almanya programı · entegre CRM, vize, ödeme & analytics. Yurt dışı eğitim danışmanlığı firmaları için profesyonel SaaS çözümü.">
<meta name="robots" content="index, follow">
<meta property="og:title" content="{{ $brand }} Platform — Yurt Dışı Eğitim Danışmanlığı SaaS">
<meta property="og:description" content="Tek panel, sınırsız ölçek. Almanya odaklı, çok-portal, AI destekli end-to-end danışmanlık platformu.">
<meta property="og:type" content="website">

<link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    /* MentörDE Brand Palette (brandbook 2026-01-26) */
    --primary:#7e58bf;          /* en koyu mor */
    --primary-dark:#6c47a8;
    --primary-deep:#5a3a8d;
    --primary-mid:#a07ed9;       /* orta geçiş mor */
    --primary-light:#b79ae9;     /* en açık mor */
    --primary-soft:#efe9fb;      /* mor zarf */
    --neutral:#e9e7e2;           /* brandbook nötr/cream */
    --neutral-soft:#faf9f5;
    --accent:#7e58bf;            /* legacy alias (eski kodda kullanılıyor — moruyla uyumlu) */
    --accent-dark:#6c47a8;
    --success:#16a34a;
    --info:#2563eb;
    --danger:#dc2626;
    --warn:#f59e0b;
    --text:#1a1325;              /* brandbook'taki koyu yumuşak siyah */
    --muted:#6b6377;
    --line:#e3dcec;
    --surface:#ffffff;
    --bg:#faf9f5;
    --gradient-purple:linear-gradient(140deg, #7e58bf 0%, #5a3a8d 100%);
    --gradient-mid:linear-gradient(140deg, #a07ed9 0%, #7e58bf 100%);
    --gradient-mix:linear-gradient(140deg, #7e58bf 0%, #b79ae9 100%);
    --gradient-soft:linear-gradient(180deg, #efe9fb 0%, #faf9f5 100%);
    --font-base:"Space Grotesk", "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, sans-serif;
}
* { box-sizing:border-box; }
html, body { margin:0; padding:0; scroll-behavior:smooth; }
body {
    font-family:var(--font-base);
    color:var(--text);
    background:linear-gradient(180deg, #f7f3ff 0%, #faf9f5 50%, #e9e7e2 100%);
    line-height:1.6;
    font-size:15px;
    -webkit-font-smoothing:antialiased;
    font-feature-settings:"ss01", "ss02";
}
.serif { font-family:var(--font-base); font-weight:600; font-style:italic; letter-spacing:-.5px; }
a { color:var(--primary); text-decoration:none; }
img { max-width:100%; height:auto; display:block; }

/* === NAV === */
.p-nav {
    position:sticky; top:0; z-index:50;
    background:rgba(255,255,255,.92); backdrop-filter:blur(12px);
    border-bottom:1px solid var(--line);
}
.p-nav-inner { max-width:1200px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; padding:14px 22px; gap:16px; }
.p-logo { font-family:var(--font-base); font-size:28px; color:var(--primary); letter-spacing:-.5px; line-height:1; display:inline-flex; align-items:center; gap:8px; font-weight:700; }
.p-logo span { color:var(--primary-mid); font-style:italic; font-weight:600; }
.p-logo img { height:36px; width:auto; max-width:200px; display:block; }
.p-nav-links { display:flex; gap:28px; font-size:14px; font-weight:600; }
.p-nav-links a { color:var(--muted); }
.p-nav-links a:hover { color:var(--primary); text-decoration:none; }
.p-nav-cta {
    padding:10px 20px; background:var(--primary); color:#fff !important;
    border-radius:10px; font-size:13px; font-weight:700;
}
.p-nav-cta:hover { background:var(--primary-dark); text-decoration:none !important; }
@media(max-width:820px) { .p-nav-links { display:none; } }

/* === LAYOUT === */
.container { max-width:1200px; margin:0 auto; padding:0 22px; }
section { padding:90px 0; position:relative; }
.sec-bg-white { background:#fff; }
.sec-bg-soft  { background:linear-gradient(180deg, rgba(91,46,145,.04), transparent 80%); }
.sec-bg-dark  { background:var(--gradient-purple); color:#fff; }

.sec-label {
    display:inline-block; color:var(--primary); text-transform:uppercase;
    letter-spacing:.18em; font-size:12px; font-weight:800; margin-bottom:14px;
    background:var(--primary-soft); padding:6px 14px; border-radius:20px;
}
.sec-bg-dark .sec-label { color:#fff; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.28); }
.sec-title {
    font-family:var(--font-base); font-style:italic;
    font-size:clamp(32px, 4.5vw, 52px); line-height:1.1; color:var(--primary-deep);
    letter-spacing:-1.5px; margin:0 0 18px; max-width:900px;
}
.sec-bg-dark .sec-title { color:#fff; }
.sec-lead { font-size:18px; color:var(--muted); max-width:760px; margin:0 0 50px; line-height:1.6; }
.sec-bg-dark .sec-lead { color:rgba(255,255,255,.85); }

.btn-primary {
    display:inline-flex; align-items:center; gap:8px;
    padding:16px 32px; background:var(--primary); color:#fff !important;
    border-radius:12px; font-size:15px; font-weight:700; border:none; cursor:pointer;
    box-shadow:0 6px 20px rgba(91,46,145,.32);
    transition:all .18s;
}
.btn-primary:hover { background:var(--primary-dark); transform:translateY(-2px); text-decoration:none !important; box-shadow:0 12px 32px rgba(91,46,145,.4); }
.btn-ghost {
    display:inline-flex; align-items:center; gap:8px;
    padding:16px 30px; border:2px solid var(--primary); color:var(--primary) !important;
    border-radius:12px; font-size:15px; font-weight:700; background:#fff;
    transition:all .18s;
}
.btn-ghost:hover { background:var(--primary-soft); text-decoration:none !important; }
.btn-gold {
    display:inline-flex; align-items:center; gap:8px;
    padding:16px 32px;
    background:#fff; color:var(--primary-deep) !important;
    border-radius:12px; font-size:15px; font-weight:800; border:2px solid #fff; cursor:pointer;
    box-shadow:0 12px 32px rgba(0,0,0,.18), 0 0 0 1px rgba(255,255,255,.5);
    transition:all .18s;
}
.btn-gold:hover {
    background:var(--neutral); color:var(--primary) !important;
    transform:translateY(-2px); text-decoration:none !important;
    box-shadow:0 18px 40px rgba(0,0,0,.28);
}

/* === HERO === */
.hero { padding:80px 0 60px; position:relative; overflow:hidden; }
.hero::before {
    content:''; position:absolute; inset:0; z-index:-1;
    background:
        radial-gradient(80% 60% at 70% 20%, rgba(91,46,145,.18), transparent 70%),
        radial-gradient(60% 50% at 20% 80%, rgba(233,231,226,.18), transparent 70%);
}
.hero-grid { display:grid; grid-template-columns:1.2fr 1fr; gap:60px; align-items:center; }
@media(max-width:920px) { .hero-grid { grid-template-columns:1fr; gap:40px; } }
.hero-badge {
    display:inline-flex; align-items:center; gap:8px;
    background:#fff; color:var(--primary-deep); padding:8px 16px;
    border-radius:30px; font-size:12px; font-weight:800;
    text-transform:uppercase; letter-spacing:.1em;
    border:1px solid var(--primary-soft);
    box-shadow:0 4px 12px rgba(91,46,145,.08);
    margin-bottom:24px;
}
.hero-badge .dot { width:8px; height:8px; border-radius:50%; background:var(--success); animation:pulse 1.6s ease-out infinite; }
@keyframes pulse { 0% { box-shadow:0 0 0 0 rgba(22,163,74,.6); } 70% { box-shadow:0 0 0 10px rgba(22,163,74,0); } 100% { box-shadow:0 0 0 0 rgba(22,163,74,0); } }
.hero h1 {
    font-family:var(--font-base); font-weight:700;
    font-size:clamp(40px, 6vw, 68px); line-height:1.04; letter-spacing:-2px;
    margin:0 0 24px; color:var(--primary-deep); font-style:normal;
}
.hero h1 em {
    font-style:italic; font-weight:800;
    color:var(--primary-deep);
    background:linear-gradient(180deg, transparent 60%, var(--primary-light) 60%, var(--primary-light) 92%, transparent 92%);
    -webkit-background-clip:initial; background-clip:initial;
    padding:0 4px; box-decoration-break:clone; -webkit-box-decoration-break:clone;
}
.hero-lead { font-size:19px; color:var(--muted); margin:0 0 36px; max-width:600px; line-height:1.6; }
.hero-ctas { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:36px; }
.hero-trust { display:flex; gap:32px; flex-wrap:wrap; align-items:center; padding-top:24px; border-top:1px solid var(--line); }
.hero-trust-item { font-size:12px; color:var(--muted); position:relative; padding-left:14px; }
.hero-trust-item::before {
    content:''; position:absolute; left:0; top:4px; bottom:4px; width:3px;
    background:var(--gradient-mid); border-radius:2px;
}
.hero-trust-item strong { display:block; font-size:28px; color:var(--primary-deep); font-family:var(--font-base); font-weight:700; line-height:1; letter-spacing:-.5px; }

/* Hero altında Almanya bayrak şeridi (brandbook görsel kimliği) */
.hero-flag-strip {
    display:flex; height:5px; border-radius:3px; overflow:hidden;
    margin:0 0 28px; max-width:200px;
    box-shadow:0 2px 8px rgba(0,0,0,.08);
}
.hero-flag-strip > div { flex:1; }
.hero-flag-strip .fl-blk { background:#1a1a1a; }
.hero-flag-strip .fl-red { background:#dc2626; }
.hero-flag-strip .fl-gld { background:#fbbf24; }

.hero-visual {
    position:relative; perspective:1200px;
}
.hero-card-stack {
    position:relative; transform-style:preserve-3d;
    transform:rotateY(-12deg) rotateX(6deg);
}
.hero-card {
    background:#fff; border:1px solid var(--line); border-radius:18px;
    padding:20px; box-shadow:0 28px 56px rgba(126,88,191,.22);
    margin-bottom:14px;
}
.hero-card-1 { transform:translateZ(0); border-top:3px solid var(--primary); }
.hero-card-2 { transform:translateZ(20px) translateX(40px) translateY(20px); position:absolute; top:80px; right:-30px; width:240px; border-top:3px solid var(--success); }
.hero-card-3 { transform:translateZ(40px) translateX(-30px) translateY(60px); position:absolute; bottom:-30px; left:-20px; width:220px; border-top:3px solid var(--primary-mid); }
.hero-card .lbl { font-size:10px; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; font-weight:700; margin-bottom:6px; }
.hero-card .val { font-family:var(--font-base); font-weight:700; font-size:28px; color:var(--primary-deep); line-height:1; letter-spacing:-1px; }
.hero-card .val.eur { color:var(--success); }
.hero-card .delta { font-size:11px; color:var(--success); font-weight:700; margin-top:4px; }

/* === STATS === */
.stats-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:24px; }
@media(max-width:900px) { .stats-grid { grid-template-columns:repeat(2, 1fr); } }
.stat {
    background:#fff; border:1px solid var(--line); border-radius:16px;
    padding:32px 24px; text-align:center; transition:all .25s; position:relative; overflow:hidden;
}
.stat:hover { transform:translateY(-4px); box-shadow:0 20px 40px rgba(91,46,145,.12); }
.stat::before {
    content:''; position:absolute; top:0; left:0; right:0; height:4px;
    background:var(--gradient-mix);
}
.stat-icon { font-size:36px; margin-bottom:14px; }
.stat-num { font-family:var(--font-base); font-weight:700; font-size:46px; line-height:1; color:var(--primary-deep); margin-bottom:8px; letter-spacing:-1px; }
.stat-num span { color:var(--accent-dark); }
.stat-lbl { font-size:13px; color:var(--muted); font-weight:600; }

/* === ROI PROOF PANEL === */
.roi-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:18px; }
@media(max-width:900px) { .roi-grid { grid-template-columns:repeat(2, 1fr); } }
@media(max-width:520px) { .roi-grid { grid-template-columns:1fr; } }
.roi-card {
    background:linear-gradient(145deg, #fff 0%, var(--neutral-soft) 100%);
    border:1px solid var(--line); border-radius:16px;
    padding:28px 22px; text-align:left; position:relative; overflow:hidden;
    transition:all .2s;
}
.roi-card:hover { transform:translateY(-3px); box-shadow:0 16px 36px rgba(126,88,191,.15); border-color:var(--primary-light); }
.roi-card::before {
    content:''; position:absolute; top:0; left:0; bottom:0; width:5px;
    background:var(--gradient-mid);
}
.roi-num {
    font-family:var(--font-base); font-weight:700; font-size:48px; line-height:1;
    color:var(--primary); margin-bottom:10px; letter-spacing:-2px;
}
.roi-lbl { font-size:14px; font-weight:700; color:var(--text); line-height:1.4; margin-bottom:6px; }
.roi-sub { font-size:12px; color:var(--muted); line-height:1.5; }

/* === PROBLEM-SOLUTION === */
.compare-grid { display:grid; grid-template-columns:1fr 1fr; gap:30px; }
@media(max-width:820px) { .compare-grid { grid-template-columns:1fr; } }
.compare-card {
    border-radius:20px; padding:36px; position:relative;
}
.compare-card.bad { background:#fef2f2; border:1px solid #fecaca; }
.compare-card.good { background:linear-gradient(140deg, var(--primary-soft), #fff); border:2px solid var(--primary); box-shadow:0 16px 40px rgba(91,46,145,.15); }
.compare-card h3 { margin:0 0 16px; font-size:20px; color:var(--primary-deep); display:flex; align-items:center; gap:10px; }
.compare-card.bad h3 { color:#991b1b; }
.compare-card ul { list-style:none; padding:0; margin:0; }
.compare-card li { padding:12px 0 12px 30px; position:relative; font-size:14px; line-height:1.55; border-bottom:1px solid rgba(0,0,0,.05); }
.compare-card li:last-child { border-bottom:0; }
.compare-card.bad li::before { content:'✕'; position:absolute; left:0; color:#dc2626; font-weight:900; font-size:16px; }
.compare-card.good li::before { content:'✓'; position:absolute; left:0; color:var(--success); font-weight:900; font-size:16px; }

/* === 3-TIER PRICING === */
.tier-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:22px; align-items:stretch; }
@media(max-width:980px) { .tier-grid { grid-template-columns:1fr; gap:16px; } }
.tier-card {
    background:#fff; border:1px solid var(--line); border-radius:18px;
    padding:32px 28px; position:relative; display:flex; flex-direction:column;
    transition:all .25s;
}
.tier-card:hover { transform:translateY(-3px); box-shadow:0 20px 48px rgba(126,88,191,.12); }
.tier-card.featured {
    border:2px solid var(--primary);
    background:linear-gradient(180deg, #fff 0%, var(--primary-soft) 100%);
    box-shadow:0 24px 56px rgba(126,88,191,.18);
    transform:scale(1.03);
}
.tier-card.featured:hover { transform:scale(1.03) translateY(-3px); }
.tier-tag-pop {
    position:absolute; top:-13px; left:50%; transform:translateX(-50%);
    background:var(--gradient-purple); color:#fff;
    padding:6px 18px; border-radius:999px;
    font-size:11px; font-weight:700; letter-spacing:1px; text-transform:uppercase;
    box-shadow:0 6px 16px rgba(126,88,191,.32);
}
.tier-header { margin-bottom:18px; }
.tier-icon { font-size:34px; margin-bottom:10px; }
.tier-name { font-family:var(--font-base); font-weight:700; font-size:24px; color:var(--primary-deep); margin:0 0 6px; letter-spacing:-.5px; }
.tier-tag { font-size:12px; color:var(--muted); margin:0; line-height:1.4; }
.tier-price { display:flex; align-items:baseline; gap:6px; margin-top:8px; }
.tier-num { font-family:var(--font-base); font-weight:700; font-size:44px; color:var(--primary-deep); line-height:1; letter-spacing:-1.5px; }
.tier-per { font-size:14px; color:var(--muted); font-weight:600; }
.tier-yearly { font-size:12px; color:var(--muted); margin-bottom:22px; }
.tier-yearly small { color:var(--success); font-weight:700; }
.tier-features { list-style:none; padding:0; margin:0 0 24px; flex:1; }
.tier-features li {
    padding:9px 0 9px 24px; position:relative; font-size:13.5px; line-height:1.5;
    border-bottom:1px solid var(--line);
}
.tier-features li:last-child { border-bottom:0; }
.tier-features li::before { content:'✓'; position:absolute; left:0; color:var(--success); font-weight:900; }
.tier-features li.tier-disabled { color:var(--muted); opacity:.6; }
.tier-features li.tier-disabled::before { content:'—'; color:var(--muted); }
.tier-cta {
    display:block; padding:14px 22px; text-align:center;
    background:var(--surface); color:var(--primary) !important;
    border:2px solid var(--primary); border-radius:12px;
    font-size:14px; font-weight:700; text-decoration:none;
    transition:all .15s;
}
.tier-cta:hover { background:var(--primary); color:#fff !important; }
.tier-card.featured .tier-cta { background:var(--primary); color:#fff !important; }
.tier-card.featured .tier-cta:hover { background:var(--primary-deep); }
.tier-trust {
    display:flex; flex-wrap:wrap; justify-content:center; gap:24px;
    margin-top:34px; font-size:13px; color:var(--muted); font-weight:600;
}

/* === VS MATRIX === */
.vs-matrix-wrap { margin-top:50px; padding:36px; background:#fff; border:1px solid var(--line); border-radius:20px; box-shadow:0 4px 18px rgba(0,0,0,.04); }
.vs-matrix-title { font-family:var(--font-base); font-weight:700; font-size:24px; color:var(--primary-deep); margin:0 0 8px; letter-spacing:-.5px; }
.vs-matrix-sub { font-size:14px; color:var(--muted); margin:0 0 22px; line-height:1.55; }
.vs-matrix-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.vs-matrix { width:100%; min-width:780px; border-collapse:collapse; font-size:13.5px; }
.vs-matrix th, .vs-matrix td { text-align:left; padding:14px 16px; vertical-align:top; }
.vs-matrix thead th {
    font-family:var(--font-base); font-weight:700; font-size:13px;
    color:var(--text); border-bottom:2px solid var(--line);
    background:var(--neutral-soft);
}
.vs-matrix thead th span { display:block; font-size:11px; font-weight:500; color:var(--muted); margin-top:2px; }
.vs-matrix tbody tr { border-bottom:1px solid var(--line); }
.vs-matrix tbody tr:last-child { border-bottom:0; }
.vs-matrix tbody tr:hover { background:var(--neutral-soft); }
.vs-matrix tbody td:first-child { font-weight:600; color:var(--text); width:28%; }
.vs-matrix .col-bad { background:rgba(220,38,38,.04); color:#7f1d1d; }
.vs-matrix .col-mid { background:rgba(245,158,11,.04); color:#92400e; }
.vs-matrix .col-good { background:rgba(126,88,191,.06); color:var(--primary-deep); font-weight:600; }
.vs-matrix thead th.col-bad  { background:rgba(220,38,38,.1);  color:#991b1b; }
.vs-matrix thead th.col-mid  { background:rgba(245,158,11,.1); color:#92400e; }
.vs-matrix thead th.col-good { background:rgba(126,88,191,.12); color:var(--primary-deep); }

/* === PORTAL CARDS === */
.portals-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:24px; }
@media(max-width:900px) { .portals-grid { grid-template-columns:repeat(2, 1fr); } }
@media(max-width:560px) { .portals-grid { grid-template-columns:1fr; } }
.portal {
    background:#fff; border:2px solid var(--line); border-radius:20px;
    padding:32px 24px; transition:all .25s; position:relative; overflow:hidden;
}
.portal:hover { border-color:var(--primary); transform:translateY(-4px); box-shadow:0 20px 40px rgba(91,46,145,.15); }
.portal-icon-wrap {
    width:64px; height:64px; border-radius:16px; display:flex; align-items:center; justify-content:center;
    font-size:32px; margin-bottom:18px; color:#fff;
}
.portal-icon-wrap.guest { background:linear-gradient(140deg, #2563eb, #1e3a8a); }
.portal-icon-wrap.student { background:linear-gradient(140deg, #7c3aed, #4c1d95); }
.portal-icon-wrap.senior { background:linear-gradient(140deg, #db2777, #831843); }
.portal-icon-wrap.dealer { background:linear-gradient(140deg, #16a34a, #14532d); }
.portal-icon-wrap.manager { background:linear-gradient(140deg, #5b2e91, #3d1c67); }
.portal-icon-wrap.marketing { background:linear-gradient(140deg, #e8b931, #c99c26); }
.portal-name { font-size:13px; color:var(--muted); text-transform:uppercase; letter-spacing:.1em; font-weight:700; margin-bottom:4px; }
.portal h3 { margin:0 0 12px; font-size:22px; color:var(--primary-deep); }
.portal p { margin:0 0 16px; color:var(--muted); font-size:14px; line-height:1.55; }
.portal-features { list-style:none; padding:0; margin:0; }
.portal-features li { padding:6px 0 6px 22px; position:relative; font-size:13px; color:var(--text); line-height:1.5; }
.portal-features li::before { content:'→'; position:absolute; left:0; color:var(--primary); font-weight:800; }

/* === MODULES === */
.modules-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; }
@media(max-width:900px) { .modules-grid { grid-template-columns:repeat(2, 1fr); } }
@media(max-width:560px) { .modules-grid { grid-template-columns:1fr; } }
.module {
    background:#fff; border:1px solid var(--line); border-radius:14px;
    padding:24px; transition:all .2s; display:flex; gap:16px; align-items:flex-start;
}
.module:hover { border-color:var(--primary); transform:translateY(-2px); box-shadow:0 12px 28px rgba(91,46,145,.1); }
.module-icon {
    flex-shrink:0; width:48px; height:48px; border-radius:12px;
    background:var(--primary-soft); color:var(--primary);
    display:flex; align-items:center; justify-content:center; font-size:24px;
}
.module h4 { margin:0 0 6px; font-size:15px; color:var(--primary-deep); font-weight:800; }
.module p { margin:0; font-size:13px; color:var(--muted); line-height:1.55; }

/* === FLAGSHIP MODULES (Vurgu) === */
.hl-section-tag {
    display:inline-block; background:var(--primary-soft); color:var(--primary);
    padding:8px 18px; border-radius:999px; font-size:12px; font-weight:700;
    text-transform:uppercase; letter-spacing:1.5px; margin-bottom:18px;
}
.hl-section-sub { font-size:14px; color:var(--muted); margin:0 0 22px; max-width:680px; }
.hl-modules-grid {
    display:grid; grid-template-columns:repeat(4, 1fr); gap:18px; margin-bottom:30px;
}
@media(max-width:1100px) { .hl-modules-grid { grid-template-columns:repeat(3, 1fr); } }
@media(max-width:820px)  { .hl-modules-grid { grid-template-columns:repeat(2, 1fr); } }
@media(max-width:520px)  { .hl-modules-grid { grid-template-columns:1fr; } }
.hl-module {
    background:#fff; border:1px solid var(--line); border-radius:18px;
    padding:26px 22px; transition:all .2s; position:relative; overflow:hidden;
}
.hl-module::before {
    content:''; position:absolute; top:0; left:0; right:0; height:4px;
    background:var(--gradient-mid); opacity:.85;
}
.hl-module:hover {
    transform:translateY(-3px); border-color:var(--primary-light);
    box-shadow:0 18px 40px rgba(126,88,191,.15);
}
.hl-module-icon {
    width:54px; height:54px; border-radius:14px;
    background:linear-gradient(135deg, var(--primary-soft) 0%, var(--neutral) 100%);
    color:var(--primary); display:flex; align-items:center; justify-content:center;
    font-size:28px; margin-bottom:14px;
    box-shadow:inset 0 0 0 1px rgba(126,88,191,.1);
}
.hl-module h4 {
    font-family:var(--font-base); font-weight:700; font-size:16px;
    color:var(--primary-deep); margin:0 0 8px; letter-spacing:-.3px; line-height:1.25;
}
.hl-module p { margin:0; font-size:13px; color:var(--muted); line-height:1.55; }

/* === COMPACT MODULE PILLS === */
.modules-pills {
    display:grid; grid-template-columns:repeat(3, 1fr); gap:8px;
}
@media(max-width:900px) { .modules-pills { grid-template-columns:repeat(2, 1fr); } }
@media(max-width:520px) { .modules-pills { grid-template-columns:1fr; } }
.m-pill {
    background:#fff; border:1px solid var(--line); border-radius:10px;
    padding:11px 14px; display:flex; align-items:center; gap:10px;
    font-size:12.5px; line-height:1.4; color:var(--text);
    transition:all .15s;
}
.m-pill:hover { border-color:var(--primary-light); background:var(--neutral-soft); }
.m-pill-icon {
    flex-shrink:0; width:30px; height:30px; border-radius:8px;
    background:var(--primary-soft); display:flex; align-items:center; justify-content:center;
    font-size:15px;
}
.m-pill strong { color:var(--primary-deep); font-weight:700; }

/* === AI HIGHLIGHT === */
.ai-spotlight {
    background:var(--gradient-purple); color:#fff;
    border-radius:32px; padding:60px; position:relative; overflow:hidden;
    box-shadow:0 24px 60px rgba(61,28,103,.3);
}
.ai-spotlight::before {
    content:''; position:absolute; top:-50px; right:-50px; width:300px; height:300px;
    background:radial-gradient(circle, rgba(233,231,226,.3), transparent 70%);
    border-radius:50%;
}
.ai-grid { display:grid; grid-template-columns:1.2fr 1fr; gap:50px; align-items:center; position:relative; z-index:1; }
@media(max-width:900px) { .ai-grid { grid-template-columns:1fr; } }
.ai-spotlight h2 { font-family:var(--font-base); font-style:italic; font-size:42px; line-height:1.1; margin:0 0 18px; color:#fff; }
.ai-spotlight h2 em { color:var(--neutral); font-weight:700; }
.ai-spotlight p { font-size:17px; opacity:.9; margin:0 0 30px; }
.ai-features { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:30px; }
.ai-feature { background:rgba(255,255,255,.08); border-radius:12px; padding:14px; backdrop-filter:blur(10px); }
.ai-feature .lbl { font-size:11px; opacity:.7; text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px; }
.ai-feature .val { font-size:15px; font-weight:700; color:#fff; }
.ai-mockup { background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.15); border-radius:18px; padding:24px; backdrop-filter:blur(10px); }
.ai-msg { background:rgba(233,231,226,.18); border-radius:12px; padding:12px 14px; margin-bottom:10px; font-size:13px; }
.ai-reply { background:rgba(255,255,255,.08); border-radius:12px; padding:12px 14px; font-size:13px; line-height:1.55; }
.ai-reply .badge { display:inline-block; background:rgba(255,255,255,.18); color:#fff; padding:2px 8px; border-radius:8px; font-size:10px; font-weight:700; margin-bottom:6px; }

/* === WORKFLOW === */
.workflow {
    background:#fff; border-radius:24px; padding:40px;
    border:1px solid var(--line); position:relative;
}
.flow-steps { display:flex; gap:14px; flex-wrap:wrap; align-items:stretch; }
.flow-step {
    flex:1; min-width:180px;
    background:linear-gradient(140deg, var(--primary-soft), #fff);
    border:1px solid var(--line); border-radius:14px; padding:20px;
    position:relative; transition:all .2s;
}
.flow-step:hover { border-color:var(--primary); transform:translateY(-3px); }
.flow-num { position:absolute; top:-14px; left:18px; background:var(--primary); color:#fff; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px; }
.flow-icon { font-size:28px; margin-bottom:8px; }
.flow-step h4 { margin:0 0 6px; font-size:15px; color:var(--primary-deep); font-weight:700; }
.flow-step p { margin:0; font-size:12px; color:var(--muted); line-height:1.5; }
.flow-step .meta { display:inline-block; background:#fff; padding:3px 10px; border-radius:8px; font-size:10px; color:var(--primary); font-weight:700; margin-top:8px; border:1px solid var(--primary-soft); }

/* === ANALYTICS DASHBOARD MOCKUP === */
.dash-frame { background:#fff; border:1px solid var(--line); border-radius:20px; padding:28px; box-shadow:0 24px 50px rgba(91,46,145,.12); max-width:1000px; margin:0 auto; }
.dash-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:14px; margin-bottom:24px; }
@media(max-width:700px) { .dash-grid { grid-template-columns:repeat(2, 1fr); } }
.dash-kpi { background:#f8fafc; border-radius:12px; padding:16px; }
.dash-kpi .lbl { font-size:10px; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; }
.dash-kpi .val { font-size:24px; font-weight:800; color:var(--primary-deep); margin-top:4px; }
.dash-kpi .delta { font-size:11px; color:var(--success); font-weight:700; margin-top:2px; }
.dash-chart { height:160px; background:linear-gradient(180deg, var(--primary-soft), #fff); border-radius:12px; padding:14px; display:flex; align-items:flex-end; gap:6px; }
.dash-bar { flex:1; background:var(--gradient-mix); border-radius:4px 4px 0 0; min-height:14px; }

/* === INTEGRATIONS === */
.integ-grid { display:grid; grid-template-columns:repeat(6, 1fr); gap:18px; }
@media(max-width:900px) { .integ-grid { grid-template-columns:repeat(3, 1fr); } }
@media(max-width:540px) { .integ-grid { grid-template-columns:repeat(2, 1fr); } }
.integ {
    background:#fff; border:1px solid var(--line); border-radius:14px;
    padding:20px 16px; text-align:center; transition:all .2s;
}
.integ:hover { border-color:var(--primary); transform:translateY(-3px); box-shadow:0 8px 20px rgba(91,46,145,.1); }
.integ-icon { font-size:32px; margin-bottom:8px; }
.integ-name { font-size:12px; font-weight:700; color:var(--primary-deep); }

/* === SECURITY === */
.sec-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:18px; }
@media(max-width:720px) { .sec-grid { grid-template-columns:1fr; } }
.sec-card {
    background:#fff; border-left:4px solid var(--success);
    border-radius:14px; padding:24px; display:flex; gap:16px; align-items:flex-start;
    box-shadow:0 4px 14px rgba(0,0,0,.04);
}
.sec-card-icon {
    flex-shrink:0; width:50px; height:50px; border-radius:12px;
    background:#dcfce7; color:var(--success); font-size:24px;
    display:flex; align-items:center; justify-content:center;
}
.sec-card h4 { margin:0 0 6px; font-size:15px; color:var(--primary-deep); }
.sec-card p { margin:0; font-size:13px; color:var(--muted); line-height:1.55; }

/* === PRICING === */
.pricing-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:24px; }
@media(max-width:900px) { .pricing-grid { grid-template-columns:1fr; } }
.pricing-card {
    background:#fff; border:2px solid var(--line); border-radius:24px;
    padding:36px 30px; position:relative; transition:all .25s;
    display:flex; flex-direction:column;
}
.pricing-card:hover { transform:translateY(-4px); box-shadow:0 20px 50px rgba(91,46,145,.15); }
.pricing-card.featured { border-color:var(--primary); background:linear-gradient(180deg, #fff, var(--primary-soft)); transform:scale(1.04); }
.pricing-card.featured:hover { transform:scale(1.04) translateY(-4px); }
.pricing-badge { position:absolute; top:-14px; left:50%; transform:translateX(-50%); background:var(--accent); color:var(--primary-deep); padding:6px 16px; border-radius:20px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
.pricing-tier { font-size:13px; color:var(--muted); text-transform:uppercase; letter-spacing:.12em; font-weight:700; margin-bottom:6px; }
.pricing-name { font-family:var(--font-base); font-style:italic; font-size:32px; color:var(--primary-deep); margin:0 0 14px; }
.pricing-price { display:flex; align-items:baseline; gap:6px; margin-bottom:6px; }
.pricing-price .num { font-family:var(--font-base); font-size:48px; color:var(--primary-deep); line-height:1; }
.pricing-price .period { color:var(--muted); font-size:14px; }
.pricing-desc { color:var(--muted); font-size:13px; margin-bottom:24px; min-height:50px; }
.pricing-features { list-style:none; padding:0; margin:0 0 30px; flex:1; }
.pricing-features li { padding:8px 0 8px 24px; position:relative; font-size:13px; line-height:1.5; }
.pricing-features li::before { content:'✓'; position:absolute; left:0; color:var(--success); font-weight:900; }
.pricing-features li.disabled { color:var(--muted); opacity:.6; }
.pricing-features li.disabled::before { content:'—'; color:var(--muted); }

/* === MODULAR PRICING === */
.modular-pricing-wrap { max-width:1200px; margin:0 auto; }

/* Core plan */
.core-plan {
    background:linear-gradient(140deg, var(--primary-deep), var(--primary));
    color:#fff; border-radius:24px; padding:36px;
    box-shadow:0 24px 50px rgba(61,28,103,.25);
    margin-bottom:36px; position:relative; overflow:hidden;
}
.core-plan::before {
    content:''; position:absolute; top:-80px; right:-80px; width:300px; height:300px;
    background:radial-gradient(circle, rgba(233,231,226,.25), transparent 70%);
    border-radius:50%;
}
.core-plan-header { display:flex; justify-content:space-between; align-items:flex-start; gap:24px; flex-wrap:wrap; margin-bottom:24px; position:relative; z-index:1; }
.core-badge {
    display:inline-block;
    background:#fff; color:var(--primary-deep);
    padding:6px 14px; border-radius:14px; font-size:11px; font-weight:800;
    text-transform:uppercase; letter-spacing:.12em; margin-bottom:12px;
    box-shadow:0 4px 14px rgba(0,0,0,.18), 0 0 0 1px rgba(255,255,255,.4);
}
.core-name { margin:0 0 8px; font-family:var(--font-base); font-style:italic; font-size:32px; color:#fff; }
.core-desc { margin:0; color:rgba(255,255,255,.85); font-size:14px; }
.core-price-block { text-align:right; }
.core-price-row { display:flex; align-items:baseline; gap:6px; justify-content:flex-end; }
.core-price { font-family:var(--font-base); font-weight:700; font-size:56px; line-height:1; color:#fff; text-shadow:0 2px 12px rgba(0,0,0,.25); letter-spacing:-2px; }
.core-period { color:rgba(255,255,255,.7); font-size:14px; }
.core-price-yearly { font-size:12px; color:rgba(255,255,255,.7); margin-top:4px; }
.core-features { display:grid; grid-template-columns:repeat(3, 1fr); gap:10px; position:relative; z-index:1; }
@media(max-width:720px) { .core-features { grid-template-columns:repeat(2, 1fr); } }
@media(max-width:480px) { .core-features { grid-template-columns:1fr; } }
.core-feat { font-size:13px; color:rgba(255,255,255,.92); padding:6px 0; }

/* Add-ons header */
.addons-header { display:flex; justify-content:space-between; align-items:flex-end; gap:20px; flex-wrap:wrap; margin-bottom:18px; padding-top:8px; }
.addons-toggle-all { display:flex; align-items:center; gap:10px; }
.addon-switch { display:inline-flex; align-items:center; gap:10px; cursor:pointer; }
.addon-switch-label { font-size:13px; color:var(--primary); font-weight:700; }

/* Add-ons grid */
.addons-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:18px; margin-bottom:36px; }
@media(max-width:980px) { .addons-grid { grid-template-columns:repeat(2, 1fr); } }
@media(max-width:600px) { .addons-grid { grid-template-columns:1fr; } }

.addon-card {
    background:#fff; border:2px solid var(--line); border-radius:18px;
    padding:24px; transition:all .25s; position:relative; cursor:pointer;
    display:flex; flex-direction:column;
}
.addon-card:hover { border-color:var(--primary); transform:translateY(-3px); box-shadow:0 16px 36px rgba(91,46,145,.12); }
.addon-card.active { border-color:var(--primary); background:linear-gradient(180deg, var(--primary-soft), #fff); box-shadow:0 12px 32px rgba(91,46,145,.18); }
.addon-card.active::before {
    content:'✓'; position:absolute; top:-10px; right:20px;
    background:var(--success); color:#fff; width:28px; height:28px;
    border-radius:50%; display:flex; align-items:center; justify-content:center;
    font-weight:900; font-size:14px;
    box-shadow:0 4px 10px rgba(22,163,74,.4);
}
.addon-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
.addon-icon {
    width:54px; height:54px; border-radius:14px; color:#fff; font-size:28px;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
    box-shadow:0 6px 18px rgba(0,0,0,.1);
}

/* Switch */
.addon-switch input[type=checkbox] { display:none; }
.addon-slider {
    width:48px; height:26px; background:#e2e8f0; border-radius:13px;
    position:relative; transition:all .2s; cursor:pointer;
    flex-shrink:0;
}
.addon-slider::after {
    content:''; position:absolute; top:3px; left:3px; width:20px; height:20px;
    background:#fff; border-radius:50%; transition:all .2s;
    box-shadow:0 2px 4px rgba(0,0,0,.2);
}
.addon-switch input:checked + .addon-slider { background:var(--primary); }
.addon-switch input:checked + .addon-slider::after { transform:translateX(22px); }

.addon-card h4 { margin:0 0 6px; font-size:17px; color:var(--primary-deep); font-weight:800; }
.addon-price { font-family:var(--font-base); font-size:28px; color:var(--primary); margin-bottom:10px; }
.addon-price span { font-size:13px; color:var(--muted); font-family:var(--font-base); }
.addon-card > p { color:var(--muted); font-size:13px; line-height:1.55; margin:0 0 14px; }
.addon-features { list-style:none; padding:0; margin:0; flex:1; }
.addon-features li { padding:5px 0 5px 20px; position:relative; font-size:12px; color:var(--text); line-height:1.5; }
.addon-features li::before { content:'→'; position:absolute; left:0; color:var(--primary); font-weight:800; }

/* Price summary — pricing section sonunda inline (sticky kaldırıldı) */
.price-summary {
    background:#fff; border:2px solid var(--primary); border-radius:24px;
    padding:32px 36px; box-shadow:0 16px 40px rgba(91,46,145,.15);
    margin-top:8px;
}

/* Floating mini-pill: scroll'da küçük gösterim — featurelara hiç dokunmaz */
.price-pill {
    position:fixed; bottom:24px; right:24px; z-index:40;
    background:var(--primary); color:#fff;
    border-radius:50px; padding:14px 22px;
    box-shadow:0 12px 32px rgba(91,46,145,.4);
    font-weight:800; font-size:15px;
    display:none; align-items:center; gap:10px;
    cursor:pointer; transition:all .2s;
    border:none; font-family:inherit;
}
.price-pill:hover { background:var(--primary-dark); transform:translateY(-2px); }
.price-pill.visible { display:inline-flex; }
.price-pill .pill-count {
    background:var(--accent); color:var(--primary-deep);
    width:24px; height:24px; border-radius:50%;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:11px; font-weight:900;
}
.price-pill .arrow { font-size:18px; }
.ps-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--line); font-size:15px; }
.ps-row:last-child { border-bottom:0; }
.ps-row.total { padding-top:14px; margin-top:6px; border-top:2px solid var(--primary); border-bottom:0; font-size:18px; }
.ps-lbl { color:var(--text); }
.ps-val { color:var(--primary-deep); font-weight:700; font-family:var(--font-base); font-size:20px; }
.ps-val small { font-size:11px; color:var(--muted); font-family:var(--font-base); font-weight:normal; }
.total-amount { color:var(--primary); font-size:36px !important; }
.ps-yearly { background:var(--primary-soft); border-radius:10px; padding:12px 16px; margin-top:14px; font-size:13px; color:var(--primary-deep); }
.ps-yearly small { color:var(--muted); }
.ps-cta { display:flex; gap:10px; margin-top:18px; flex-wrap:wrap; }

/* Enterprise callout */
.enterprise-callout {
    background:linear-gradient(140deg, var(--primary-deep), #1a0f2e);
    border-radius:20px; padding:32px 36px; margin-top:36px;
    display:flex; justify-content:space-between; align-items:center; gap:24px; flex-wrap:wrap;
    box-shadow:0 12px 30px rgba(0,0,0,.2);
}

/* === PARTNER === */
.partner-card {
    background:linear-gradient(140deg, #fff, var(--primary-soft));
    border-radius:32px; padding:60px;
    border:1px solid var(--primary-soft);
    position:relative; overflow:hidden;
}
.partner-card::before {
    content:''; position:absolute; top:-100px; right:-100px; width:300px; height:300px;
    background:radial-gradient(circle, rgba(233,231,226,.3), transparent 70%);
    border-radius:50%;
}
.partner-grid { display:grid; grid-template-columns:1.3fr 1fr; gap:50px; align-items:center; position:relative; z-index:1; }
@media(max-width:900px) { .partner-grid { grid-template-columns:1fr; } }
.partner-stats { display:grid; grid-template-columns:repeat(2, 1fr); gap:14px; margin-top:24px; }
.partner-stat { background:#fff; border-radius:14px; padding:18px; border:1px solid var(--line); text-align:center; }
.partner-stat .num { font-family:var(--font-base); font-size:28px; color:var(--primary-deep); }
.partner-stat .lbl { font-size:11px; color:var(--muted); text-transform:uppercase; }

/* === FAQ === */
.faq-list { max-width:800px; margin:0 auto; }
.faq-item { background:#fff; border:1px solid var(--line); border-radius:14px; margin-bottom:12px; overflow:hidden; }
.faq-item.open { border-color:var(--primary); }
.faq-q { width:100%; text-align:left; padding:20px 24px; background:transparent; border:none; font-size:16px; font-weight:700; color:var(--text); cursor:pointer; display:flex; justify-content:space-between; align-items:center; gap:12px; font-family:inherit; }
.faq-icon { font-size:20px; color:var(--primary); transition:transform .2s; }
.faq-item.open .faq-icon { transform:rotate(45deg); }
.faq-a { max-height:0; overflow:hidden; transition:max-height .3s ease; padding:0 24px; color:var(--muted); font-size:14px; line-height:1.65; }
.faq-item.open .faq-a { max-height:600px; padding-bottom:20px; }

/* === CTA FINAL === */
.cta-final {
    background:var(--gradient-purple); color:#fff;
    text-align:center; padding:100px 0;
    position:relative; overflow:hidden;
}
.cta-final::before {
    content:''; position:absolute; inset:0;
    background:radial-gradient(60% 50% at 30% 30%, rgba(233,231,226,.2), transparent 70%);
}
.cta-final .container { position:relative; z-index:1; }
.cta-final h2 { font-family:var(--font-base); font-style:italic; font-size:clamp(36px, 5vw, 56px); margin:0 0 18px; line-height:1.1; }
.cta-final p { font-size:18px; opacity:.9; margin:0 0 36px; max-width:700px; margin-left:auto; margin-right:auto; }
.cta-final .ctas { display:flex; gap:16px; justify-content:center; flex-wrap:wrap; }
.cta-final .contacts { display:flex; gap:24px; justify-content:center; flex-wrap:wrap; margin-top:42px; font-size:14px; }
.cta-final .contacts a { color:#fff !important; text-decoration:underline; text-decoration-color:rgba(255,255,255,.3); }

/* === FOOTER === */
footer { background:#0f172a; color:rgba(255,255,255,.7); padding:48px 0 24px; font-size:13px; }
footer .container { display:grid; grid-template-columns:1.5fr 1fr 1fr 1fr; gap:40px; }
@media(max-width:720px) { footer .container { grid-template-columns:1fr 1fr; } }
footer h5 { color:#fff; font-size:13px; text-transform:uppercase; letter-spacing:.08em; margin:0 0 12px; }
footer ul { list-style:none; padding:0; margin:0; }
footer li { margin-bottom:8px; }
footer a { color:rgba(255,255,255,.7); }
footer a:hover { color:#fff; }
.footer-bottom { border-top:1px solid rgba(255,255,255,.1); margin-top:32px; padding-top:24px; text-align:center; font-size:12px; opacity:.6; }
</style>
</head>
<body>

@php
    $logoUrl = config('brand.logo_url') ?: null;
@endphp
{{-- ═══ NAV ═══ --}}
<nav class="p-nav">
    <div class="p-nav-inner">
        <a href="/" class="p-logo">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $brand }}">
            @else
                mentor<span>de</span>
            @endif
        </a>
        <div class="p-nav-links">
            <a href="#portallar">Portallar</a>
            <a href="#moduller">Modüller</a>
            <a href="#ai">AI Asistan</a>
            <a href="#fiyat">Fiyatlandırma</a>
            <a href="#partner">Partner</a>
        </div>
        <a href="#cta"
           class="p-nav-cta"
           data-track="cta_clicked"
           data-ph-cta-name="nav_demo">
            Demo İste →
        </a>
    </div>
</nav>

{{-- ═══ HERO ═══ --}}
<section class="hero">
    <div class="container hero-grid">
        <div>
            <span class="hero-badge"><span class="dot"></span> Yurt Dışı Eğitim Danışmanlığı SaaS</span>
            <div class="hero-flag-strip" aria-hidden="true">
                <div class="fl-blk"></div><div class="fl-red"></div><div class="fl-gld"></div>
            </div>
            <h1>5 Excel + WhatsApp + Calendar = <em>kaybedilen aday.</em><br>Tek panel, ekibin <em>2× kapasite.</em></h1>
            <p class="hero-lead">
                <strong>{{ $brand }}</strong> — Almanya'ya öğrenci gönderen danışmanlık firmaları için end-to-end bulut platformu.
                Lead'den vizeye, sözleşmeden mezuniyete — bütün süreç tek dashboard'da, ekibin gerçek işine odaklansın.
            </p>
            <div class="hero-ctas">
                <a href="#cta"
                   class="btn-primary"
                   data-track="cta_clicked"
                   data-ph-cta-name="hero_demo"
                   data-ph-location="platform_hero">
                    🎯 Ücretsiz Demo Talebi
                </a>
                <a href="#portallar"
                   class="btn-ghost"
                   data-track="cta_clicked"
                   data-ph-cta-name="hero_explore"
                   data-ph-location="platform_hero">
                    Modülleri İncele
                </a>
            </div>
            <div class="hero-trust">
                <div class="hero-trust-item">
                    <strong>6</strong>
                    Ayrı Portal
                </div>
                <div class="hero-trust-item">
                    <strong>28+</strong>
                    Modül
                </div>
                <div class="hero-trust-item">
                    <strong>AI</strong>
                    Destekli Asistan
                </div>
                <div class="hero-trust-item">
                    <strong>GDPR</strong>
                    Uyumlu
                </div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-card-stack">
                <div class="hero-card hero-card-1" style="padding:28px;">
                    <div class="lbl">Bu Ay Toplam Aday</div>
                    <div class="val">147</div>
                    <div class="delta">↑ %32 önceki aya göre</div>
                    <div style="margin-top:18px; height:80px; background:linear-gradient(180deg, var(--primary-soft), #fff); border-radius:10px; display:flex; align-items:flex-end; gap:4px; padding:8px;">
                        @foreach([35,42,38,55,48,62,67,58,72,68,80,90] as $h)
                        <div style="flex:1; background:var(--gradient-mix); border-radius:3px 3px 0 0; height:{{ $h }}%;"></div>
                        @endforeach
                    </div>
                </div>
                <div class="hero-card hero-card-2">
                    <div class="lbl">💰 Bu Ay Tahsilat</div>
                    <div class="val eur">€48.350</div>
                    <div class="delta">↑ 12 sözleşme</div>
                </div>
                <div class="hero-card hero-card-3">
                    <div class="lbl">🤖 AI Soruları</div>
                    <div class="val">1.247</div>
                    <div class="delta">98% memnuniyet</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ STATS ═══ --}}
<section class="sec-bg-white" style="padding:60px 0;">
    <div class="container">
        <div class="stats-grid">
            <div class="stat">
                <div class="stat-icon">🎯</div>
                <div class="stat-num">6 <span>portal</span></div>
                <div class="stat-lbl">Aday · Öğrenci · Senior · Bayi · Manager · Marketing</div>
            </div>
            <div class="stat">
                <div class="stat-icon">⚡</div>
                <div class="stat-num">28+</div>
                <div class="stat-lbl">Hazır Modül + Genişleyebilir Mimari</div>
            </div>
            <div class="stat">
                <div class="stat-icon">🤖</div>
                <div class="stat-num">AI</div>
                <div class="stat-lbl">Gemini 2.5 Flash + Knowledge Base RAG</div>
            </div>
            <div class="stat">
                <div class="stat-icon">🇪🇺</div>
                <div class="stat-num">GDPR</div>
                <div class="stat-lbl">EU Data Residency + Full Audit Trail</div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ ROI PROOF PANEL ═══ --}}
<section class="sec-bg-soft" style="padding:70px 0;">
    <div class="container">
        <span class="sec-label">Kanıtlanmış Etki</span>
        <h2 class="sec-title">Sayılarla {{ $brand }}.</h2>
        <p class="sec-lead">
            Mevcut müşteri firmalarımızın iç ölçümlerinden alınan gerçek etki rakamları —
            ekibin günlük operasyonu nasıl değişiyor.
        </p>
        <div class="roi-grid">
            <div class="roi-card">
                <div class="roi-num">2×</div>
                <div class="roi-lbl">Senior başına aktif aday kapasitesi</div>
                <div class="roi-sub">30 → 60 paralel takip</div>
            </div>
            <div class="roi-card">
                <div class="roi-num">+%40</div>
                <div class="roi-lbl">Lead conversion artışı</div>
                <div class="roi-sub">Otomatik nurture + AI follow-up ile</div>
            </div>
            <div class="roi-card">
                <div class="roi-num">30 sa</div>
                <div class="roi-lbl">Aylık manuel rapor tasarrufu</div>
                <div class="roi-sub">Excel cehennemine veda</div>
            </div>
            <div class="roi-card">
                <div class="roi-num">%92</div>
                <div class="roi-lbl">Aday belge tamamlanma oranı</div>
                <div class="roi-sub">Otomatik reminder + sessizlik check-in</div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ PROBLEM/SOLUTION ═══ --}}
<section class="sec-bg-white">
    <div class="container">
        <span class="sec-label">Sorun & Çözüm</span>
        <h2 class="sec-title">Excel'lerden, dağınık WhatsApp gruplarından, eksik takipten kurtul.</h2>
        <p class="sec-lead">
            Yurt dışı eğitim danışmanlığı firmaları binlerce aday, yüzlerce belge, onlarca üniversite ve karmaşık vize süreçleriyle savaşırken hâlâ excel ve email üzerinde yönetim yapıyor. Sonuç: kaybolan adaylar, gecikmiş başvurular, ölçülemeyen performans.
        </p>
        <div class="compare-grid">
            <div class="compare-card bad">
                <h3>❌ Geleneksel Yaklaşım</h3>
                <ul>
                    <li>5+ farklı araç (Excel + Email + WhatsApp + Drive + Trello)</li>
                    <li>Aday süreci karaya oturduğunda kimsenin haberi olmuyor</li>
                    <li>Senior performans ölçümü öznel, KPI yok</li>
                    <li>Belgeler farklı yerlerde, deadline'lar kaçıyor</li>
                    <li>Vize/üniversite başvuru durumu manuel takip</li>
                    <li>Bayi komisyonu hesaplama excel cehennem</li>
                    <li>Ödeme takibi muhasebeyi çıldırtıyor</li>
                    <li>Pazarlama harcaması nereye gidiyor belirsiz</li>
                </ul>
            </div>
            <div class="compare-card good">
                <h3>✓ {{ $brand }} ile</h3>
                <ul>
                    <li>Tek panel — 6 portal, tüm ekip aynı veride</li>
                    <li>Aday lifecycle otomatik takip + dormant alarm</li>
                    <li>Senior KPI dashboard + danışman performans skoru</li>
                    <li>Belgeler entegre, deadline reminder otomatik</li>
                    <li>Vize/uni süreç kanban + status timeline</li>
                    <li>Bayi komisyonu otomatik hesap + payout</li>
                    <li>Stripe entegrasyonu + invoice/fatura akışı</li>
                    <li>UTM tracking + multi-touch attribution + ROI</li>
                </ul>
            </div>
        </div>

        {{-- Detaylı karşılaştırma matrisi --}}
        <div class="vs-matrix-wrap">
            <h3 class="vs-matrix-title">"Generic CRM kullansam aynı şey değil mi?" — Hayır.</h3>
            <p class="vs-matrix-sub">Eğitim danışmanlığı domain'ine özel akışlar (vize, Anmeldung, üniversite başvuru, bayi attribution) generic SaaS'larda yok. {{ $brand }} bu sürecin <strong>tamamını</strong> kapsar.</p>
            <div class="vs-matrix-scroll">
                <table class="vs-matrix">
                    <thead>
                        <tr>
                            <th>Operasyonel İhtiyaç</th>
                            <th class="col-bad">Excel + WhatsApp</th>
                            <th class="col-mid">Generic CRM<br><span>(HubSpot, Pipedrive vb.)</span></th>
                            <th class="col-good">{{ $brand }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Aday → Öğrenci dönüşüm akışı</td>
                            <td class="col-bad">Manuel takip</td>
                            <td class="col-mid">Lead → Deal mantığı (zorla)</td>
                            <td class="col-good">Native: aday/öğrenci ayrı portal</td>
                        </tr>
                        <tr>
                            <td>Üniversite başvuru pipeline + DAAD entegrasyonu</td>
                            <td class="col-bad">❌ Yok</td>
                            <td class="col-mid">❌ Yok</td>
                            <td class="col-good">✓ 500+ üni + deadline tracker</td>
                        </tr>
                        <tr>
                            <td>Vize süreci (Sperrkonto · randevu · konsolosluk)</td>
                            <td class="col-bad">❌ Yok</td>
                            <td class="col-mid">❌ Yok</td>
                            <td class="col-good">✓ Checklist + status timeline</td>
                        </tr>
                        <tr>
                            <td>Bayi/Partner komisyon + payout</td>
                            <td class="col-bad">Excel formülleri</td>
                            <td class="col-mid">Custom field workaround</td>
                            <td class="col-good">✓ Native dealer modülü + portal</td>
                        </tr>
                        <tr>
                            <td>AI knowledge base + adaya RAG asistan</td>
                            <td class="col-bad">❌ Yok</td>
                            <td class="col-mid">Eklenti gerekir (extra cost)</td>
                            <td class="col-good">✓ AI Labs dahil + intent analizi</td>
                        </tr>
                        <tr>
                            <td>Sözleşme dijital imza + ödeme planı</td>
                            <td class="col-bad">DocuSign + manuel takip</td>
                            <td class="col-mid">Eklenti</td>
                            <td class="col-good">✓ Native + sözleşme PDF + Stripe</td>
                        </tr>
                        <tr>
                            <td>EU Data Residency + DSGVO/KVKK uyumu</td>
                            <td class="col-bad">Sorumluluk sende</td>
                            <td class="col-mid">Genelde ABD veri merkezi</td>
                            <td class="col-good">✓ Almanya hosting + AVV registry</td>
                        </tr>
                        <tr>
                            <td>Multi-portal (aday/öğrenci/senior/bayi/yönetici)</td>
                            <td class="col-bad">❌ Yok</td>
                            <td class="col-mid">Tek arayüz, role-based gizleme</td>
                            <td class="col-good">✓ 6 ayrı UX, izole portal</td>
                        </tr>
                        <tr>
                            <td>Türkçe arayüz + yerelleştirilmiş süreçler</td>
                            <td class="col-bad">—</td>
                            <td class="col-mid">İngilizce ağırlıklı</td>
                            <td class="col-good">✓ Tam Türkçe + Almanya odaklı</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- ═══ 6 PORTAL ═══ --}}
<section id="portallar" class="sec-bg-white">
    <div class="container">
        <span class="sec-label">6 Portal Mimarisi</span>
        <h2 class="sec-title">Her rol için optimize edilmiş ayrı çalışma alanları.</h2>
        <p class="sec-lead">Aday, öğrenci, senior, bayi, yönetici, pazarlama — her biri kendi UX'ine sahip ama tek veriden besleniyor. Permission-based erişim + multi-tenant izolasyon.</p>

        <div class="portals-grid">
            <div class="portal">
                <div class="portal-icon-wrap guest">🙋</div>
                <div class="portal-name">Portal 1</div>
                <h3>Aday Öğrenci</h3>
                <p>Türkiye'den başvuran adayların ilk dokunuşu — başvuru formu, belge yükleme, randevu, soru-cevap.</p>
                <ul class="portal-features">
                    <li>Self-service başvuru formu + KVKK</li>
                    <li>Belge yükleme + AI analizi</li>
                    <li>Senior randevusu + Google Calendar</li>
                    <li>AI Asistan (vize/üniversite soruları)</li>
                    <li>Lead score otomatik hesap</li>
                </ul>
            </div>

            <div class="portal">
                <div class="portal-icon-wrap student">🎓</div>
                <div class="portal-name">Portal 2</div>
                <h3>Öğrenci</h3>
                <p>Sözleşme imzalamış aktif öğrenciler — Almanya'ya geçiş sürecinin tüm adımları.</p>
                <ul class="portal-features">
                    <li>Belge takip (Sperrkonto, sigorta, vize)</li>
                    <li>Üniversite başvuru pipeline</li>
                    <li>Konaklama (Wohnung) rehberi</li>
                    <li>Mesajlaşma (DM + WhatsApp sync)</li>
                    <li>Ödeme planı + invoice geçmişi</li>
                </ul>
            </div>

            <div class="portal">
                <div class="portal-icon-wrap senior">👨‍🏫</div>
                <div class="portal-name">Portal 3</div>
                <h3>Senior Danışman</h3>
                <p>Aday-öğrenci ilişkisini yöneten danışman ekibi — pipeline + müsaitlik + iletişim hub.</p>
                <ul class="portal-features">
                    <li>Atanan adaylar + lead pipeline kanban</li>
                    <li>Müsaitlik takvimi (haftalık + away)</li>
                    <li>Booking yönetimi (otomatik confirm)</li>
                    <li>Hızlı aksiyon (📞 ara, 💬 WhatsApp, 📧 mail)</li>
                    <li>Performans dashboard + KPI</li>
                </ul>
            </div>

            <div class="portal">
                <div class="portal-icon-wrap dealer">🤝</div>
                <div class="portal-name">Portal 4</div>
                <h3>Bayi (Dealer)</h3>
                <p>Aday yönlendiren satış ortakları için şeffaf takip + komisyon yönetimi.</p>
                <ul class="portal-features">
                    <li>Yönlendirilen aday süreç takibi</li>
                    <li>Şeffaf komisyon ekranı (kademe)</li>
                    <li>Pazarlama materyali kütüphanesi</li>
                    <li>UTM tracking link generator</li>
                    <li>Otomatik payout request</li>
                </ul>
            </div>

            <div class="portal">
                <div class="portal-icon-wrap marketing">📣</div>
                <div class="portal-name">Portal 5</div>
                <h3>Marketing Admin</h3>
                <p>Pazarlama ve satış ekibi için kampanya yönetimi + lead funnel analytics.</p>
                <ul class="portal-features">
                    <li>Kampanya CRUD + bütçe takibi</li>
                    <li>Lead pipeline (kanban + drag-drop)</li>
                    <li>Multi-touch attribution</li>
                    <li>Email/SMS drip otomasyonu</li>
                    <li>UTM tracking link analytics</li>
                </ul>
            </div>

            <div class="portal">
                <div class="portal-icon-wrap manager">👔</div>
                <div class="portal-name">Portal 6</div>
                <h3>Yönetici (Manager)</h3>
                <p>Tüm operasyonun komuta merkezi — analytics, finans, HR, ayarlar, AI Labs.</p>
                <ul class="portal-features">
                    <li>16+ analytics dashboard (BI ready)</li>
                    <li>Finans (Stripe + invoice + payout)</li>
                    <li>İK (personel + izin + bordro)</li>
                    <li>AI Labs ayarları + intent intelligence</li>
                    <li>GDPR + audit + güvenlik kontrol</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ═══ MODULES === --}}
<section id="moduller" class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">28+ Hazır Modül</span>
        <h2 class="sec-title">Eğitim danışmanlığının her iş süreci için modül.</h2>
        <p class="sec-lead">Hiçbir modülü baştan yazmana gerek yok — hepsi kutudan çıkar çıkmaz çalışıyor.</p>

        {{-- ── Vurgu Modülleri (flagship 8) ────────────────────── --}}
        <div class="hl-section-tag">★ Flagship Modüller</div>
        <div class="hl-modules-grid">
            <div class="hl-module">
                <div class="hl-module-icon">👥</div>
                <h4>CRM &amp; Lead Pipeline</h4>
                <p>5-tier scoring · otomatik atama · dormant alarm · multi-touch attribution. Eğitim danışmanlığına özel akışlar — generic CRM'de bulamazsın.</p>
            </div>
            <div class="hl-module">
                <div class="hl-module-icon">📜</div>
                <h4>Contracts Hub</h4>
                <p>Sözleşme şablonları · dijital imza · ödeme planı · ek madde versiyonu · iptal akışı. Aday → öğrenci geçişinin yasal kalbi.</p>
            </div>
            <div class="hl-module">
                <div class="hl-module-icon">📄</div>
                <h4>Document Pipeline</h4>
                <p>Belge upload · OCR · kategori · deadline tracker · e-imza altyapı · public talep linki. Sperrkonto'dan diplomaya 50+ belge tipi.</p>
            </div>
            <div class="hl-module">
                <div class="hl-module-icon">📅</div>
                <h4>Booking &amp; Calendar</h4>
                <p>Senior takvimi · public booking widget · Google Calendar 2-way sync · otomatik reminder · ücretli/ücretsiz seans yönetimi.</p>
            </div>
            <div class="hl-module">
                <div class="hl-module-icon">🤖</div>
                <h4>AI Labs (Diferansiyatör)</h4>
                <p>Gemini 2.5 + RAG knowledge base · intent analizi · FAQ önerileri · streaming yanıt · token guard. Aday soruları otomatik öğrenir.</p>
            </div>
            <div class="hl-module" style="border-left:3px solid var(--primary);">
                <div class="hl-module-icon">🎯</div>
                <h4>UniMatch — Akıllı Program Bulucu <span style="font-size:10px; background:var(--primary); color:#fff; padding:2px 6px; border-radius:4px; vertical-align:middle; margin-left:4px;">YENİ</span></h4>
                <p>13.000+ Almanya programı canonical katalog · 19 adımlık UniMatch wizard · 9-faktör akıllı öneri motoru · TR↔EN otomatik çeviri (Gemini). Aday tek tıkla en uygun 5 programı görür — danışman ön elemeyi atlar.</p>
                <a href="{{ route('uni-match.landing') }}"
                   target="_blank"
                   style="display:inline-block; margin-top:10px; font-size:12px; color:var(--primary); font-weight:600; text-decoration:none; border-bottom:1px solid var(--primary);"
                   data-track="cta_clicked"
                   data-ph-cta-name="modules_unimatch_demo"
                   data-ph-location="platform_modules">
                    → Canlı Demo: UniMatch'ı Dene
                </a>
            </div>
            <div class="hl-module">
                <div class="hl-module-icon">💳</div>
                <h4>Payments &amp; Invoicing</h4>
                <p>Stripe checkout · fatura · taksitli plan · overdue takibi · dealer payout · ödeme hatırlatma akışı (4+1 kademeli mail).</p>
            </div>
            <div class="hl-module">
                <div class="hl-module-icon">📈</div>
                <h4>Marketing Attribution</h4>
                <p>UTM tracking · multi-touch · kanal ROI · lead quality score · A/B test · email/SMS drip · referral programı.</p>
            </div>
            <div class="hl-module">
                <div class="hl-module-icon">📊</div>
                <h4>Analytics Hub (16+ Dashboard)</h4>
                <p>Lead funnel · senior performans · revenue · NPS · GDPR · kampanya ROI · scheduled snapshots. BI-ready, export edilebilir.</p>
            </div>
        </div>

        {{-- ── Tamamlayıcı Modüller (kompakt pill) ────────────── --}}
        <div class="hl-section-tag" style="margin-top:60px;">+ Tamamlayıcı Modüller (19)</div>
        <p class="hl-section-sub">Çekirdek operasyonu tamamlayan iş akışı modülleri — hepsi dahil, ayrıca yapılandırma gerekmez.</p>
        <div class="modules-pills">
            <div class="m-pill"><span class="m-pill-icon">🏛️</span><span><strong>Üniversite Belge Haritası</strong> · 500+ üni + DAAD</span></div>
            <div class="m-pill"><span class="m-pill-icon">🛂</span><span><strong>Vize Süreci</strong> · randevu + checklist</span></div>
            <div class="m-pill"><span class="m-pill-icon">🏠</span><span><strong>Konaklama</strong> · Wohnung + Anmeldung</span></div>
            <div class="m-pill"><span class="m-pill-icon">💬</span><span><strong>Messaging Hub</strong> · DM + Email + WhatsApp</span></div>
            <div class="m-pill"><span class="m-pill-icon">🎫</span><span><strong>Ticket System</strong> · SLA + auto-assign</span></div>
            <div class="m-pill"><span class="m-pill-icon">📢</span><span><strong>Bulletin Board</strong> · iç duyuru</span></div>
            <div class="m-pill"><span class="m-pill-icon">📧</span><span><strong>Email Campaigns</strong> · A/B + drip</span></div>
            <div class="m-pill"><span class="m-pill-icon">📱</span><span><strong>Sosyal Medya</strong> · içerik takvimi</span></div>
            <div class="m-pill"><span class="m-pill-icon">🤝</span><span><strong>Dealer Network</strong> · komisyon + payout</span></div>
            <div class="m-pill"><span class="m-pill-icon">👔</span><span><strong>İnsan Kaynakları</strong> · personel + izin</span></div>
            <div class="m-pill"><span class="m-pill-icon">📋</span><span><strong>Görev Yönetimi</strong> · kanban + SLA</span></div>
            <div class="m-pill"><span class="m-pill-icon">📑</span><span><strong>Workflow Engine</strong> · kural tabanlı</span></div>
            <div class="m-pill"><span class="m-pill-icon">🔒</span><span><strong>GDPR &amp; Audit</strong> · export + retention</span></div>
            <div class="m-pill"><span class="m-pill-icon">🛡️</span><span><strong>Security</strong> · 2FA + anomaly detect</span></div>
            <div class="m-pill"><span class="m-pill-icon">💱</span><span><strong>Currency Sync</strong> · EUR/TRY/USD canlı</span></div>
            <div class="m-pill"><span class="m-pill-icon">📝</span><span><strong>Audit Reports</strong> · aylık snapshot</span></div>
            <div class="m-pill"><span class="m-pill-icon">📍</span><span><strong>Sessizlik Monitörü</strong> · auto check-in</span></div>
            <div class="m-pill"><span class="m-pill-icon">🎟️</span><span><strong>İndirim Kodları</strong> · 5 template + AI</span></div>
            <div class="m-pill"><span class="m-pill-icon">⏰</span><span><strong>Ödeme Hatırlatma</strong> · 4+1 kademeli</span></div>
        </div>
    </div>
</section>

{{-- ═══ AI HIGHLIGHT ═══ --}}
<section id="ai" class="sec-bg-white">
    <div class="container">
        <div class="ai-spotlight">
            <div class="ai-grid">
                <div>
                    <span class="sec-label">AI Labs · Diferansiyatör</span>
                    <h2>Aday hangi soruyu sorduysa, sen <em>yanıtını otomatik</em> öğrenirsin.</h2>
                    <p>Adaylar AI asistanına "Sperrkonto için hangi banka önerirsin?" diye sorar. Sen bu soruları aylık olarak görür, onları FAQ'e dönüştürür, lead scoring'i bu pattern'lerle besleyebilirsin.</p>

                    <div class="ai-features">
                        <div class="ai-feature">
                            <div class="lbl">Knowledge Base</div>
                            <div class="val">PDF + URL + Metin</div>
                        </div>
                        <div class="ai-feature">
                            <div class="lbl">Model</div>
                            <div class="val">Gemini 2.5 Flash</div>
                        </div>
                        <div class="ai-feature">
                            <div class="lbl">Mod</div>
                            <div class="val">RAG + External</div>
                        </div>
                        <div class="ai-feature">
                            <div class="lbl">Intent Analiz</div>
                            <div class="val">Top sorular + FAQ adayı</div>
                        </div>
                        <div class="ai-feature">
                            <div class="lbl">Streaming</div>
                            <div class="val">SSE — anında cevap</div>
                        </div>
                        <div class="ai-feature">
                            <div class="lbl">Limitler</div>
                            <div class="val">Daily quota + cost track</div>
                        </div>
                    </div>

                    <a href="#cta" class="btn-gold"
                       data-track="cta_clicked"
                       data-ph-cta-name="ai_demo">
                        🤖 AI Asistan Demosu
                    </a>
                </div>

                <div class="ai-mockup">
                    <div style="font-size:11px; opacity:.7; margin-bottom:14px;">💬 ai-asistan.{{ $brand }}.com</div>

                    <div class="ai-msg">
                        <strong>Aday:</strong> Münih'te yüksek lisans için Sperrkonto miktarı 2026'da değişti mi?
                    </div>
                    <div class="ai-reply">
                        <span class="badge">📚 KAYNAK</span><br>
                        Evet, 2026 başında <strong>€11.904</strong>'e güncellendi (önceki yıl €11.208). Detaylı bilgi DAAD ve federal yönetmelikten teyitli (Source: KB-2026-04).
                    </div>

                    <div style="margin-top:14px; padding-top:14px; border-top:1px solid rgba(255,255,255,.1); font-size:11px; opacity:.6;">
                        🎯 Bu soru bu hafta <strong>14 kez</strong> soruldu — FAQ adayı olarak işaretlendi.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ WORKFLOW === --}}
<section class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">Aday → Mezun Yolculuğu</span>
        <h2 class="sec-title">Tek bir akış. Sıfır manuel takip. Tüm aşamalar otomatik.</h2>
        <p class="sec-lead">Adayın senin sistemine girmesinden Almanya'da diploma almasına kadar. Her adımda PostHog event + audit trail + analytics.</p>

        <div class="workflow">
            <div class="flow-steps">
                <div class="flow-step">
                    <div class="flow-num">1</div>
                    <div class="flow-icon">📝</div>
                    <h4>Aday Başvuru</h4>
                    <p>Reklam → form → otomatik lead score</p>
                    <span class="meta">Score: 0-25</span>
                </div>
                <div class="flow-step">
                    <div class="flow-num">2</div>
                    <div class="flow-icon">👥</div>
                    <h4>Senior Atama</h4>
                    <p>Auto-assign veya manuel</p>
                    <span class="meta">Tier: Cold/Warm</span>
                </div>
                <div class="flow-step">
                    <div class="flow-num">3</div>
                    <div class="flow-icon">📞</div>
                    <h4>İlk Görüşme</h4>
                    <p>Booking + AI brief + script</p>
                    <span class="meta">Tier: Hot</span>
                </div>
                <div class="flow-step">
                    <div class="flow-num">4</div>
                    <div class="flow-icon">📜</div>
                    <h4>Sözleşme</h4>
                    <p>Dijital imza + Stripe ödeme</p>
                    <span class="meta">Conversion ✓</span>
                </div>
                <div class="flow-step">
                    <div class="flow-num">5</div>
                    <div class="flow-icon">📄</div>
                    <h4>Belge Süreci</h4>
                    <p>Upload + checklist + deadline</p>
                    <span class="meta">DOC Pipeline</span>
                </div>
                <div class="flow-step">
                    <div class="flow-num">6</div>
                    <div class="flow-icon">🛂</div>
                    <h4>Vize</h4>
                    <p>Konsolosluk + dosya kontrol</p>
                    <span class="meta">Status track</span>
                </div>
                <div class="flow-step">
                    <div class="flow-num">7</div>
                    <div class="flow-icon">🎓</div>
                    <h4>Almanya'da</h4>
                    <p>Anmeldung + Wohnung + sigorta</p>
                    <span class="meta">Active student</span>
                </div>
                <div class="flow-step">
                    <div class="flow-num">8</div>
                    <div class="flow-icon">🏆</div>
                    <h4>Mezuniyet</h4>
                    <p>Referral + champion + alumni</p>
                    <span class="meta">Lifetime value</span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ ANALYTICS DASHBOARD === --}}
<section class="sec-bg-white">
    <div class="container">
        <span class="sec-label">16+ Hazır Dashboard</span>
        <h2 class="sec-title">İşinin her metriği — tek bakışta.</h2>
        <p class="sec-lead">Lead funnel, senior perf, revenue, NPS, AI usage, dealer health, kampanya ROI. PostHog + Metabase entegrasyonlu BI-ready altyapı.</p>

        <div class="dash-frame">
            <div style="display:flex; justify-content:space-between; margin-bottom:18px; align-items:center;">
                <div style="font-weight:800; color:var(--primary-deep); font-size:18px;">📊 Manager Dashboard</div>
                <div style="display:flex; gap:6px;">
                    @foreach(['7G','30G','90G','1Y'] as $i => $p)
                    <span style="padding:4px 12px; background:{{ $i === 1 ? 'var(--primary)' : '#f1f5f9' }}; color:{{ $i === 1 ? '#fff' : 'var(--muted)' }}; border-radius:8px; font-size:11px; font-weight:700;">{{ $p }}</span>
                    @endforeach
                </div>
            </div>

            <div class="dash-grid">
                <div class="dash-kpi">
                    <div class="lbl">Yeni Aday</div>
                    <div class="val">147</div>
                    <div class="delta">↑ %32</div>
                </div>
                <div class="dash-kpi">
                    <div class="lbl">Conversion Rate</div>
                    <div class="val">18.4%</div>
                    <div class="delta">↑ %4</div>
                </div>
                <div class="dash-kpi">
                    <div class="lbl">MRR (€)</div>
                    <div class="val" style="color:var(--success);">48.350</div>
                    <div class="delta">↑ %12</div>
                </div>
                <div class="dash-kpi">
                    <div class="lbl">NPS</div>
                    <div class="val">+72</div>
                    <div class="delta">↑ +8</div>
                </div>
            </div>

            <div class="dash-chart">
                @foreach([22,38,28,42,55,48,62,58,72,68,80,90,75,88,95,82,98] as $h)
                <div class="dash-bar" style="height:{{ $h }}%;"></div>
                @endforeach
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:8px; font-size:10px; color:var(--muted);">
                <span>17 gün önce</span>
                <span>bugün</span>
            </div>
        </div>
    </div>
</section>

{{-- ═══ INTEGRATIONS === --}}
<section class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">Entegrasyonlar</span>
        <h2 class="sec-title">Kullandığın araçlarla tak-çalıştır.</h2>
        <p class="sec-lead">Webhook + REST API + native entegrasyonlar — kurulum 1-2 dakika.</p>

        <div class="integ-grid">
            <div class="integ"><div class="integ-icon">📅</div><div class="integ-name">Google Calendar</div></div>
            <div class="integ"><div class="integ-icon">💳</div><div class="integ-name">Stripe</div></div>
            <div class="integ"><div class="integ-icon">🤖</div><div class="integ-name">Gemini AI</div></div>
            <div class="integ"><div class="integ-icon">📧</div><div class="integ-name">Resend</div></div>
            <div class="integ"><div class="integ-icon">💬</div><div class="integ-name">WhatsApp</div></div>
            <div class="integ"><div class="integ-icon">📊</div><div class="integ-name">PostHog</div></div>
            <div class="integ"><div class="integ-icon">🎬</div><div class="integ-name">Giphy</div></div>
            <div class="integ"><div class="integ-icon">🔔</div><div class="integ-name">FCM Push</div></div>
            <div class="integ"><div class="integ-icon">🌐</div><div class="integ-name">Google OAuth</div></div>
            <div class="integ"><div class="integ-icon">📈</div><div class="integ-name">Metabase</div></div>
            <div class="integ"><div class="integ-icon">📄</div><div class="integ-name">PDF.js</div></div>
            <div class="integ"><div class="integ-icon">⚙️</div><div class="integ-name">Webhook API</div></div>
        </div>
    </div>
</section>

{{-- ═══ SECURITY === --}}
<section class="sec-bg-white">
    <div class="container">
        <span class="sec-label">Güvenlik & Uyum</span>
        <h2 class="sec-title">Almanya/EU regülasyonlarına %100 uyumlu.</h2>
        <p class="sec-lead">GDPR, KVKK, EU data residency, SOC 2 hazırlık. Müşteri verisi için tasarlanmış sıfır-trust mimari.</p>

        <div class="sec-grid">
            <div class="sec-card">
                <div class="sec-card-icon">🇪🇺</div>
                <div>
                    <h4>GDPR + KVKK Uyumlu</h4>
                    <p>Right-to-access, right-to-erasure, data portability, retention policy. Tüm akış doc + API.</p>
                </div>
            </div>
            <div class="sec-card">
                <div class="sec-card-icon">🔒</div>
                <div>
                    <h4>Audit Trail</h4>
                    <p>Her CRUD işlem loglanır — kim, ne zaman, nereden, hangi alanı değiştirdi. 90 gün arşiv + cold storage.</p>
                </div>
            </div>
            <div class="sec-card">
                <div class="sec-card-icon">🛡️</div>
                <div>
                    <h4>2FA + RBAC</h4>
                    <p>İki faktörlü auth, role-based access (15+ rol), permission matrix, IP allowlist desteği.</p>
                </div>
            </div>
            <div class="sec-card">
                <div class="sec-card-icon">⚠️</div>
                <div>
                    <h4>Anomaly Detection</h4>
                    <p>Gece yarısı toplu silme, beklenmedik IP, 5+ failed login → otomatik alert + lockdown.</p>
                </div>
            </div>
            <div class="sec-card">
                <div class="sec-card-icon">🔐</div>
                <div>
                    <h4>Encryption</h4>
                    <p>HTTPS-only, encrypted cookies, hashed PII (email/phone), bcrypt password, signed URLs.</p>
                </div>
            </div>
            <div class="sec-card">
                <div class="sec-card-icon">📦</div>
                <div>
                    <h4>Data Backup</h4>
                    <p>Günlük full backup, 30 gün retention, point-in-time recovery, cross-region replication.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ TIER PRICING — 3 paket ═══ --}}
<section id="paketler" class="sec-bg-white" style="padding:80px 0 50px;">
    <div class="container">
        <span class="sec-label">Hızlı Paketler</span>
        <h2 class="sec-title">Ekibinin boyutuna göre paket seç.</h2>
        <p class="sec-lead">
            Üç hazır paket — solo danışmandan kurumsal ekibe kadar. Karar veremiyorsan
            <a href="#fiyat" style="color:var(--primary); border-bottom:1px solid var(--primary);">modüler hesabı</a>
            ile kendi paketini oluştur.
        </p>

        <div class="tier-grid">
            {{-- SOLO --}}
            <div class="tier-card">
                <div class="tier-header">
                    <div class="tier-icon">👤</div>
                    <h3 class="tier-name">Solo</h3>
                    <p class="tier-tag">1–3 kişilik ekip · ≤100 aday/yıl</p>
                </div>
                <div class="tier-price">
                    <span class="tier-num">€199</span>
                    <span class="tier-per">/ay</span>
                </div>
                <div class="tier-yearly">veya €1.910/yıl <small>(%20 indirim)</small></div>
                <ul class="tier-features">
                    <li>3 portal (Aday + Öğrenci + Senior)</li>
                    <li>Lead pipeline + temel skoring</li>
                    <li>Belge yönetimi + reminder</li>
                    <li>50 aktif öğrenci · 5 senior</li>
                    <li>Email destek (24h SLA)</li>
                    <li class="tier-disabled">— AI Asistan (ek modül)</li>
                    <li class="tier-disabled">— Bayi network</li>
                </ul>
                <a href="#cta" class="tier-cta">Solo'yu Seç →</a>
            </div>

            {{-- BÜYÜYEN — featured --}}
            <div class="tier-card featured">
                <div class="tier-tag-pop">⭐ En Popüler</div>
                <div class="tier-header">
                    <div class="tier-icon">📈</div>
                    <h3 class="tier-name">Büyüyen Ekip</h3>
                    <p class="tier-tag">3–10 kişi · 100–500 aday/yıl</p>
                </div>
                <div class="tier-price">
                    <span class="tier-num">€499</span>
                    <span class="tier-per">/ay</span>
                </div>
                <div class="tier-yearly">veya €4.790/yıl <small>(%20 indirim)</small></div>
                <ul class="tier-features">
                    <li><strong>Solo'daki her şey +</strong></li>
                    <li>🤖 AI Asistan dahil (Gemini RAG)</li>
                    <li>📣 Marketing Admin modülü</li>
                    <li>📜 Contracts Hub + dijital imza</li>
                    <li>250 aktif öğrenci · 15 senior · 3 manager</li>
                    <li>Analytics Hub (16+ dashboard)</li>
                    <li>Öncelikli destek (4h SLA)</li>
                </ul>
                <a href="#cta" class="tier-cta">Büyüyen'i Seç →</a>
            </div>

            {{-- KURUMSAL --}}
            <div class="tier-card">
                <div class="tier-header">
                    <div class="tier-icon">🏢</div>
                    <h3 class="tier-name">Kurumsal</h3>
                    <p class="tier-tag">10+ kişi · 500+ aday/yıl · bayi ağı</p>
                </div>
                <div class="tier-price">
                    <span class="tier-num">€999</span>
                    <span class="tier-per">/ay</span>
                </div>
                <div class="tier-yearly">veya €9.590/yıl <small>(%20 indirim)</small></div>
                <ul class="tier-features">
                    <li><strong>Büyüyen'deki her şey +</strong></li>
                    <li>🤝 Dealer Network modülü + komisyon</li>
                    <li>🌐 White-label + custom domain</li>
                    <li>📦 DAM (Dijital Varlık Yönetimi)</li>
                    <li>Sınırsız öğrenci · sınırsız senior</li>
                    <li>API erişim + webhook</li>
                    <li>Dedicated success manager (1h SLA)</li>
                </ul>
                <a href="#cta" class="tier-cta">Kurumsal'ı Seç →</a>
            </div>
        </div>

        <div class="tier-trust">
            <span>✓ 14 gün ücretsiz deneme</span>
            <span>✓ Kredi kartı gerekmez</span>
            <span>✓ İstediğin zaman iptal</span>
            <span>✓ Veri taşıma desteği dahil</span>
        </div>
    </div>
</section>

{{-- ═══ PRICING — MODULAR === --}}
<section id="fiyat" class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">Modüler Hesap</span>
        <h2 class="sec-title">Veya kendi paketini oluştur.</h2>
        <p class="sec-lead">
            Core plan zorunlu — sonrasında 11 add-on modülden ihtiyacın olanları aktif et.
            Anlık fiyat hesabı, esnek ölçekleme, 14 gün ücretsiz deneme.
        </p>

        <div class="modular-pricing-wrap">
            {{-- ──── CORE PLAN ──── --}}
            <div class="core-plan">
                <div class="core-plan-header">
                    <div>
                        <span class="core-badge">⚡ ZORUNLU</span>
                        <h3 class="core-name">Core Platform</h3>
                        <p class="core-desc">3 portal · Lead pipeline · Belge yönetimi · Bildirim · Temel raporlar</p>
                    </div>
                    <div class="core-price-block">
                        <div class="core-price-row">
                            <span class="core-price">€199</span>
                            <span class="core-period">/ay</span>
                        </div>
                        <div class="core-price-yearly">veya €1.910/yıl <small>(%20 indirim)</small></div>
                    </div>
                </div>
                <div class="core-features">
                    <div class="core-feat">✓ Aday + Öğrenci + Senior portal</div>
                    <div class="core-feat">✓ Lead pipeline + temel skoring</div>
                    <div class="core-feat">✓ Belge yönetimi (yükleme + kategori)</div>
                    <div class="core-feat">✓ Bildirim sistemi (in-app + email)</div>
                    <div class="core-feat">✓ Temel dashboard + raporlar</div>
                    <div class="core-feat">✓ 50 aktif öğrenci · 5 senior · 1 manager</div>
                    <div class="core-feat">✓ Email destek (24h SLA)</div>
                    <div class="core-feat">✓ GDPR + audit trail</div>
                    <div class="core-feat">✓ 2FA + Google OAuth giriş</div>
                </div>
            </div>

            {{-- ──── 11 ADD-ON MODULES ──── --}}
            <div class="addons-header">
                <div>
                    <h3 style="margin:0 0 6px; font-size:22px; color:var(--primary-deep);">📦 İsteğe Bağlı Add-on Modüller (11)</h3>
                    <p style="margin:0; color:var(--muted); font-size:14px;">Açıp kapatabilirsin — değişiklik anında geçerli olur. <strong>Tümünü açarsan %15 paket indirimi.</strong></p>
                </div>
                <div class="addons-toggle-all">
                    <label class="addon-switch">
                        <input type="checkbox" id="select-all-addons">
                        <span class="addon-switch-label">Tümünü Seç</span>
                    </label>
                </div>
            </div>

            <div class="addons-grid">
                {{-- AI ENTEGRASYON --}}
                <div class="addon-card" data-addon="ai" data-price="149">
                    <div class="addon-header">
                        <div class="addon-icon" style="background:linear-gradient(140deg, #8b5cf6, #5b21b6);">🤖</div>
                        <label class="addon-switch">
                            <input type="checkbox" data-addon-toggle="ai">
                            <span class="addon-slider"></span>
                        </label>
                    </div>
                    <h4>AI Entegrasyon</h4>
                    <div class="addon-price">€149<span>/ay</span></div>
                    <p>Gemini AI asistan + knowledge base RAG + intent analiz + FAQ generator. Adayların sorduğu soruları otomatik öğren.</p>
                    <ul class="addon-features">
                        <li>Multi-source (PDF/URL/metin)</li>
                        <li>Streaming yanıt (SSE)</li>
                        <li>Intent + FAQ aday analizi</li>
                        <li>Token cost tracking</li>
                        <li>Daily limit + safety filter</li>
                    </ul>
                </div>

                {{-- DAM --}}
                <div class="addon-card" data-addon="dam" data-price="99">
                    <div class="addon-header">
                        <div class="addon-icon" style="background:linear-gradient(140deg, #06b6d4, #0e7490);">📦</div>
                        <label class="addon-switch">
                            <input type="checkbox" data-addon-toggle="dam">
                            <span class="addon-slider"></span>
                        </label>
                    </div>
                    <h4>DAM — Dijital Varlık Yönetimi</h4>
                    <div class="addon-price">€99<span>/ay</span></div>
                    <p>Belge, görsel, video kütüphanesi. Versiyon kontrolü, kategori, paylaşım izinleri, full-text arama.</p>
                    <ul class="addon-features">
                        <li>500 GB storage (genişlenebilir)</li>
                        <li>Versiyon geçmişi + restore</li>
                        <li>Klasör yapısı + etiket</li>
                        <li>Aktivite logu (kim indirdi/açtı)</li>
                        <li>Public/private paylaşım linki</li>
                    </ul>
                </div>

                {{-- MARKETING ADMIN --}}
                <div class="addon-card" data-addon="marketing" data-price="199">
                    <div class="addon-header">
                        <div class="addon-icon" style="background:linear-gradient(140deg, #e8b931, #c99c26);">📣</div>
                        <label class="addon-switch">
                            <input type="checkbox" data-addon-toggle="marketing">
                            <span class="addon-slider"></span>
                        </label>
                    </div>
                    <h4>Marketing Admin Modülü</h4>
                    <div class="addon-price">€199<span>/ay</span></div>
                    <p>Marketing portal — kampanya yönetimi, lead pipeline kanban, multi-touch attribution, email/SMS drip.</p>
                    <ul class="addon-features">
                        <li>Kampanya CRUD + bütçe</li>
                        <li>Pipeline kanban (drag-drop)</li>
                        <li>UTM tracking + attribution</li>
                        <li>Email/SMS otomasyon</li>
                        <li>Loss + re-engagement raporları</li>
                    </ul>
                </div>

                {{-- DEALER --}}
                <div class="addon-card" data-addon="dealer" data-price="149">
                    <div class="addon-header">
                        <div class="addon-icon" style="background:linear-gradient(140deg, #16a34a, #14532d);">🤝</div>
                        <label class="addon-switch">
                            <input type="checkbox" data-addon-toggle="dealer">
                            <span class="addon-slider"></span>
                        </label>
                    </div>
                    <h4>Dealer Network Modülü</h4>
                    <div class="addon-price">€149<span>/ay</span></div>
                    <p>Bayi/satış ortağı yönetimi — onboarding, kademeli komisyon, materyal kütüphanesi, otomatik payout.</p>
                    <ul class="addon-features">
                        <li>Bayi başvuru + onay akışı</li>
                        <li>5 kademe komisyon sistemi</li>
                        <li>Pazarlama materyali kütüphanesi</li>
                        <li>UTM link generator</li>
                        <li>Stripe payout entegre</li>
                    </ul>
                </div>

                {{-- HR --}}
                <div class="addon-card" data-addon="hr" data-price="99">
                    <div class="addon-header">
                        <div class="addon-icon" style="background:linear-gradient(140deg, #db2777, #831843);">👔</div>
                        <label class="addon-switch">
                            <input type="checkbox" data-addon-toggle="hr">
                            <span class="addon-slider"></span>
                        </label>
                    </div>
                    <h4>HR — İnsan Kaynakları</h4>
                    <div class="addon-price">€99<span>/ay</span></div>
                    <p>Personel yönetimi — özlük dosyası, izin yönetimi, devam takibi, sertifika, bordro profilleri.</p>
                    <ul class="addon-features">
                        <li>Personel veritabanı + özlük</li>
                        <li>İzin onay akışı</li>
                        <li>Devam/giriş-çıkış takibi</li>
                        <li>Sertifika + eğitim kaydı</li>
                        <li>Bordro profili + KPI</li>
                    </ul>
                </div>

                {{-- FINANS --}}
                <div class="addon-card" data-addon="finance" data-price="149">
                    <div class="addon-header">
                        <div class="addon-icon" style="background:linear-gradient(140deg, #f59e0b, #b45309);">💰</div>
                        <label class="addon-switch">
                            <input type="checkbox" data-addon-toggle="finance">
                            <span class="addon-slider"></span>
                        </label>
                    </div>
                    <h4>Finans Modülü</h4>
                    <div class="addon-price">€149<span>/ay</span></div>
                    <p>Stripe ödeme + faturalama + taksitli ödeme + multi-currency. Vadesi geçen takibi + dealer payout.</p>
                    <ul class="addon-features">
                        <li>Stripe checkout + webhook</li>
                        <li>Taksitli ödeme planı</li>
                        <li>Multi-currency (EUR/TRY/USD)</li>
                        <li>Overdue + reminder otomasyon</li>
                        <li>Aylık MRR + churn raporu</li>
                    </ul>
                </div>

                {{-- KEŞFET --}}
                <div class="addon-card" data-addon="discover" data-price="79">
                    <div class="addon-header">
                        <div class="addon-icon" style="background:linear-gradient(140deg, #2563eb, #1e3a8a);">🔍</div>
                        <label class="addon-switch">
                            <input type="checkbox" data-addon-toggle="discover">
                            <span class="addon-slider"></span>
                        </label>
                    </div>
                    <h4>Keşfet — Üniversite & Vize</h4>
                    <div class="addon-price">€79<span>/ay</span></div>
                    <p>500+ Almanya üniversitesi belge haritası + program kataloğu + vize gereklilikleri rehberi. Hep güncel.</p>
                    <ul class="addon-features">
                        <li>500+ üniversite veritabanı</li>
                        <li>Program filtreleri (BSc/MSc/PhD)</li>
                        <li>Belge gereklilikleri haritası</li>
                        <li>Vize randevu rehberi</li>
                        <li>DAAD + resmi kaynak sync</li>
                    </ul>
                </div>

                {{-- BOOKING --}}
                <div class="addon-card" data-addon="booking" data-price="49">
                    <div class="addon-header">
                        <div class="addon-icon" style="background:linear-gradient(140deg, #0ea5e9, #075985);">📅</div>
                        <label class="addon-switch">
                            <input type="checkbox" data-addon-toggle="booking">
                            <span class="addon-slider"></span>
                        </label>
                    </div>
                    <h4>Booking & Takvim</h4>
                    <div class="addon-price">€49<span>/ay</span></div>
                    <p>Danışman müsaitlik takvimi, public booking sayfası, Google Calendar senkronu. Aday self-service randevu alır.</p>
                    <ul class="addon-features">
                        <li>Danışman pattern + exception</li>
                        <li>Public booking landing</li>
                        <li>Google Calendar 2-way sync</li>
                        <li>Multi-timezone slot generator</li>
                        <li>Otomatik onay + hatırlatma</li>
                    </ul>
                </div>

                {{-- CONTRACTS HUB --}}
                <div class="addon-card" data-addon="contracts" data-price="79">
                    <div class="addon-header">
                        <div class="addon-icon" style="background:linear-gradient(140deg, #7c3aed, #4c1d95);">📄</div>
                        <label class="addon-switch">
                            <input type="checkbox" data-addon-toggle="contracts">
                            <span class="addon-slider"></span>
                        </label>
                    </div>
                    <h4>Contracts Hub — Sözleşme Yönetimi</h4>
                    <div class="addon-price">€79<span>/ay</span></div>
                    <p>Şablon tabanlı sözleşme üretimi, workflow state machine, e-imza, audit log. PDF tek tıkla.</p>
                    <ul class="addon-features">
                        <li>Dinamik şablon (değişken alan)</li>
                        <li>Workflow: draft → review → signed</li>
                        <li>E-imza entegrasyonu</li>
                        <li>Versiyon geçmişi + audit log</li>
                        <li>Toplu PDF export</li>
                    </ul>
                </div>

                {{-- ANALYTICS HUB --}}
                <div class="addon-card" data-addon="analytics" data-price="99">
                    <div class="addon-header">
                        <div class="addon-icon" style="background:linear-gradient(140deg, #10b981, #064e3b);">📊</div>
                        <label class="addon-switch">
                            <input type="checkbox" data-addon-toggle="analytics">
                            <span class="addon-slider"></span>
                        </label>
                    </div>
                    <h4>Analytics Hub</h4>
                    <div class="addon-price">€99<span>/ay</span></div>
                    <p>PostHog event tracking + KPI dashboard + dönüşüm hunisi + zamanlanmış raporlar. Veri ile karar ver.</p>
                    <ul class="addon-features">
                        <li>PostHog 25+ event hazır</li>
                        <li>Rol bazlı KPI dashboard</li>
                        <li>Conversion funnel + cohort</li>
                        <li>Scheduled reports (PDF/email)</li>
                        <li>User Activity Intelligence</li>
                    </ul>
                </div>

                {{-- DOCUMENT BUILDER AI --}}
                <div class="addon-card" data-addon="docbuilder" data-price="49">
                    <div class="addon-header">
                        <div class="addon-icon" style="background:linear-gradient(140deg, #f43f5e, #881337);">✍️</div>
                        <label class="addon-switch">
                            <input type="checkbox" data-addon-toggle="docbuilder">
                            <span class="addon-slider"></span>
                        </label>
                    </div>
                    <h4>Doküman Üretici (AI)</h4>
                    <div class="addon-price">€49<span>/ay</span></div>
                    <p>Öğrenci motivasyon mektubu + CV + niyet beyanı şablonları AI ile. Senior onayı ile final.</p>
                    <ul class="addon-features">
                        <li>Motivasyon mektubu generator</li>
                        <li>CV + Lebenslauf şablonları</li>
                        <li>AI taslak → senior düzenleme</li>
                        <li>Çoklu dil (TR/DE/EN)</li>
                        <li>PDF + DOCX export</li>
                    </ul>
                </div>
            </div>

            {{-- ──── PRICE SUMMARY ──── --}}
            <div class="price-summary">
                <div class="ps-row">
                    <span class="ps-lbl">Core Platform</span>
                    <span class="ps-val">€199<small>/ay</small></span>
                </div>
                <div class="ps-row" id="ps-addons-row" style="display:none;">
                    <span class="ps-lbl">Seçilen add-on'lar (<span id="ps-addon-count">0</span>)</span>
                    <span class="ps-val" id="ps-addons-total">€0<small>/ay</small></span>
                </div>
                <div class="ps-row" id="ps-discount-row" style="display:none; color:var(--success);">
                    <span class="ps-lbl">🎉 Tümünü seç paket indirimi (%15)</span>
                    <span class="ps-val" id="ps-discount-amount">−€0<small>/ay</small></span>
                </div>
                <div class="ps-row total">
                    <span class="ps-lbl"><strong>TOPLAM AYLIK</strong></span>
                    <span class="ps-val total-amount" id="ps-total">€199<small>/ay</small></span>
                </div>
                <div class="ps-yearly">
                    💡 Yıllık ödeme: <strong id="ps-yearly">€1.910/yıl</strong> <small>(2 ay bedava — %17 ek tasarruf)</small>
                </div>

                <div class="ps-cta">
                    <a href="#cta" class="btn-primary"
                       data-track="cta_clicked"
                       data-ph-cta-name="modular_demo"
                       data-ph-location="platform_pricing">
                        🎯 Bu Paketle Demo İste
                    </a>
                    <a href="#cta" class="btn-ghost"
                       data-track="cta_clicked"
                       data-ph-cta-name="modular_consult">
                        Danışmana Sor
                    </a>
                </div>
            </div>
        </div>

        {{-- Floating mini-pill: scroll'da küçük gösterim, full summary'e zıplama --}}
        <button type="button" class="price-pill" id="price-pill" aria-label="Toplam fiyatı gör">
            <span class="pill-count" id="pill-count">0</span>
            <span>Toplam: <strong id="pill-total">€199/ay</strong></span>
            <span class="arrow">↓</span>
        </button>

        {{-- Enterprise/White-label callout --}}
        <div class="enterprise-callout">
            <div>
                <span class="sec-label" style="background:rgba(255,255,255,.18); color:#fff; border:1px solid rgba(255,255,255,.28);">Enterprise / White-label</span>
                <h3 style="margin:10px 0 8px; font-size:24px; color:#fff;">Çok şubeli, marka olarak satmak isteyen firmalar için</h3>
                <p style="margin:0; color:rgba(255,255,255,.85); font-size:14px;">
                    Sınırsız kullanıcı · Multi-tenant (çok şirket/şube) · White-label (kendi domain + logo + tema) · Custom modül geliştirme · Dedicated CSM · On-premise opsiyonu
                </p>
            </div>
            <a href="#cta" class="btn-gold"
               data-track="cta_clicked"
               data-ph-cta-name="enterprise_contact">
                İletişime Geç →
            </a>
        </div>

        <p style="text-align:center; margin-top:32px; font-size:13px; color:var(--muted);">
            🎁 14 gün ücretsiz deneme · Kredi kartı gerekmez · Add-on'ları istediğin zaman aç/kapat · İstediğin zaman iptal
        </p>
    </div>
</section>

{{-- ═══ PARTNER PROGRAM === --}}
<section id="partner" class="sec-bg-white">
    <div class="container">
        <div class="partner-card">
            <div class="partner-grid">
                <div>
                    <span class="sec-label">Partner Programı</span>
                    <h2 class="sec-title" style="font-size:38px;">Türkiye'nin önde gelen <em>danışmanlık firması</em> mısın?</h2>
                    <p style="font-size:16px; color:var(--muted); margin:0 0 24px;">
                        {{ $brand }} ile partner ol — bizim Almanya operasyon altyapımızı kullan, sen kendi markanı büyüt.
                        White-label opsiyonu + kademeli komisyon + ortak pazarlama desteği.
                    </p>

                    <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:24px;">
                        <div style="display:flex; gap:12px; align-items:center;">
                            <span style="background:var(--primary); color:#fff; width:24px; height:24px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:800;">1</span>
                            <span style="font-size:14px;"><strong>Sen yönlendir, biz sürdür.</strong> Müşterilerin kayıp olmaz.</span>
                        </div>
                        <div style="display:flex; gap:12px; align-items:center;">
                            <span style="background:var(--primary); color:#fff; width:24px; height:24px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:800;">2</span>
                            <span style="font-size:14px;"><strong>Almanya operasyonu bizden.</strong> Vize, üniversite, konaklama hepsi.</span>
                        </div>
                        <div style="display:flex; gap:12px; align-items:center;">
                            <span style="background:var(--primary); color:#fff; width:24px; height:24px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:800;">3</span>
                            <span style="font-size:14px;"><strong>Şeffaf komisyon — kademeli.</strong> €200-€750/öğrenci.</span>
                        </div>
                        <div style="display:flex; gap:12px; align-items:center;">
                            <span style="background:var(--primary); color:#fff; width:24px; height:24px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:800;">4</span>
                            <span style="font-size:14px;"><strong>Vize reddi güvencesi —</strong> teselli payı garantili.</span>
                        </div>
                    </div>

                    <a href="{{ route('public.dealer-landing') }}"
                       class="btn-primary"
                       data-track="cta_clicked"
                       data-ph-cta-name="partner_program">
                        🤝 Partner Programını İncele →
                    </a>
                </div>

                <div>
                    <div class="partner-stats">
                        <div class="partner-stat">
                            <div class="num">€200-750</div>
                            <div class="lbl">öğrenci başına</div>
                        </div>
                        <div class="partner-stat">
                            <div class="num">100€</div>
                            <div class="lbl">hoş geldin bonusu</div>
                        </div>
                        <div class="partner-stat">
                            <div class="num">15 gün</div>
                            <div class="lbl">hızlı ödeme</div>
                        </div>
                        <div class="partner-stat">
                            <div class="num">5 kademe</div>
                            <div class="lbl">Bronz → Elmas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ FAQ === --}}
<section class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">SSS</span>
        <h2 class="sec-title" style="text-align:center; margin-left:auto; margin-right:auto;">Sıkça Sorulan Sorular</h2>

        <div class="faq-list">
            <div class="faq-item">
                <button type="button" class="faq-q">SaaS olarak satın alıp kendi firmamda kullanabilir miyim? <span class="faq-icon">+</span></button>
                <div class="faq-a">Evet. {{ $brand }} multi-tenant mimaride çalışır — her firma kendi izole verisi, kendi kullanıcıları, kendi marka kimliği ile sistemi kullanır. Gold + Premium planlarda white-label (kendi domain + logo + tema) tam destekli.</div>
            </div>
            <div class="faq-item">
                <button type="button" class="faq-q">Verilerimiz nerede saklanıyor? <span class="faq-icon">+</span></button>
                <div class="faq-a">Tüm production datası Almanya/Frankfurt EU bölgesinde. PostgreSQL/MySQL + günlük backup + 30 gün retention. GDPR + KVKK + EU data residency tam uyumlu.</div>
            </div>
            <div class="faq-item">
                <button type="button" class="faq-q">Mevcut Excel/CRM verimi import edebilir miyim? <span class="faq-icon">+</span></button>
                <div class="faq-a">Evet. CSV bulk-import + API endpoint'leri mevcut. Onboarding ekibimiz Premium müşteriler için manuel migration desteği sunar (1-2 hafta içinde tüm veri taşınır).</div>
            </div>
            <div class="faq-item">
                <button type="button" class="faq-q">Eğitim ve onboarding süreci nasıl? <span class="faq-icon">+</span></button>
                <div class="faq-a">14 gün ücretsiz deneme + ücretsiz video kütüphanesi. Gold müşterilerine 2 saatlik canlı onboarding workshop. Premium'da yerinde/online dedicated training. Kullanım kılavuzu + el kitabı tam Türkçe.</div>
            </div>
            <div class="faq-item">
                <button type="button" class="faq-q">AI asistan hangi sorulara cevap verebiliyor? <span class="faq-icon">+</span></button>
                <div class="faq-a">Sen knowledge base'e PDF/URL/metin ekledikten sonra AI asistan o kaynaklardan cevap verir. Almanya eğitim, vize, Sperrkonto, üniversite başvuru, dil sınavı vb. tüm konularda. Cevap bulamazsa Gemini'nin genel bilgisinden cevaplar (mode: external).</div>
            </div>
            <div class="faq-item">
                <button type="button" class="faq-q">Mobil uygulama var mı? <span class="faq-icon">+</span></button>
                <div class="faq-a">Tüm portallar mobile-responsive — telefondan tarayıcıda tam çalışır. Native iOS/Android uygulaması Q3 2026 roadmap'inde, Premium müşterilerine erken erişim.</div>
            </div>
            <div class="faq-item">
                <button type="button" class="faq-q">Sözleşme bağlayıcı mı? <span class="faq-icon">+</span></button>
                <div class="faq-a">Hayır. Aylık ödeme + istediğin zaman iptal. 14 gün ücretsiz deneme + 7 gün geri ödeme garantisi. Premium müşteriler için yıllık plan opsiyonel %20 indirim.</div>
            </div>
            <div class="faq-item">
                <button type="button" class="faq-q">Teknik destek nasıl çalışıyor? <span class="faq-icon">+</span></button>
                <div class="faq-a">Basic: Email destek, 24h SLA. Gold: WhatsApp + email, 4h SLA + acil durum hattı. Premium: Dedicated Customer Success Manager, 1h SLA, telefonla erişim.</div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ CTA FINAL === --}}
<section id="cta" class="cta-final">
    <div class="container">
        <h2>Demo'yu kendi gözlerinle gör.</h2>
        <p>14 gün ücretsiz dene · Kredi kartı gerekmez · 30 dakikalık özel demo + kurulum yardımı.</p>

        <div class="ctas">
            <a href="mailto:info@mentorde.com?subject=Demo%20Talebi%20-%20{{ urlencode($brand) }}%20Platform"
               class="btn-gold"
               data-track="cta_clicked"
               data-ph-cta-name="cta_demo_email"
               data-ph-location="platform_cta">
                🎯 Demo Talebi Gönder
            </a>
            <a href="https://wa.me/4915203253691?text={{ urlencode('Merhaba, ' . $brand . ' platformu hakkında bilgi almak istiyorum.') }}"
               target="_blank" rel="noopener"
               class="btn-ghost"
               style="border-color:#fff; color:#fff !important; background:transparent;"
               data-track="cta_clicked"
               data-ph-cta-name="cta_whatsapp"
               data-ph-location="platform_cta">
                💬 WhatsApp ile Konuş
            </a>
        </div>

        <div class="contacts">
            <div>📧 <a href="mailto:info@mentorde.com">info@mentorde.com</a></div>
            <div>💬 <a href="https://wa.me/4915203253691">+49 1520 325 3691</a></div>
            <div>🌐 <a href="https://panel.mentorde.com">panel.mentorde.com</a></div>
        </div>
    </div>
</section>

{{-- ═══ FOOTER === --}}
<footer>
    <div class="container">
        <div>
            <div class="p-logo" style="color:#fff; margin-bottom:12px;">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $brand }}" style="background:#fff; padding:6px 10px; border-radius:8px;">
                @else
                    mentor<span style="color:var(--neutral); font-style:italic;">de</span>
                @endif
            </div>
            <p style="margin:0; color:rgba(255,255,255,.6);">
                Almanya eğitim danışmanlığında end-to-end SaaS platformu. 6 portal, 28+ modül, AI destekli — tek panel, sınırsız ölçek.
            </p>
        </div>
        <div>
            <h5>Ürün</h5>
            <ul>
                <li><a href="#portallar">6 Portal</a></li>
                <li><a href="#moduller">Modüller</a></li>
                <li><a href="#ai">AI Asistan</a></li>
                <li><a href="#fiyat">Fiyatlandırma</a></li>
            </ul>
        </div>
        <div>
            <h5>Programlar</h5>
            <ul>
                <li><a href="{{ route('public.dealer-landing') }}">Satış Ortaklığı</a></li>
                <li><a href="{{ route('public.dealer-application.create') }}">Başvuru Formu</a></li>
                <li><a href="/randevu">Randevu Al</a></li>
                <li><a href="/sss">SSS</a></li>
            </ul>
        </div>
        <div>
            <h5>İletişim</h5>
            <ul>
                <li>📧 info@mentorde.com</li>
                <li>💬 +49 1520 325 3691</li>
                <li><a href="/legal/privacy">Gizlilik</a></li>
                <li><a href="/legal/terms">Kullanım</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom container" style="display:block;">
        © {{ date('Y') }} {{ $brand }} · Almanya eğitim danışmanlığında uzman SaaS platformu.
    </div>
</footer>

<script nonce="{{ $cspNonce ?? '' }}">
// FAQ accordion
document.querySelectorAll('.faq-q').forEach(btn => {
    btn.addEventListener('click', () => {
        const item = btn.closest('.faq-item');
        const wasOpen = item.classList.contains('open');
        document.querySelectorAll('.faq-item.open').forEach(i => i.classList.remove('open'));
        if (!wasOpen) item.classList.add('open');
    });
});

// Modular Pricing Calculator
(function() {
    const CORE_PRICE = 199;
    const BUNDLE_DISCOUNT_PCT = 0.15; // tümü açıkken %15 indirim
    const YEARLY_DISCOUNT_PCT = 0.17; // yıllık ödeme ek tasarrufu

    const totalEl = document.getElementById('ps-total');
    const yearlyEl = document.getElementById('ps-yearly');
    const addonsRow = document.getElementById('ps-addons-row');
    const addonsTotalEl = document.getElementById('ps-addons-total');
    const addonCountEl = document.getElementById('ps-addon-count');
    const discountRow = document.getElementById('ps-discount-row');
    const discountAmountEl = document.getElementById('ps-discount-amount');
    const selectAllToggle = document.getElementById('select-all-addons');

    function formatEur(n) {
        return '€' + Math.round(n).toLocaleString('tr-TR').replace(/,/g, '.');
    }

    const pricePill = document.getElementById('price-pill');
    const pillCountEl = document.getElementById('pill-count');
    const pillTotalEl = document.getElementById('pill-total');
    const priceSummaryEl = document.querySelector('.price-summary');

    function recalc() {
        const cards = document.querySelectorAll('.addon-card');
        let addonsTotal = 0;
        let activeCount = 0;
        const totalAddons = cards.length;

        cards.forEach(card => {
            const toggle = card.querySelector('input[type=checkbox]');
            const price = parseInt(card.dataset.price, 10) || 0;
            if (toggle && toggle.checked) {
                card.classList.add('active');
                addonsTotal += price;
                activeCount++;
            } else {
                card.classList.remove('active');
            }
        });

        // Bundle discount: tümü açıksa %15
        let discount = 0;
        if (activeCount === totalAddons) {
            discount = addonsTotal * BUNDLE_DISCOUNT_PCT;
        }

        const grandTotal = CORE_PRICE + addonsTotal - discount;
        const yearlyTotal = grandTotal * 12 * (1 - YEARLY_DISCOUNT_PCT);

        // Update DOM — main summary
        totalEl.innerHTML = formatEur(grandTotal) + '<small>/ay</small>';
        yearlyEl.textContent = formatEur(yearlyTotal) + '/yıl';

        // Pill
        if (pillCountEl) pillCountEl.textContent = activeCount;
        if (pillTotalEl) pillTotalEl.textContent = formatEur(grandTotal) + '/ay';

        if (activeCount > 0) {
            addonsRow.style.display = 'flex';
            addonsTotalEl.innerHTML = formatEur(addonsTotal) + '<small>/ay</small>';
            addonCountEl.textContent = activeCount;
        } else {
            addonsRow.style.display = 'none';
        }

        if (discount > 0) {
            discountRow.style.display = 'flex';
            discountAmountEl.innerHTML = '−' + formatEur(discount) + '<small>/ay</small>';
        } else {
            discountRow.style.display = 'none';
        }

        // Sync "select all" state
        if (selectAllToggle) {
            selectAllToggle.checked = (activeCount === totalAddons);
        }
    }

    // Pill: yalnızca pricing section'dayken VE summary görünür değilken görünür
    if (pricePill && priceSummaryEl) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.target === priceSummaryEl) {
                    // Summary görünüyorsa pill gizlen
                    pricePill.classList.toggle('visible', !entry.isIntersecting);
                }
            });
        }, { threshold: 0.1 });
        observer.observe(priceSummaryEl);

        // Pricing section sona erdiğinde de gizle
        const pricingSection = document.getElementById('fiyat');
        if (pricingSection) {
            const sectionObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (!entry.isIntersecting) pricePill.classList.remove('visible');
                });
            }, { threshold: 0 });
            sectionObserver.observe(pricingSection);
        }

        pricePill.addEventListener('click', () => {
            priceSummaryEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }

    // Card click → toggle (anywhere on card except switch itself, which has its own handler)
    document.querySelectorAll('.addon-card').forEach(card => {
        card.addEventListener('click', (e) => {
            if (e.target.closest('.addon-switch')) return; // switch already handles
            const toggle = card.querySelector('input[type=checkbox]');
            if (toggle) {
                toggle.checked = !toggle.checked;
                recalc();

                if (window.posthog) {
                    window.posthog.capture('addon_toggled', {
                        addon: card.dataset.addon,
                        active: toggle.checked,
                    });
                }
            }
        });
    });

    // Direct switch toggle
    document.querySelectorAll('.addon-card input[type=checkbox]').forEach(cb => {
        cb.addEventListener('change', () => {
            recalc();
            if (window.posthog) {
                const card = cb.closest('.addon-card');
                window.posthog.capture('addon_toggled', {
                    addon: card?.dataset.addon,
                    active: cb.checked,
                });
            }
        });
    });

    // Select all
    if (selectAllToggle) {
        selectAllToggle.addEventListener('change', () => {
            document.querySelectorAll('.addon-card input[type=checkbox]').forEach(cb => {
                cb.checked = selectAllToggle.checked;
            });
            recalc();

            if (window.posthog) {
                window.posthog.capture('select_all_addons', { selected: selectAllToggle.checked });
            }
        });
    }

    recalc();
})();
</script>

<x-analytics.posthog-snippet :portal="'public'" />
<x-analytics.consent-banner />

</body>
</html>
