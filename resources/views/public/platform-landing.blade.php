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
<meta name="description" content="6 portal · 24+ modül · AI asistan · entegre CRM, vize, ödeme & analytics. Yurt dışı eğitim danışmanlığı firmaları için profesyonel SaaS çözümü.">
<meta name="robots" content="index, follow">
<meta property="og:title" content="{{ $brand }} Platform — Yurt Dışı Eğitim Danışmanlığı SaaS">
<meta property="og:description" content="Tek panel, sınırsız ölçek. Almanya odaklı, çok-portal, AI destekli end-to-end danışmanlık platformu.">
<meta property="og:type" content="website">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">

<style>
:root {
    --primary:#5b2e91;
    --primary-dark:#4a2377;
    --primary-deep:#3d1c67;
    --primary-soft:#f1e8fb;
    --accent:#e8b931;
    --accent-dark:#c99c26;
    --success:#16a34a;
    --info:#2563eb;
    --danger:#dc2626;
    --warn:#f59e0b;
    --text:#12233a;
    --muted:#5e7187;
    --line:#d9e2ee;
    --surface:#ffffff;
    --bg:#f9fafd;
    --gradient-purple:linear-gradient(140deg, #5b2e91 0%, #3d1c67 100%);
    --gradient-gold:linear-gradient(140deg, #e8b931 0%, #c99c26 100%);
    --gradient-mix:linear-gradient(140deg, #5b2e91 0%, #e8b931 200%);
}
* { box-sizing:border-box; }
html, body { margin:0; padding:0; scroll-behavior:smooth; }
body {
    font-family:"Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, sans-serif;
    color:var(--text);
    background:linear-gradient(180deg, #f7f3ff 0%, #f9fafd 50%, #fff8e8 100%);
    line-height:1.6;
    font-size:15px;
    -webkit-font-smoothing:antialiased;
}
.serif { font-family:"DM Serif Display", Georgia, serif; font-weight:normal; font-style:italic; }
a { color:var(--primary); text-decoration:none; }
img { max-width:100%; height:auto; display:block; }

/* === NAV === */
.p-nav {
    position:sticky; top:0; z-index:50;
    background:rgba(255,255,255,.92); backdrop-filter:blur(12px);
    border-bottom:1px solid var(--line);
}
.p-nav-inner { max-width:1200px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; padding:14px 22px; gap:16px; }
.p-logo { font-family:"DM Serif Display", serif; font-size:28px; color:var(--primary); letter-spacing:-.5px; line-height:1; display:inline-flex; align-items:center; gap:2px; }
.p-logo span { color:var(--primary-dark); font-style:italic; }
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
.sec-bg-dark .sec-label { color:var(--accent); background:rgba(232,185,49,.15); }
.sec-title {
    font-family:"DM Serif Display", serif; font-style:italic;
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
    padding:16px 32px; background:var(--accent); color:var(--primary-deep) !important;
    border-radius:12px; font-size:15px; font-weight:800; border:none; cursor:pointer;
    box-shadow:0 6px 20px rgba(232,185,49,.4);
    transition:all .18s;
}
.btn-gold:hover { background:var(--accent-dark); transform:translateY(-2px); text-decoration:none !important; }

/* === HERO === */
.hero { padding:80px 0 60px; position:relative; overflow:hidden; }
.hero::before {
    content:''; position:absolute; inset:0; z-index:-1;
    background:
        radial-gradient(80% 60% at 70% 20%, rgba(91,46,145,.18), transparent 70%),
        radial-gradient(60% 50% at 20% 80%, rgba(232,185,49,.18), transparent 70%);
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
    font-family:"DM Serif Display", serif;
    font-size:clamp(40px, 6vw, 68px); line-height:1.04; letter-spacing:-2px;
    margin:0 0 24px; color:var(--primary-deep); font-style:normal;
}
.hero h1 em { font-style:italic; background:var(--gradient-mix); -webkit-background-clip:text; background-clip:text; color:transparent; }
.hero-lead { font-size:19px; color:var(--muted); margin:0 0 36px; max-width:600px; line-height:1.6; }
.hero-ctas { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:36px; }
.hero-trust { display:flex; gap:32px; flex-wrap:wrap; align-items:center; padding-top:24px; border-top:1px solid var(--line); }
.hero-trust-item { font-size:12px; color:var(--muted); }
.hero-trust-item strong { display:block; font-size:24px; color:var(--primary-deep); font-family:"DM Serif Display", serif; line-height:1; }

.hero-visual {
    position:relative; perspective:1200px;
}
.hero-card-stack {
    position:relative; transform-style:preserve-3d;
    transform:rotateY(-12deg) rotateX(6deg);
}
.hero-card {
    background:#fff; border:1px solid var(--line); border-radius:18px;
    padding:18px; box-shadow:0 24px 48px rgba(91,46,145,.18);
    margin-bottom:14px;
}
.hero-card-1 { transform:translateZ(0); }
.hero-card-2 { transform:translateZ(20px) translateX(40px) translateY(20px); position:absolute; top:80px; right:-30px; width:240px; }
.hero-card-3 { transform:translateZ(40px) translateX(-30px) translateY(60px); position:absolute; bottom:-30px; left:-20px; width:220px; }
.hero-card .lbl { font-size:10px; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; font-weight:700; margin-bottom:6px; }
.hero-card .val { font-family:"DM Serif Display", serif; font-size:24px; color:var(--primary-deep); line-height:1; }
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
.stat-num { font-family:"DM Serif Display", serif; font-size:46px; line-height:1; color:var(--primary-deep); margin-bottom:8px; letter-spacing:-1px; }
.stat-num span { color:var(--accent-dark); }
.stat-lbl { font-size:13px; color:var(--muted); font-weight:600; }

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

/* === AI HIGHLIGHT === */
.ai-spotlight {
    background:var(--gradient-purple); color:#fff;
    border-radius:32px; padding:60px; position:relative; overflow:hidden;
    box-shadow:0 24px 60px rgba(61,28,103,.3);
}
.ai-spotlight::before {
    content:''; position:absolute; top:-50px; right:-50px; width:300px; height:300px;
    background:radial-gradient(circle, rgba(232,185,49,.3), transparent 70%);
    border-radius:50%;
}
.ai-grid { display:grid; grid-template-columns:1.2fr 1fr; gap:50px; align-items:center; position:relative; z-index:1; }
@media(max-width:900px) { .ai-grid { grid-template-columns:1fr; } }
.ai-spotlight h2 { font-family:"DM Serif Display", serif; font-style:italic; font-size:42px; line-height:1.1; margin:0 0 18px; color:#fff; }
.ai-spotlight h2 em { color:var(--accent); }
.ai-spotlight p { font-size:17px; opacity:.9; margin:0 0 30px; }
.ai-features { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:30px; }
.ai-feature { background:rgba(255,255,255,.08); border-radius:12px; padding:14px; backdrop-filter:blur(10px); }
.ai-feature .lbl { font-size:11px; opacity:.7; text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px; }
.ai-feature .val { font-size:15px; font-weight:700; color:var(--accent); }
.ai-mockup { background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.15); border-radius:18px; padding:24px; backdrop-filter:blur(10px); }
.ai-msg { background:rgba(232,185,49,.18); border-radius:12px; padding:12px 14px; margin-bottom:10px; font-size:13px; }
.ai-reply { background:rgba(255,255,255,.08); border-radius:12px; padding:12px 14px; font-size:13px; line-height:1.55; }
.ai-reply .badge { display:inline-block; background:rgba(255,255,255,.15); color:var(--accent); padding:2px 8px; border-radius:8px; font-size:10px; font-weight:700; margin-bottom:6px; }

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
.pricing-name { font-family:"DM Serif Display", serif; font-style:italic; font-size:32px; color:var(--primary-deep); margin:0 0 14px; }
.pricing-price { display:flex; align-items:baseline; gap:6px; margin-bottom:6px; }
.pricing-price .num { font-family:"DM Serif Display", serif; font-size:48px; color:var(--primary-deep); line-height:1; }
.pricing-price .period { color:var(--muted); font-size:14px; }
.pricing-desc { color:var(--muted); font-size:13px; margin-bottom:24px; min-height:50px; }
.pricing-features { list-style:none; padding:0; margin:0 0 30px; flex:1; }
.pricing-features li { padding:8px 0 8px 24px; position:relative; font-size:13px; line-height:1.5; }
.pricing-features li::before { content:'✓'; position:absolute; left:0; color:var(--success); font-weight:900; }
.pricing-features li.disabled { color:var(--muted); opacity:.6; }
.pricing-features li.disabled::before { content:'—'; color:var(--muted); }

/* === PARTNER === */
.partner-card {
    background:linear-gradient(140deg, #fff, var(--primary-soft));
    border-radius:32px; padding:60px;
    border:1px solid var(--primary-soft);
    position:relative; overflow:hidden;
}
.partner-card::before {
    content:''; position:absolute; top:-100px; right:-100px; width:300px; height:300px;
    background:radial-gradient(circle, rgba(232,185,49,.3), transparent 70%);
    border-radius:50%;
}
.partner-grid { display:grid; grid-template-columns:1.3fr 1fr; gap:50px; align-items:center; position:relative; z-index:1; }
@media(max-width:900px) { .partner-grid { grid-template-columns:1fr; } }
.partner-stats { display:grid; grid-template-columns:repeat(2, 1fr); gap:14px; margin-top:24px; }
.partner-stat { background:#fff; border-radius:14px; padding:18px; border:1px solid var(--line); text-align:center; }
.partner-stat .num { font-family:"DM Serif Display", serif; font-size:28px; color:var(--primary-deep); }
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
    background:radial-gradient(60% 50% at 30% 30%, rgba(232,185,49,.2), transparent 70%);
}
.cta-final .container { position:relative; z-index:1; }
.cta-final h2 { font-family:"DM Serif Display", serif; font-style:italic; font-size:clamp(36px, 5vw, 56px); margin:0 0 18px; line-height:1.1; }
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
footer a:hover { color:var(--accent); }
.footer-bottom { border-top:1px solid rgba(255,255,255,.1); margin-top:32px; padding-top:24px; text-align:center; font-size:12px; opacity:.6; }
</style>
</head>
<body>

{{-- ═══ NAV ═══ --}}
<nav class="p-nav">
    <div class="p-nav-inner">
        <a href="/" class="p-logo">
            mentor<span>de</span>
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
            <span class="hero-badge"><span class="dot"></span> 2026 — End-to-End SaaS Platform</span>
            <h1>Yurt Dışı Eğitim Danışmanlığında<br><em>Tam Otomasyon</em></h1>
            <p class="hero-lead">
                <strong>{{ $brand }}</strong> — Almanya odaklı eğitim danışmanlığı firmaları için 6 portal, 24+ modül,
                AI destekli, end-to-end bulut platformu. Aday → vize → kabul → mezuniyet sürecinin tamamı tek dashboard'da.
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
                    <strong>24+</strong>
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
                <div class="stat-num">24+</div>
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

{{-- ═══ PROBLEM/SOLUTION ═══ --}}
<section class="sec-bg-soft">
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
                <div class="portal-icon-wrap manager">👔</div>
                <div class="portal-name">Portal 5</div>
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

            <div class="portal">
                <div class="portal-icon-wrap marketing">📣</div>
                <div class="portal-name">Portal 6</div>
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
        </div>
    </div>
</section>

{{-- ═══ MODULES === --}}
<section id="moduller" class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">24+ Hazır Modül</span>
        <h2 class="sec-title">Eğitim danışmanlığının her iş süreci için modül.</h2>
        <p class="sec-lead">Hiçbir modülü baştan yazmana gerek yok — hepsi kutudan çıkar çıkmaz çalışıyor.</p>

        <div class="modules-grid">
            <div class="module"><div class="module-icon">👥</div><div><h4>CRM & Lead</h4><p>Lead scoring (5 tier), atama, süreç takibi, dormant alarm.</p></div></div>
            <div class="module"><div class="module-icon">📅</div><div><h4>Booking</h4><p>Senior takvimi, public booking, Google Calendar 2-way sync, reminder.</p></div></div>
            <div class="module"><div class="module-icon">📄</div><div><h4>Document Pipeline</h4><p>Belge upload, OCR, kategori, deadline tracker, e-imza altyapı.</p></div></div>
            <div class="module"><div class="module-icon">📜</div><div><h4>Contracts Hub</h4><p>Sözleşme şablonları, dijital imza, ödeme planı, koşulsuz revize.</p></div></div>
            <div class="module"><div class="module-icon">💳</div><div><h4>Payments</h4><p>Stripe checkout, fatura, taksitli ödeme, overdue takibi, dealer payout.</p></div></div>
            <div class="module"><div class="module-icon">🏛️</div><div><h4>Üniversite Belge Haritası</h4><p>500+ üniversite başvuru gereklilikleri, DAAD entegrasyonu.</p></div></div>
            <div class="module"><div class="module-icon">🛂</div><div><h4>Vize Süreci</h4><p>Konsolosluk randevu, dosya kontrol checklist, mülakat hazırlık.</p></div></div>
            <div class="module"><div class="module-icon">🏠</div><div><h4>Konaklama</h4><p>Wohnung araştırma, Anmeldung, sigorta partner ağı.</p></div></div>
            <div class="module"><div class="module-icon">💬</div><div><h4>Messaging Hub</h4><p>İç DM + Email + WhatsApp + auto-reply (away periods).</p></div></div>
            <div class="module"><div class="module-icon">🎫</div><div><h4>Ticket System</h4><p>SLA tracking, kategorize, auto-assign, çözüm süresi metrikleri.</p></div></div>
            <div class="module"><div class="module-icon">📢</div><div><h4>Bulletin Board</h4><p>Şirket içi duyurular, role-based hedefleme, okundu takibi.</p></div></div>
            <div class="module"><div class="module-icon">📊</div><div><h4>Dashboards (16+)</h4><p>Lead funnel, senior perf, revenue, NPS, GDPR, kampanya ROI.</p></div></div>
            <div class="module"><div class="module-icon">📈</div><div><h4>Marketing Attribution</h4><p>UTM tracking, multi-touch, kanal ROI, lead quality score.</p></div></div>
            <div class="module"><div class="module-icon">📧</div><div><h4>Email Campaigns</h4><p>Resend SMTP, A/B test, drip otomasyonu, analytics.</p></div></div>
            <div class="module"><div class="module-icon">📱</div><div><h4>Sosyal Medya</h4><p>İçerik takvimi, kampanya yönetimi, performans takibi.</p></div></div>
            <div class="module"><div class="module-icon">🤝</div><div><h4>Dealer Network</h4><p>Bayi onboarding, kademe sistemi, komisyon hesap, materyal kütüphanesi.</p></div></div>
            <div class="module"><div class="module-icon">👔</div><div><h4>İnsan Kaynakları</h4><p>Personel, izin, devam, sertifika, bordro profilleri.</p></div></div>
            <div class="module"><div class="module-icon">📋</div><div><h4>Görev Yönetimi</h4><p>Task otomasyon, escalation, kanban, SLA, departman bazlı.</p></div></div>
            <div class="module"><div class="module-icon">📑</div><div><h4>Workflow Engine</h4><p>Kural tabanlı süreç, otomatik atama, koşullu eskalasyon.</p></div></div>
            <div class="module"><div class="module-icon">🤖</div><div><h4>AI Labs</h4><p>Knowledge base, RAG, intent analizi, FAQ önerileri.</p></div></div>
            <div class="module"><div class="module-icon">🔒</div><div><h4>GDPR & Audit</h4><p>Data export, erasure, audit trail, retention policy, IP allowlist.</p></div></div>
            <div class="module"><div class="module-icon">🛡️</div><div><h4>Security</h4><p>2FA, role-based access, brute-force koruması, anomaly detection.</p></div></div>
            <div class="module"><div class="module-icon">💱</div><div><h4>Currency Sync</h4><p>EUR/TRY/USD canlı kur, multi-currency invoice.</p></div></div>
            <div class="module"><div class="module-icon">📝</div><div><h4>Audit Reports</h4><p>Aylık otomatik rapor, scheduled exports, KPI snapshots.</p></div></div>
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

{{-- ═══ PRICING === --}}
<section id="fiyat" class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">SaaS Fiyatlandırma</span>
        <h2 class="sec-title">Şeffaf, ölçekli, danışmanlık firmaları için tasarlanmış.</h2>
        <p class="sec-lead">Sıfır kurulum maliyeti — 14 gün ücretsiz deneme. Aktif öğrenci başına fiyatlama.</p>

        <div class="pricing-grid">
            <div class="pricing-card">
                <div class="pricing-tier">Starter</div>
                <h3 class="pricing-name">Basic</h3>
                <div class="pricing-price">
                    <span class="num">€199</span><span class="period">/ay</span>
                </div>
                <p class="pricing-desc">Yeni başlayan firmalar için temel modüller — küçük ekip, sınırlı modül.</p>
                <ul class="pricing-features">
                    <li>3 portal (Aday + Öğrenci + Senior)</li>
                    <li>Gen kapasiteye kadar 50 aktif öğrenci</li>
                    <li>5 senior + 1 manager kullanıcı</li>
                    <li>CRM + Booking + Belge</li>
                    <li>Temel analytics (5 dashboard)</li>
                    <li>Email destek (24h SLA)</li>
                    <li class="disabled">AI Labs</li>
                    <li class="disabled">Marketing Admin portal</li>
                    <li class="disabled">Custom modül</li>
                </ul>
                <a href="#cta" class="btn-ghost" style="justify-content:center;"
                   data-track="cta_clicked" data-ph-cta-name="pricing_basic">
                    Basic'i Seç
                </a>
            </div>

            <div class="pricing-card featured">
                <span class="pricing-badge">★ En Popüler</span>
                <div class="pricing-tier">Profesyonel</div>
                <h3 class="pricing-name">Gold</h3>
                <div class="pricing-price">
                    <span class="num">€499</span><span class="period">/ay</span>
                </div>
                <p class="pricing-desc">Büyüyen danışmanlık firmaları için tam paket — AI dahil, multi-portal.</p>
                <ul class="pricing-features">
                    <li><strong>Tüm 6 portal</strong></li>
                    <li>500 aktif öğrenciye kadar</li>
                    <li>30 senior + 5 admin kullanıcı</li>
                    <li>Tüm core modüller (24+)</li>
                    <li><strong>AI Labs (Gemini)</strong></li>
                    <li>Marketing + Dealer portal</li>
                    <li>16+ dashboard + custom report</li>
                    <li>WhatsApp + Stripe entegre</li>
                    <li>Öncelikli destek (4h SLA)</li>
                </ul>
                <a href="#cta" class="btn-primary" style="justify-content:center;"
                   data-track="cta_clicked" data-ph-cta-name="pricing_gold">
                    Gold'u Seç →
                </a>
            </div>

            <div class="pricing-card">
                <div class="pricing-tier">Enterprise</div>
                <h3 class="pricing-name">Premium</h3>
                <div class="pricing-price">
                    <span class="num">Özel</span>
                </div>
                <p class="pricing-desc">Çok şubeli, white-label ihtiyaçlı, özel entegrasyonlar gereken firmalar için.</p>
                <ul class="pricing-features">
                    <li>Sınırsız öğrenci + kullanıcı</li>
                    <li>Multi-tenant (çoklu şirket/şube)</li>
                    <li><strong>White-label</strong> (logo + domain + tema)</li>
                    <li>Custom modül geliştirme</li>
                    <li>Custom entegrasyon (CRM/ERP)</li>
                    <li>Özel SLA + dedicated CSM</li>
                    <li>On-premise opsiyonu</li>
                    <li>Eğitim + onboarding workshop</li>
                    <li>VIP 1h destek SLA</li>
                </ul>
                <a href="#cta" class="btn-ghost" style="justify-content:center;"
                   data-track="cta_clicked" data-ph-cta-name="pricing_premium">
                    İletişime Geç
                </a>
            </div>
        </div>

        <p style="text-align:center; margin-top:32px; font-size:13px; color:var(--muted);">
            🎁 Tüm planlar <strong>14 gün ücretsiz deneme</strong> · Kredi kartı gerekmez · İstediğin zaman iptal
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
                mentor<span style="color:var(--accent);">de</span>
            </div>
            <p style="margin:0; color:rgba(255,255,255,.6);">
                Almanya eğitim danışmanlığında end-to-end SaaS platformu. 6 portal, 24+ modül, AI destekli — tek panel, sınırsız ölçek.
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
</script>

<x-analytics.posthog-snippet :portal="'public'" />
<x-analytics.consent-banner />

</body>
</html>
