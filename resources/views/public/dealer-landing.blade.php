<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    $hdrBrand = $brandName ?? config('brand.name', 'MentorDE');
@endphp
<title>Satış Ortaklığı Programı — {{ $hdrBrand }} · Birlikte Kazanalım</title>
<meta name="description" content="{{ $hdrBrand }} Satış Ortaklığı Programı 2026. Almanya eğitim sürecine yönlendirdiğiniz her başarılı kayıt için €200-€750 komisyon kazanın. 100€ hoş geldin bonusu + vize reddi güvencesi.">
<meta name="robots" content="index, follow">
<meta property="og:title" content="{{ $hdrBrand }} Satış Ortaklığı Programı — Birlikte Kazanalım">
<meta property="og:description" content="Sıfır yatırım, Euro bazlı yüksek komisyon, operasyonel destek. Öğrenci başına €200-€750 kazanç.">
<meta property="og:type" content="website">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">

<style>
:root {
    --primary:#5b2e91;
    --primary-dark:#4a2377;
    --primary-deep:#3d1c67;
    --primary-soft:#f1e8fb;
    --accent:#e8b931;
    --accent-dark:#c99c26;
    --success:#16a34a;
    --success-bg:#dcfce7;
    --text:#12233a;
    --muted:#5e7187;
    --line:#d9e2ee;
    --surface:#ffffff;
    --bg:#f9fafd;
}
* { box-sizing:border-box; }
html, body { margin:0; padding:0; scroll-behavior:smooth; }
body {
    font-family:"Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, sans-serif;
    color:var(--text);
    background:linear-gradient(140deg, #f7f3ff 0%, #f9fafd 42%, #fff8e8 100%);
    line-height:1.6;
    font-size:15px;
    -webkit-font-smoothing:antialiased;
}
.serif { font-family:"DM Serif Display", Georgia, serif; font-weight:normal; }
a { color:var(--primary); text-decoration:none; }
a:hover { text-decoration:underline; }

/* === NAV === */
.d-nav {
    position:sticky; top:0; z-index:50;
    background:rgba(255,255,255,.92); backdrop-filter:blur(10px);
    border-bottom:1px solid var(--line);
}
.d-nav-inner { max-width:1180px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; padding:14px 22px; gap:16px; }
.d-logo { font-family:"DM Serif Display", serif; font-size:28px; color:var(--primary); letter-spacing:-.5px; line-height:1; display:inline-flex; align-items:center; gap:2px; }
.d-logo span { color:var(--primary-dark); font-style:italic; }
.d-logo img { max-height:40px; width:auto; display:block; }
.d-logo-wrap-light { background:transparent; border-radius:8px; padding:4px 8px; }
.d-logo-wrap-dark  { background:linear-gradient(140deg, var(--primary), var(--primary-deep)); border-radius:8px; padding:4px 10px; }
.d-nav-links { display:flex; gap:24px; font-size:14px; font-weight:600; }
.d-nav-links a { color:var(--muted); }
.d-nav-links a:hover { color:var(--primary); text-decoration:none; }
.d-nav-cta {
    padding:10px 18px; background:var(--primary); color:#fff !important;
    border-radius:10px; font-size:13px; font-weight:700;
}
.d-nav-cta:hover { background:var(--primary-dark); text-decoration:none !important; }
@media(max-width:720px) { .d-nav-links { display:none; } }

/* === LAYOUT === */
.container { max-width:1180px; margin:0 auto; padding:0 22px; }

/* === HERO === */
.hero { position:relative; overflow:hidden; padding:80px 0 100px; }
.hero::before {
    content:''; position:absolute; inset:0; z-index:-1;
    background:
        radial-gradient(80% 60% at 70% 20%, rgba(91,46,145,.18), transparent 70%),
        radial-gradient(60% 50% at 20% 80%, rgba(232,185,49,.15), transparent 70%);
}
.hero-grid { display:grid; grid-template-columns:1.3fr 1fr; gap:48px; align-items:center; }
@media(max-width:920px) { .hero-grid { grid-template-columns:1fr; gap:32px; } }
.hero-badge {
    display:inline-flex; align-items:center; gap:6px;
    background:var(--primary-soft); color:var(--primary-dark);
    padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700;
    text-transform:uppercase; letter-spacing:.08em; margin-bottom:20px;
}
.hero h1 {
    font-family:"DM Serif Display", Georgia, serif;
    font-size:clamp(36px, 5vw, 58px); line-height:1.08; letter-spacing:-1.5px;
    margin:0 0 20px; color:var(--primary-deep);
}
.hero h1 em { color:var(--accent-dark); font-style:italic; }
.hero-lead { font-size:18px; color:var(--muted); margin:0 0 32px; max-width:560px; }
.hero-ctas { display:flex; gap:14px; flex-wrap:wrap; }
.btn-primary {
    display:inline-flex; align-items:center; gap:8px;
    padding:15px 30px; background:var(--primary); color:#fff !important;
    border-radius:12px; font-size:15px; font-weight:700; border:none; cursor:pointer;
    box-shadow:0 4px 14px rgba(91,46,145,.32);
    transition:all .18s;
}
.btn-primary:hover { background:var(--primary-dark); transform:translateY(-2px); text-decoration:none !important; box-shadow:0 8px 24px rgba(91,46,145,.4); }
.btn-ghost {
    display:inline-flex; align-items:center; gap:8px;
    padding:15px 28px; border:2px solid var(--primary); color:var(--primary) !important;
    border-radius:12px; font-size:15px; font-weight:700; background:#fff;
    transition:all .18s;
}
.btn-ghost:hover { background:var(--primary-soft); text-decoration:none !important; }

.hero-visual {
    background:linear-gradient(140deg, var(--primary), var(--primary-deep));
    border-radius:24px; padding:36px; color:#fff;
    box-shadow:0 24px 48px rgba(61,28,103,.35);
    position:relative; overflow:hidden;
}
.hero-visual::after {
    content:''; position:absolute; top:-40px; right:-40px; width:200px; height:200px;
    background:radial-gradient(circle, rgba(232,185,49,.3), transparent 70%);
    border-radius:50%;
}
.hero-visual-title { font-size:13px; text-transform:uppercase; letter-spacing:.1em; opacity:.8; margin-bottom:12px; }
.hero-visual-amount { font-family:"DM Serif Display", serif; font-size:56px; line-height:1; margin:0 0 8px; color:var(--accent); }
.hero-visual-sub { font-size:14px; opacity:.9; margin-bottom:24px; }
.hero-visual-list { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:10px; font-size:13px; }
.hero-visual-list li { display:flex; align-items:center; gap:10px; }
.hero-visual-list li::before { content:'✓'; color:var(--accent); font-weight:900; font-size:16px; }

/* === SECTIONS === */
section { padding:70px 0; }
.sec-label {
    display:inline-block; color:var(--primary); text-transform:uppercase;
    letter-spacing:.15em; font-size:12px; font-weight:800; margin-bottom:14px;
}
.sec-title {
    font-family:"DM Serif Display", serif;
    font-size:clamp(28px, 3.5vw, 40px); line-height:1.15; color:var(--primary-deep);
    letter-spacing:-1px; margin:0 0 16px; max-width:800px;
}
.sec-lead { font-size:17px; color:var(--muted); max-width:680px; margin:0 0 44px; }
.sec-bg-white { background:#fff; }
.sec-bg-soft  { background:linear-gradient(180deg, rgba(91,46,145,.04), transparent); }

/* === STEPS === */
.steps { display:grid; grid-template-columns:repeat(3, 1fr); gap:24px; position:relative; }
@media(max-width:900px) { .steps { grid-template-columns:1fr; } }
.step {
    background:#fff; border-radius:16px; padding:32px 24px;
    border:1px solid var(--line); position:relative;
    transition:all .2s;
}
.step:hover { border-color:var(--primary); transform:translateY(-3px); box-shadow:0 12px 32px rgba(91,46,145,.12); }
.step-num {
    position:absolute; top:-18px; left:24px;
    background:var(--primary); color:#fff; width:36px; height:36px;
    border-radius:50%; display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:14px;
}
.step-icon { font-size:32px; margin-bottom:10px; }
.step h3 { font-size:18px; margin:0 0 8px; color:var(--primary-deep); font-weight:700; }
.step p { font-size:14px; color:var(--muted); margin:0; }
.step strong { color:var(--accent-dark); }

/* === BENEFIT CARDS === */
.benefits { display:grid; grid-template-columns:repeat(2, 1fr); gap:18px; }
@media(max-width:720px) { .benefits { grid-template-columns:1fr; } }
.benefit {
    background:#fff; padding:28px; border-radius:16px;
    border-left:4px solid var(--primary); display:flex; gap:18px; align-items:flex-start;
    box-shadow:0 2px 8px rgba(0,0,0,.04);
}
.benefit-icon {
    font-size:32px; background:var(--primary-soft); width:56px; height:56px;
    border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.benefit h3 { margin:0 0 6px; font-size:17px; color:var(--primary-deep); }
.benefit p { margin:0; color:var(--muted); font-size:14px; line-height:1.55; }

/* === PLAN COMPARE === */
.plans { display:grid; grid-template-columns:1fr 1fr; gap:28px; }
@media(max-width:900px) { .plans { grid-template-columns:1fr; } }
.plan {
    background:#fff; border:2px solid var(--line); border-radius:20px;
    padding:36px 28px; position:relative;
    transition:all .2s;
}
.plan:hover { border-color:var(--primary); transform:translateY(-4px); box-shadow:0 20px 40px rgba(91,46,145,.12); }
.plan.featured { border-color:var(--primary); background:linear-gradient(180deg, #fff, var(--primary-soft)); }
.plan-badge {
    position:absolute; top:-14px; right:24px;
    background:var(--accent); color:#fff; padding:4px 12px; border-radius:20px;
    font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em;
}
.plan-title { font-family:"DM Serif Display", serif; font-size:26px; color:var(--primary-deep); margin:0 0 8px; }
.plan-sub { color:var(--muted); font-size:14px; font-style:italic; margin:0 0 20px; }
.plan-hook { font-size:15px; color:var(--text); font-weight:600; margin:0 0 20px; padding:14px 16px; background:var(--primary-soft); border-radius:10px; border-left:3px solid var(--primary); }
.plan h4 { font-size:13px; text-transform:uppercase; letter-spacing:.08em; color:var(--primary); margin:20px 0 10px; }
.plan ul { padding-left:20px; margin:0 0 16px; color:var(--muted); font-size:14px; }
.plan ul li { margin-bottom:6px; }

/* === TABLE === */
.ctable { width:100%; border-collapse:collapse; margin-top:14px; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.04); }
.ctable th { background:var(--primary); color:#fff; padding:12px 14px; font-size:12px; font-weight:700; text-align:left; text-transform:uppercase; letter-spacing:.04em; }
.ctable td { padding:12px 14px; border-bottom:1px solid var(--line); font-size:13px; }
.ctable td.amount { color:var(--primary); font-weight:800; font-size:15px; white-space:nowrap; }
.ctable tr:last-child td { border-bottom:0; }
.ctable tr:hover { background:var(--primary-soft); }

/* === PROGRAM CARDS === */
.programs { display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; }
@media(max-width:900px) { .programs { grid-template-columns:repeat(2, 1fr); } }
@media(max-width:540px) { .programs { grid-template-columns:1fr; } }
.program {
    background:#fff; border:1px solid var(--line); border-radius:16px;
    padding:28px 22px; text-align:center;
    transition:all .2s;
}
.program:hover { border-color:var(--primary); transform:translateY(-3px); box-shadow:0 12px 24px rgba(91,46,145,.10); }
.program-icon {
    font-size:36px; width:72px; height:72px; background:var(--primary);
    color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center;
    margin:0 auto 18px;
}
.program h3 { margin:0 0 8px; font-size:16px; color:var(--primary-deep); font-weight:700; }
.program p { font-size:13px; color:var(--muted); margin:0; line-height:1.5; }

/* === PANEL FEATURES === */
.features { display:grid; grid-template-columns:1fr 1fr; gap:28px; align-items:center; }
@media(max-width:900px) { .features { grid-template-columns:1fr; } }
.feature-list { list-style:none; padding:0; margin:0; }
.feature-list li { padding:18px 0; border-bottom:1px solid var(--line); display:flex; gap:14px; align-items:flex-start; }
.feature-list li:last-child { border-bottom:0; }
.feature-icon {
    background:var(--primary-soft); color:var(--primary); font-size:22px;
    width:44px; height:44px; border-radius:10px;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.feature-list strong { display:block; color:var(--primary-deep); margin-bottom:4px; font-size:15px; }
.feature-list span { color:var(--muted); font-size:14px; }
.feature-visual {
    background:linear-gradient(140deg, var(--primary-soft), #fff);
    border:1px solid var(--line); border-radius:20px; padding:36px;
    text-align:center;
}

/* === GUARANTEES === */
.guarantees { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
@media(max-width:720px) { .guarantees { grid-template-columns:1fr; } }
.guarantee {
    background:#fff; border-radius:18px; padding:32px;
    border:1px solid var(--line); display:flex; gap:20px; align-items:flex-start;
}
.guarantee-icon {
    background:linear-gradient(140deg, var(--primary), var(--primary-deep));
    color:#fff; font-size:26px; width:64px; height:64px; border-radius:50%;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.guarantee h3 { margin:0 0 8px; font-size:17px; color:var(--primary-deep); }
.guarantee p { margin:0; color:var(--muted); font-size:14px; line-height:1.6; }

/* === CTA === */
.cta-section {
    background:linear-gradient(140deg, var(--primary-deep), var(--primary));
    color:#fff; padding:90px 0; text-align:center; position:relative; overflow:hidden;
}
.cta-section::before {
    content:''; position:absolute; inset:0;
    background:radial-gradient(60% 50% at 30% 20%, rgba(232,185,49,.2), transparent 70%);
    z-index:0;
}
.cta-section .container { position:relative; z-index:1; }
.cta-section h2 { font-family:"DM Serif Display", serif; font-size:clamp(32px, 4vw, 48px); margin:0 0 16px; line-height:1.1; }
.cta-section p { font-size:18px; opacity:.9; margin:0 0 36px; max-width:640px; margin-left:auto; margin-right:auto; }
.cta-section .btn-primary { background:var(--accent); color:var(--primary-deep) !important; font-size:17px; padding:18px 36px; box-shadow:0 8px 24px rgba(232,185,49,.4); }
.cta-section .btn-primary:hover { background:var(--accent-dark); }
.cta-contacts { display:flex; gap:20px; justify-content:center; flex-wrap:wrap; margin-top:48px; color:#fff; font-size:14px; }
.cta-contact { display:flex; align-items:center; gap:10px; background:rgba(255,255,255,.1); padding:10px 18px; border-radius:10px; }
.cta-contact a { color:#fff !important; text-decoration:underline; text-decoration-color:rgba(255,255,255,.4); }

/* === LIVE COUNTERS === */
.live-counters {
    background:linear-gradient(140deg, #fff, var(--primary-soft), #fff);
    padding:50px 0; border-top:1px solid var(--line); border-bottom:1px solid var(--line);
}
.live-head { display:flex; align-items:center; justify-content:center; gap:12px; margin-bottom:30px; color:var(--primary); font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; }
.live-dot { width:8px; height:8px; border-radius:50%; background:#16a34a; box-shadow:0 0 0 0 rgba(22,163,74,.5); animation:pulseDot 1.6s ease-out infinite; }
@keyframes pulseDot { 0% { box-shadow:0 0 0 0 rgba(22,163,74,.6); } 70% { box-shadow:0 0 0 12px rgba(22,163,74,0); } 100% { box-shadow:0 0 0 0 rgba(22,163,74,0); } }
.counters-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:20px; }
@media(max-width:900px) { .counters-grid { grid-template-columns:repeat(2, 1fr); } }
@media(max-width:540px) { .counters-grid { grid-template-columns:1fr; } }
.counter {
    background:#fff; border:1px solid var(--line); border-radius:16px; padding:28px 22px;
    text-align:center; transition:all .25s; position:relative; overflow:hidden;
}
.counter::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg, var(--primary), var(--accent));
}
.counter:hover { transform:translateY(-3px); box-shadow:0 12px 32px rgba(91,46,145,.12); }
.counter-icon { font-size:32px; margin-bottom:10px; }
.counter-value {
    font-family:"DM Serif Display", serif; font-size:42px; line-height:1;
    color:var(--primary-deep); margin:0 0 6px; letter-spacing:-.5px;
    transition:color .3s ease;
}
.counter-value.flash { color:var(--success); }
.counter-label { font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; font-weight:700; }

/* === CALCULATOR === */
.calc-wrap { display:grid; grid-template-columns:1fr 1fr; gap:36px; align-items:start; }
@media(max-width:900px) { .calc-wrap { grid-template-columns:1fr; } }
.calc-form { background:#fff; border:1px solid var(--line); border-radius:20px; padding:32px; }
.calc-form h3 { margin:0 0 20px; font-size:20px; color:var(--primary-deep); font-family:"DM Serif Display", serif; }
.calc-field { margin-bottom:20px; }
.calc-field label { display:block; font-size:13px; font-weight:700; color:var(--text); margin-bottom:8px; }
.calc-field .row { display:flex; align-items:center; gap:12px; }
.calc-field input[type=range] { flex:1; }
.calc-field input[type=number] { width:80px; padding:8px 10px; border:1px solid var(--line); border-radius:8px; text-align:center; font-weight:700; font-size:15px; color:var(--primary); }
.calc-toggle { display:flex; gap:8px; background:var(--primary-soft); padding:4px; border-radius:10px; }
.calc-toggle button { flex:1; padding:10px 14px; border:none; background:transparent; color:var(--muted); font-size:13px; font-weight:700; cursor:pointer; border-radius:8px; transition:all .15s; }
.calc-toggle button.active { background:#fff; color:var(--primary-deep); box-shadow:0 2px 6px rgba(0,0,0,.06); }

.calc-result {
    background:linear-gradient(140deg, var(--primary), var(--primary-deep));
    color:#fff; border-radius:20px; padding:36px; position:relative; overflow:hidden;
    box-shadow:0 12px 32px rgba(61,28,103,.25);
}
.calc-result::after {
    content:''; position:absolute; top:-40px; right:-40px; width:200px; height:200px;
    background:radial-gradient(circle, rgba(232,185,49,.25), transparent 70%);
    border-radius:50%;
}
.calc-result-label { font-size:13px; text-transform:uppercase; letter-spacing:.1em; opacity:.85; margin-bottom:6px; }
.calc-result-amount { font-family:"DM Serif Display", serif; font-size:64px; line-height:1; color:var(--accent); margin:0 0 8px; }
.calc-result-sub { font-size:14px; opacity:.85; margin-bottom:22px; }
.calc-breakdown { background:rgba(255,255,255,.08); border-radius:12px; padding:16px; margin-top:14px; font-size:13px; }
.calc-breakdown-row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid rgba(255,255,255,.1); }
.calc-breakdown-row:last-child { border-bottom:0; font-weight:700; color:var(--accent); padding-top:10px; margin-top:4px; border-top:1px solid rgba(255,255,255,.15); border-bottom:0; }
.calc-tier-badge { display:inline-flex; align-items:center; gap:6px; background:var(--accent); color:var(--primary-deep); padding:4px 12px; border-radius:20px; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; margin-top:10px; }

/* === MOCKUP DASHBOARD === */
.mockup-frame {
    background:#fff; border:1px solid var(--line); border-radius:20px;
    box-shadow:0 20px 60px rgba(91,46,145,.18); overflow:hidden;
    max-width:1000px; margin:0 auto; position:relative;
}
.mockup-chrome {
    background:#f1f5f9; padding:10px 18px; display:flex; align-items:center; gap:10px;
    border-bottom:1px solid var(--line);
}
.mockup-dots { display:flex; gap:6px; }
.mockup-dots span { width:12px; height:12px; border-radius:50%; background:#cbd5e1; }
.mockup-dots span:first-child { background:#ef4444; }
.mockup-dots span:nth-child(2) { background:#f59e0b; }
.mockup-dots span:nth-child(3) { background:#10b981; }
.mockup-url { background:#fff; border-radius:6px; padding:4px 12px; font-size:11px; color:var(--muted); font-family:monospace; flex:1; text-align:center; }
.mockup-body { padding:28px; background:#fafbfc; }
.mockup-title { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.mockup-title h4 { margin:0; font-size:18px; color:var(--primary-deep); }
.mockup-title .tier-chip { background:#fef3c7; color:#92400e; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
.mockup-kpis { display:grid; grid-template-columns:repeat(4, 1fr); gap:14px; margin-bottom:22px; }
@media(max-width:700px) { .mockup-kpis { grid-template-columns:repeat(2, 1fr); } }
.mockup-kpi { background:#fff; border:1px solid var(--line); border-radius:12px; padding:16px; }
.mockup-kpi-lbl { font-size:10px; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:8px; }
.mockup-kpi-val { font-size:22px; font-weight:800; color:var(--primary-deep); line-height:1; }
.mockup-kpi-val.eur { color:var(--success); }
.mockup-kpi-sub { font-size:10px; color:var(--muted); margin-top:4px; }
.mockup-table { width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; border:1px solid var(--line); font-size:12px; }
.mockup-table th { background:#f8fafc; padding:10px 12px; text-align:left; font-size:10px; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; }
.mockup-table td { padding:12px; border-top:1px solid var(--line); }
.mockup-status { display:inline-block; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:700; }
.mockup-status.v { background:#dcfce7; color:#166534; }
.mockup-status.p { background:#fef3c7; color:#92400e; }
.mockup-status.o { background:#dbeafe; color:#1e40af; }
.mockup-overlay {
    position:absolute; bottom:20px; left:50%; transform:translateX(-50%);
    background:rgba(91,46,145,.95); color:#fff; padding:16px 28px; border-radius:14px;
    box-shadow:0 12px 32px rgba(0,0,0,.3); display:flex; gap:14px; align-items:center;
    font-size:14px; backdrop-filter:blur(6px);
}
.mockup-overlay strong { color:var(--accent); }
.mockup-overlay .btn-primary { padding:8px 18px; font-size:13px; margin:0; }

/* === FOOTER === */
footer { background:#1a0f2e; color:rgba(255,255,255,.7); padding:36px 0; font-size:13px; text-align:center; }
footer a { color:var(--accent); }
</style>
</head>
<body>

{{-- ═══ NAV ═══ --}}
@php
    $dlBrandName = $brandName ?? config('brand.name', 'MentorDE');
    $dlBrandLogo = $brandLogoUrl ?? (config('brand.logo_url') ?: config('brand.logo_path') ?: null);
    $dlLogoBg    = $brandLogoBg ?? 'light';
@endphp
<nav class="d-nav">
    <div class="d-nav-inner">
        <a href="/" class="d-logo" aria-label="{{ $dlBrandName }}">
            @if ($dlBrandLogo)
                <span class="d-logo-wrap-{{ $dlLogoBg === 'dark' ? 'dark' : 'light' }}">
                    <img src="{{ $dlBrandLogo }}" alt="{{ $dlBrandName }}">
                </span>
            @else
                mentor<span>de</span>
            @endif
        </a>
        <div class="d-nav-links">
            <a href="#nasil-calisir">Nasıl Çalışır</a>
            <a href="#kazanc-planlari">Kazanç Planları</a>
            <a href="#komisyon">Komisyon</a>
            <a href="#programlar">Programlar</a>
            <a href="#iletisim">İletişim</a>
        </div>
        <a href="https://panel.mentorde.com/register"
           class="d-nav-cta"
           data-track="cta_clicked"
           data-ph-cta-name="nav_register"
           data-ph-location="dealer_landing_nav">Hemen Başla →</a>
    </div>
</nav>

{{-- ═══ HERO ═══ --}}
<section class="hero">
    <div class="container hero-grid">
        <div>
            <span class="hero-badge">🤝 Satış Ortaklığı Programı 2026</span>
            <h1>Satış Ortağımız Olun,<br><em>Birlikte Kazanalım</em></h1>
            <p class="hero-lead">
                Almanya eğitim hayalini olan her aday için €200–€750 arası komisyon kazanın.
                Sıfır yatırım, sıfır risk. Yönlendirmeyi siz yapın — vize, belge ve okul sürecini biz yönetelim.
            </p>
            <div class="hero-ctas">
                <a href="https://panel.mentorde.com/register"
                   class="btn-primary"
                   data-track="cta_clicked"
                   data-ph-cta-name="hero_register"
                   data-ph-location="dealer_landing_hero">
                    🚀 Hemen Hesap Oluştur — 100€ Bonus
                </a>
                <a href="#nasil-calisir"
                   class="btn-ghost"
                   data-track="cta_clicked"
                   data-ph-cta-name="hero_learn"
                   data-ph-location="dealer_landing_hero">
                    Nasıl Çalışır?
                </a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-visual-title">💰 Öğrenci Başına</div>
            <div class="hero-visual-amount">€200—€750</div>
            <div class="hero-visual-sub">KDV hariç, kademenize göre artan komisyon</div>
            <ul class="hero-visual-list">
                <li>100€ Hoş Geldin Bonusu</li>
                <li>15 gün içinde hızlı ödeme</li>
                <li>Vize reddi güvencesi (teselli payı)</li>
                <li>Özel müşteri temsilcisi desteği</li>
                <li>Dealer Paneli ile şeffaf takip</li>
            </ul>
        </div>
    </div>
</section>

{{-- ═══ CANLI SAYAÇLAR ═══ --}}
<section class="live-counters">
    <div class="container">
        <div class="live-head">
            <span class="live-dot"></span>
            Canlı Rakamlar — Şu An
        </div>
        <div class="counters-grid">
            <div class="counter">
                <div class="counter-icon">🤝</div>
                <div class="counter-value" data-counter="sellers">{{ number_format($counters['sellers'] ?? 0, 0, ',', '.') }}</div>
                <div class="counter-label">Aktif Satış Ortağı</div>
            </div>
            <div class="counter">
                <div class="counter-icon">👥</div>
                <div class="counter-value" data-counter="applications">{{ number_format($counters['applications'] ?? 0, 0, ',', '.') }}</div>
                <div class="counter-label">Yönlendirilen Aday</div>
            </div>
            <div class="counter">
                <div class="counter-icon">🎓</div>
                <div class="counter-value" data-counter="students">{{ number_format($counters['students'] ?? 0, 0, ',', '.') }}</div>
                <div class="counter-label">Almanya'da Öğrenci</div>
            </div>
            <div class="counter">
                <div class="counter-icon">💰</div>
                <div class="counter-value" data-counter="commissions_eur">€{{ number_format($counters['commissions_eur'] ?? 0, 0, ',', '.') }}</div>
                <div class="counter-label">Ödenen Komisyon</div>
            </div>
        </div>
        <p style="text-align:center; font-size:12px; color:var(--muted); margin-top:24px;">
            Rakamlar MentorDE platform verisi ve partnerlik geçmişinden gelir — her etkinlik anlık yansır.
        </p>
    </div>
</section>

{{-- ═══ BİZ KİMİZ ═══ --}}
<section class="sec-bg-white">
    <div class="container">
        <span class="sec-label">Kimiz</span>
        <h2 class="sec-title">{{ $dlBrandName }} — Almanya Eğitim Danışmanlığında Uzman Platform</h2>
        <p class="sec-lead">
            Türk öğrencilerin Almanya eğitim yolculuğunda uzman rehberlik sağlıyoruz.
            Başvuru, vize, konaklama ve yerleşim süreçlerinin tamamını profesyonel ekibimizle
            sorunsuz yönetiyoruz — siz sadece adayınızı tanıtın, biz sürecin tamamını üstlenelim.
        </p>
        <div class="benefits">
            <div class="benefit">
                <div class="benefit-icon">🎓</div>
                <div><h3>Üniversite & Dil Okulu Başvuruları</h3>
                <p>Almanya devlet/özel üniversite + dil okulu + şartlı kabul başvuru süreçleri.</p></div>
            </div>
            <div class="benefit">
                <div class="benefit-icon">🛂</div>
                <div><h3>Profesyonel Vize Danışmanlığı</h3>
                <p>Randevu, dosya hazırlama, mülakat hazırlığı ve süreç takibinin tamamı.</p></div>
            </div>
            <div class="benefit">
                <div class="benefit-icon">💳</div>
                <div><h3>Bloke Hesap & Sağlık Sigortası</h3>
                <p>Sperrkonto ve Krankenversicherung işlemlerinin resmi partnerler üzerinden kurulumu.</p></div>
            </div>
            <div class="benefit">
                <div class="benefit-icon">🏠</div>
                <div><h3>Konaklama & Yerleşim Desteği</h3>
                <p>Wohnung/Wohnheim araştırma, Anmeldung ve günlük yaşam rehberliği.</p></div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ 3 ADIM ═══ --}}
<section id="nasil-calisir" class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">Nasıl Çalışır</span>
        <h2 class="sec-title">Adım Adım Kazanma Yolculuğunuz</h2>
        <p class="sec-lead">Hemen başlayın, 100€ bonus kazanın — sonrası kendiliğinden gelişir.</p>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-icon">📝</div>
                <h3>Hesabınızı Oluşturun</h3>
                <p><a href="https://panel.mentorde.com/register" data-track="cta_clicked" data-ph-cta-name="step_register">panel.mentorde.com</a> adresinden ücretsiz kaydınızı tamamlayın. <strong>100€ Hoş Geldin Bonusu</strong> anında hesabınıza tanımlansın.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-icon">👥</div>
                <h3>İlk Adayınızı Ekleyin</h3>
                <p>Almanya hedefi olan potansiyel öğrencinizin iletişim bilgilerini panele girin. Hepsi bu kadar.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-icon">💸</div>
                <h3>Satış Gerçekleşsin, Kazanın</h3>
                <p>Adayın satışı ve ödemesi tamamlandığında hem <strong>komisyonunuzu</strong> hem de aktifleşen <strong>100€ bonusunuzu</strong> nakit olarak alın.</p>
            </div>
        </div>
    </div>
</section>

{{-- ═══ NEDEN ═══ --}}
<section class="sec-bg-white">
    <div class="container">
        <span class="sec-label">Avantajlar</span>
        <h2 class="sec-title">Neden Satış Ortağımız Olmalısınız?</h2>
        <p class="sec-lead">Geleneksel iş modellerinin hiçbir yükünü üstlenmeden, sadece tanıdıklarınızı yönlendirerek gelir elde edin.</p>
        <div class="benefits">
            <div class="benefit">
                <div class="benefit-icon">💼</div>
                <div><h3>Sıfır Risk, Sıfır Yatırım</h3>
                <p>Hiçbir sermaye koymadan, sadece çevrenizdeki potansiyeli değerlendirerek gelir elde edin.</p></div>
            </div>
            <div class="benefit">
                <div class="benefit-icon">💶</div>
                <div><h3>Euro (€) ile Kazanç</h3>
                <p>Yönlendirdiğiniz ve başarılı kayıt olan her aday için döviz bazlı yüksek komisyon.</p></div>
            </div>
            <div class="benefit">
                <div class="benefit-icon">⚙️</div>
                <div><h3>Operasyonel Rahatlık</h3>
                <p>Evrak, başvuru ve vize stresi yok — tüm zorlu süreci profesyonel destek ekibimiz yönetir.</p></div>
            </div>
            <div class="benefit">
                <div class="benefit-icon">🎧</div>
                <div><h3>Size Özel Kesintisiz Destek</h3>
                <p>Size özel atanan müşteri temsilciniz ile tüm sorularınıza anında yanıt, operasyon ortak yönetilir.</p></div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ KAZANÇ PLANLARI ═══ --}}
<section id="kazanc-planlari" class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">2 Ayrı Model</span>
        <h2 class="sec-title">Kendi Kazanç Planınızı Seçin</h2>
        <p class="sec-lead">
            Zamanınıza, network'ünüze ve uzmanlığınıza en uygun yolu seçin.
            İster sadece yönlendirin, ister sürecin tam kalbinde yer alın.
        </p>
        <div class="plans">
            <div class="plan">
                <div class="plan-title">🤝 Lead Generation</div>
                <div class="plan-sub">Hızlı ve Kolay Kazanç</div>
                <div class="plan-hook">"Siz sadece tavsiye edin, satışı bize bırakın."</div>

                <h4>Kimler İçin?</h4>
                <ul>
                    <li>Ek gelir isteyenler</li>
                    <li>Sosyal medya influencer'ları</li>
                    <li>Geniş çevresi olan herkes</li>
                </ul>

                <h4>Nasıl Çalışır?</h4>
                <ul>
                    <li>Öğrenci iletişim bilgilerini panele girersiniz</li>
                    <li>{{ $dlBrandName }} adayı arar, teknik bilgi verir ve satışı kapatır</li>
                    <li>Operasyonel hiçbir sürece karışmadan hak edişinizi alırsınız</li>
                </ul>

                <a href="#komisyon-lead" class="btn-ghost" style="margin-top:8px; padding:10px 18px; font-size:13px;">Komisyon Tablosunu Gör →</a>
            </div>

            <div class="plan featured">
                <span class="plan-badge">Yüksek Gelir</span>
                <div class="plan-title">🎯 Freelance Danışmanlık</div>
                <div class="plan-sub">Yüksek Gelir Odaklı</div>
                <div class="plan-hook">"Süreci siz başlatın, kazancınızı katlayın."</div>

                <h4>Kimler İçin?</h4>
                <ul>
                    <li>Eğitim sektöründe tecrübeli çözüm ortakları</li>
                    <li>Adaylarla ön görüşme yapabilenler</li>
                    <li>Süreci başlatıp ortak yönetmek isteyenler</li>
                </ul>

                <h4>Nasıl Çalışır?</h4>
                <ul>
                    <li>Adaya okul sunumları + maliyet analizini siz yaparsınız</li>
                    <li>Karar aşamasında {{ $dlBrandName }} ile ortak toplantı düzenlersiniz</li>
                    <li>Resmi kayıt sonrası vize/okul başvuru süreçlerini biz devralırız</li>
                </ul>

                <a href="#komisyon-freelance" class="btn-ghost" style="margin-top:8px; padding:10px 18px; font-size:13px;">Komisyon Tablosunu Gör →</a>
            </div>
        </div>
    </div>
</section>

{{-- ═══ KOMİSYON TABLOLARI ═══ --}}
<section id="komisyon" class="sec-bg-white">
    <div class="container">
        <span class="sec-label">Şeffaf Komisyon</span>
        <h2 class="sec-title">Üniversite Başvuruları için Komisyon</h2>
        <p class="sec-lead">
            Yıllık kayıt sayınız arttıkça kademeniz yükselir, komisyonunuz katlanır.
            Her program türü (dil okulu, vize, Ausbildung) için ayrı tarife uygulanır.
        </p>

        {{-- Lead Generation Table --}}
        <div id="komisyon-lead" style="margin-bottom:56px;">
            <h3 style="color:var(--primary-deep); font-size:22px; margin:0 0 16px; font-family:'DM Serif Display', serif;">
                🤝 Lead Generation — Komisyon Kademeleri
            </h3>
            <table class="ctable">
                <thead>
                    <tr>
                        <th>Seviye</th>
                        <th>Yıllık Kayıt</th>
                        <th>Komisyon / Öğrenci (KDV Hariç)</th>
                        <th>Avantajlar</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>🥉 <strong>Bronz</strong></td>
                        <td>1 — 10</td>
                        <td class="amount">€200</td>
                        <td>Standart komisyon + Dealer Paneli erişimi</td>
                    </tr>
                    <tr>
                        <td>🥈 <strong>Gümüş</strong></td>
                        <td>11 — 25</td>
                        <td class="amount">€250</td>
                        <td>Artırılmış komisyon + Öncelikli destek</td>
                    </tr>
                    <tr>
                        <td>🥇 <strong>Altın</strong></td>
                        <td>26 — 50</td>
                        <td class="amount">€300</td>
                        <td>Yüksek komisyon + Ortak pazarlama desteği</td>
                    </tr>
                    <tr>
                        <td>💎 <strong>Platin</strong></td>
                        <td>51 — 100</td>
                        <td class="amount">€320</td>
                        <td>Premium komisyon + Özel müşteri temsilcisi</td>
                    </tr>
                    <tr>
                        <td>👑 <strong>Elmas</strong></td>
                        <td>101+</td>
                        <td class="amount">€400</td>
                        <td>En yüksek komisyon + Stratejik ortaklık toplantıları</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Freelance Table --}}
        <div id="komisyon-freelance">
            <h3 style="color:var(--primary-deep); font-size:22px; margin:0 0 16px; font-family:'DM Serif Display', serif;">
                🎯 Freelance Danışmanlık — Komisyon Kademeleri
            </h3>
            <table class="ctable">
                <thead>
                    <tr>
                        <th>Seviye</th>
                        <th>Yıllık Kayıt</th>
                        <th>Komisyon / Öğrenci (KDV Hariç)</th>
                        <th>Avantajlar</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>🚀 <strong>Aktif</strong></td>
                        <td>1 — 15</td>
                        <td class="amount">€500</td>
                        <td>Başlangıç komisyonu + Dealer Paneli + Temel süreç eğitimi</td>
                    </tr>
                    <tr>
                        <td>⭐ <strong>Uzman</strong></td>
                        <td>16 — 30</td>
                        <td class="amount">€600</td>
                        <td>Artırılmış komisyon + Öncelikli operasyon/vize inceleme desteği</td>
                    </tr>
                    <tr>
                        <td>🏆 <strong>Elit</strong></td>
                        <td>31+</td>
                        <td class="amount">€750</td>
                        <td>Yüksek komisyon + Co-branding ortak pazarlama desteği</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p style="font-size:13px; color:var(--muted); margin-top:24px; padding:14px 18px; background:var(--primary-soft); border-radius:10px; border-left:3px solid var(--primary);">
            <strong>Not:</strong> Diğer yönlendirebileceğiniz programların (dil okulu, vize danışmanlığı, Ausbildung vb.) hak edişleri seçilen program türüne göre değişiklik göstermektedir. Detaylar için temsilcinize ulaşın.
        </p>
    </div>
</section>

{{-- ═══ PROGRAMLAR ═══ --}}
<section id="programlar" class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">Yönlendirebileceğiniz</span>
        <h2 class="sec-title">6 Ayrı Program — Sınırsız Kazanç Fırsatı</h2>
        <p class="sec-lead">Öğrenci profiline göre en uygun programa yönlendirin, her biri için ayrı komisyon kazanın.</p>
        <div class="programs">
            <div class="program">
                <div class="program-icon">🎓</div>
                <h3>Üniversite Başvuruları</h3>
                <p>Almanya devlet ve özel üniversitelerine lisans/yüksek lisans başvuruları.</p>
            </div>
            <div class="program">
                <div class="program-icon">🗣️</div>
                <h3>Dil Okulları</h3>
                <p>Almanya'da İngilizce ve Almanca dil eğitimleri (A1—C2).</p>
            </div>
            <div class="program">
                <div class="program-icon">🛂</div>
                <h3>Vize Danışmanlığı</h3>
                <p>Profesyonel vize başvuru süreçleri — randevu, dosya, mülakat.</p>
            </div>
            <div class="program">
                <div class="program-icon">☀️</div>
                <h3>Yaz Okulları</h3>
                <p>Gençler için Almanya yaz programları ve kültür deneyimi.</p>
            </div>
            <div class="program">
                <div class="program-icon">🛠️</div>
                <h3>Ausbildung</h3>
                <p>Mesleki eğitim ve staj programları — maaşlı öğrenim modeli.</p>
            </div>
            <div class="program">
                <div class="program-icon">📚</div>
                <h3>Studienkolleg</h3>
                <p>Üniversite hazırlık ve denklik eğitimleri.</p>
            </div>
        </div>
    </div>
</section>

{{-- ═══ PANEL ═══ --}}
<section class="sec-bg-white">
    <div class="container">
        <span class="sec-label">Dealer Paneli</span>
        <h2 class="sec-title">Tüm Süreç ve Kazancınız Tek Ekranda</h2>
        <p class="sec-lead">
            Yönlendirdiğiniz her aday için anlık süreç takibi, şeffaf kazanç ekranı ve ücretsiz pazarlama materyalleri — {{ $dlBrandName }} Dealer Paneli.
        </p>
        <div class="features">
            <ul class="feature-list">
                <li>
                    <div class="feature-icon">📊</div>
                    <div>
                        <strong>Anlık Süreç Takibi</strong>
                        <span>Yönlendirdiğiniz öğrencinin hangi aşamada (kabul bekliyor, vize onaylandı vb.) olduğunu canlı izleyin.</span>
                    </div>
                </li>
                <li>
                    <div class="feature-icon">💰</div>
                    <div>
                        <strong>Şeffaf Kazanç Ekranı</strong>
                        <span>Hak ettiğiniz, bekleyen ve ödenen komisyon tutarlarınız tek ekranda görünür.</span>
                    </div>
                </li>
                <li>
                    <div class="feature-icon">📦</div>
                    <div>
                        <strong>Ücretsiz Materyal Desteği</strong>
                        <span>Satışı kolaylaştıracak güncel katalog, fiyat listesi ve sosyal medya görselleri tek tıkla.</span>
                    </div>
                </li>
                <li>
                    <div class="feature-icon">✨</div>
                    <div>
                        <strong>Kullanıcı Dostu Arayüz</strong>
                        <span>Hiçbir teknik bilgi gerektirmeyen, anlaşılır menülerle saniyeler içinde işlem.</span>
                    </div>
                </li>
            </ul>
            <div class="feature-visual">
                <div style="font-size:56px; margin-bottom:18px;">🖥️📱</div>
                <h3 style="color:var(--primary-deep); margin:0 0 10px;">Web & Mobil Uyumlu</h3>
                <p style="color:var(--muted); font-size:14px; margin:0 0 20px;">Masaüstü, tablet, telefon — her cihazda eksiksiz çalışır. İstediğiniz yerden adaylarınızı takip edin.</p>
                <a href="https://panel.mentorde.com/register"
                   class="btn-primary"
                   style="font-size:14px; padding:12px 24px;"
                   data-track="cta_clicked"
                   data-ph-cta-name="panel_register"
                   data-ph-location="dealer_landing_panel">
                    Panel'i İnceleyin →
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ═══ GÜVENCELER ═══ --}}
<section class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">Ödeme & Güvence</span>
        <h2 class="sec-title">Emeğinizin Karşılığı Garanti</h2>
        <p class="sec-lead">Süreç olumsuz sonuçlansa bile çabanız boşa gitmez. Size iki güvenli sözümüz var.</p>
        <div class="guarantees">
            <div class="guarantee">
                <div class="guarantee-icon">💳</div>
                <div>
                    <h3>Hızlı ve Esnek Ödeme Sistemi</h3>
                    <p>Öğrenci kayıt işlemini tamamladığında komisyonunuz kesinleşir, <strong>en geç 15 gün içinde</strong> hesabınıza yatar. Şirketiniz varsa fatura keserek, bireysel çalışıyorsanız basit yasal süreçlerle anında tahsilat.</p>
                </div>
            </div>
            <div class="guarantee">
                <div class="guarantee-icon">🛡️</div>
                <div>
                    <h3>Vize Reddi Güvencesi</h3>
                    <p>Süreç olumsuz sonuçlansa bile harcadığınız zaman değerlidir. Öğrenci vize reddi alsa dahi emekleriniz boşa gitmez — <strong>kademenize göre belirlenen teselli payı</strong> anında hesabınıza yatar.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ KAZANÇ HESAPLAYICI ═══ --}}
<section class="sec-bg-white">
    <div class="container">
        <span class="sec-label">Kaç Para Kazanırım?</span>
        <h2 class="sec-title">Kendi Kazancınızı Hesaplayın</h2>
        <p class="sec-lead">Yıllık kaç aday yönlendireceğinizi girin — plan ve kademenize göre tahmini yıllık gelirinizi anında görün.</p>

        <div class="calc-wrap">
            <div class="calc-form">
                <h3>📊 Hesaplama Formu</h3>

                <div class="calc-field">
                    <label>Kazanç Modeli</label>
                    <div class="calc-toggle" id="calc-plan-toggle">
                        <button type="button" data-plan="lead" class="active">🤝 Lead Generation</button>
                        <button type="button" data-plan="freelance">🎯 Freelance</button>
                    </div>
                </div>

                <div class="calc-field">
                    <label>Yıllık Üniversite Başvurusu Kayıt</label>
                    <div class="row">
                        <input type="range" id="calc-uni" min="1" max="150" value="10" step="1">
                        <input type="number" id="calc-uni-num" min="1" max="500" value="10">
                    </div>
                </div>

                <div class="calc-field">
                    <label>Yıllık Dil Okulu Kayıt <span style="color:var(--muted); font-weight:normal;">(~€100/kayıt)</span></label>
                    <div class="row">
                        <input type="range" id="calc-lang" min="0" max="100" value="5" step="1">
                        <input type="number" id="calc-lang-num" min="0" max="300" value="5">
                    </div>
                </div>

                <div class="calc-field">
                    <label>Yıllık Vize Danışmanlığı Yönlendirme <span style="color:var(--muted); font-weight:normal;">(~€75/kayıt)</span></label>
                    <div class="row">
                        <input type="range" id="calc-visa" min="0" max="100" value="3" step="1">
                        <input type="number" id="calc-visa-num" min="0" max="300" value="3">
                    </div>
                </div>
            </div>

            <div class="calc-result">
                <div class="calc-result-label">💰 Yıllık Tahmini Kazanç</div>
                <div class="calc-result-amount" id="calc-annual">€2.900</div>
                <div class="calc-result-sub">KDV hariç, ödenen komisyon (vize reddi teselli payı hariç)</div>

                <div class="calc-tier-badge" id="calc-tier">🥉 Bronz Seviye · €200/kayıt</div>

                <div class="calc-breakdown">
                    <div class="calc-breakdown-row">
                        <span>🎓 Üniversite (<span id="calc-uni-show">10</span> kayıt × <span id="calc-rate">€200</span>)</span>
                        <span id="calc-uni-total">€2.000</span>
                    </div>
                    <div class="calc-breakdown-row">
                        <span>🗣️ Dil Okulu (<span id="calc-lang-show">5</span> × €100)</span>
                        <span id="calc-lang-total">€500</span>
                    </div>
                    <div class="calc-breakdown-row">
                        <span>🛂 Vize (<span id="calc-visa-show">3</span> × €75)</span>
                        <span id="calc-visa-total">€225</span>
                    </div>
                    <div class="calc-breakdown-row">
                        <span>🎁 Hoş Geldin Bonusu</span>
                        <span>€100</span>
                    </div>
                    <div class="calc-breakdown-row">
                        <span>TOPLAM YILLIK</span>
                        <span id="calc-total">€2.825</span>
                    </div>
                </div>

                <div style="margin-top:20px; font-size:12px; opacity:.75;">
                    📌 Aylık ortalama: <strong id="calc-monthly" style="color:var(--accent);">€235</strong> ·
                    Kademeniz otomatik yükseldikçe oran artar.
                </div>
            </div>
        </div>

        <div style="text-align:center; margin-top:32px;">
            <a href="https://panel.mentorde.com/register"
               class="btn-primary"
               data-track="cta_clicked"
               data-ph-cta-name="calc_register"
               data-ph-location="dealer_landing_calc">
                🚀 Bu Kazançları Hedeflemek İçin Kaydolun
            </a>
        </div>
    </div>
</section>

{{-- ═══ PANEL ÖNİZLEME (MOCKUP) ═══ --}}
<section class="sec-bg-soft">
    <div class="container">
        <span class="sec-label">Panel Önizleme</span>
        <h2 class="sec-title">Kayıt Olmadan İçeriyi Görün</h2>
        <p class="sec-lead">
            Dealer paneliniz tam olarak böyle görünür. Yönlendirdiğiniz her aday için anlık süreç takibi, şeffaf komisyon hesabı ve kolay yönetim.
        </p>

        <div class="mockup-frame">
            <div class="mockup-chrome">
                <div class="mockup-dots"><span></span><span></span><span></span></div>
                <div class="mockup-url">🔒 panel.mentorde.com/dealer/dashboard</div>
            </div>
            <div class="mockup-body">
                <div class="mockup-title">
                    <h4>👋 Merhaba, Dealer! — Ocak 2026 Özeti</h4>
                    <span class="tier-chip">🥈 Gümüş Kademe</span>
                </div>

                <div class="mockup-kpis">
                    <div class="mockup-kpi">
                        <div class="mockup-kpi-lbl">Toplam Yönlendirme</div>
                        <div class="mockup-kpi-val">18</div>
                        <div class="mockup-kpi-sub">Bu yıl</div>
                    </div>
                    <div class="mockup-kpi">
                        <div class="mockup-kpi-lbl">Aktif Aday</div>
                        <div class="mockup-kpi-val">7</div>
                        <div class="mockup-kpi-sub">Süreçte</div>
                    </div>
                    <div class="mockup-kpi">
                        <div class="mockup-kpi-lbl">Onaylanan Komisyon</div>
                        <div class="mockup-kpi-val eur">€4.350</div>
                        <div class="mockup-kpi-sub">Ödendi / ödenecek</div>
                    </div>
                    <div class="mockup-kpi">
                        <div class="mockup-kpi-lbl">Bekleyen</div>
                        <div class="mockup-kpi-val">€1.750</div>
                        <div class="mockup-kpi-sub">Onay aşamasında</div>
                    </div>
                </div>

                <table class="mockup-table">
                    <thead>
                        <tr>
                            <th>Aday</th>
                            <th>Program</th>
                            <th>Aşama</th>
                            <th style="text-align:right;">Komisyon</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Ali K.</strong><br><span style="color:var(--muted); font-size:10px;">ali.k@demo</span></td>
                            <td>TU München — MSc</td>
                            <td>🎯 Vize Onaylandı</td>
                            <td style="text-align:right; font-weight:700; color:var(--success);">€250</td>
                            <td><span class="mockup-status v">Ödendi</span></td>
                        </tr>
                        <tr>
                            <td><strong>Zeynep D.</strong><br><span style="color:var(--muted); font-size:10px;">zeynep.d@demo</span></td>
                            <td>Almanca B2 Kursu</td>
                            <td>📝 Sözleşme İmzalandı</td>
                            <td style="text-align:right; font-weight:700; color:var(--muted);">€100</td>
                            <td><span class="mockup-status p">İşlemde</span></td>
                        </tr>
                        <tr>
                            <td><strong>Mert A.</strong><br><span style="color:var(--muted); font-size:10px;">mert.a@demo</span></td>
                            <td>RWTH Aachen — BSc</td>
                            <td>📄 Belgeler İnceleniyor</td>
                            <td style="text-align:right; font-weight:700; color:var(--muted);">€250</td>
                            <td><span class="mockup-status o">Devam</span></td>
                        </tr>
                        <tr>
                            <td><strong>Ayşe S.</strong><br><span style="color:var(--muted); font-size:10px;">ayse.s@demo</span></td>
                            <td>Vize Danışmanlığı</td>
                            <td>🛂 Randevu Alındı</td>
                            <td style="text-align:right; font-weight:700; color:var(--muted);">€75</td>
                            <td><span class="mockup-status o">Devam</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mockup-overlay">
                <span>Kendi dashboardunu görmek istiyor musun? <strong>100€ bonus</strong> ile başla.</span>
                <a href="https://panel.mentorde.com/register"
                   class="btn-primary"
                   data-track="cta_clicked"
                   data-ph-cta-name="mockup_register"
                   data-ph-location="dealer_landing_mockup">Hemen Kaydol →</a>
            </div>
        </div>
    </div>
</section>

{{-- ═══ CTA ═══ --}}
<section id="iletisim" class="cta-section">
    <div class="container">
        <h2>Hemen Hesabınızı Oluşturun<br>ve Kazanmaya Başlayın</h2>
        <p>Almanya eğitim fırsatlarını çevrenizle buluşturun, birlikte kazanalım.</p>
        <a href="https://panel.mentorde.com/register"
           class="btn-primary"
           data-track="cta_clicked"
           data-ph-cta-name="footer_register"
           data-ph-location="dealer_landing_cta">
            🎯 Ücretsiz Kayıt Ol — 100€ Bonus
        </a>

        <div class="cta-contacts">
            <div class="cta-contact">
                🌐 <a href="https://panel.mentorde.com" target="_blank" rel="noopener"
                      data-track="cta_clicked" data-ph-cta-name="contact_panel" data-ph-location="dealer_landing_contact">panel.mentorde.com</a>
            </div>
            <div class="cta-contact">
                ✉️ <a href="mailto:info@mentorde.com"
                      data-track="cta_clicked" data-ph-cta-name="contact_email" data-ph-location="dealer_landing_contact">info@mentorde.com</a>
            </div>
            <div class="cta-contact">
                💬 <a href="https://wa.me/4915203253691?text=Merhaba%2C%20Sat%C4%B1%C5%9F%20Orta%C4%9Fl%C4%B1%C4%9F%C4%B1%20Program%C4%B1%20hakk%C4%B1nda%20bilgi%20almak%20istiyorum."
                      target="_blank" rel="noopener"
                      data-track="cta_clicked" data-ph-cta-name="contact_whatsapp" data-ph-location="dealer_landing_contact">WhatsApp: +49 1520 325 3691</a>
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="container">
        © {{ date('Y') }} {{ $dlBrandName }} · Almanya eğitim danışmanlığında uzman platform ·
        <a href="/legal/terms">Kullanım Koşulları</a> ·
        <a href="/legal/privacy">Gizlilik</a>
    </div>
</footer>

{{-- Canlı Sayaç — pseudo-live increment (config'ten) --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function() {
    const LIVE_CFG = @json($counters['live'] ?? ['interval_ms' => 25000, 'ranges' => []]);
    const state = {
        sellers: {{ (int) ($counters['sellers'] ?? 0) }},
        applications: {{ (int) ($counters['applications'] ?? 0) }},
        students: {{ (int) ($counters['students'] ?? 0) }},
        commissions_eur: {{ (int) ($counters['commissions_eur'] ?? 0) }},
    };

    function formatNumber(n, prefix) {
        const s = Math.round(n).toLocaleString('tr-TR').replace(/,/g, '.');
        return (prefix || '') + s;
    }

    function randInRange(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    function flash(el) {
        el.classList.add('flash');
        setTimeout(() => el.classList.remove('flash'), 500);
    }

    function animate(el, from, to, prefix) {
        const duration = 800;
        const start = performance.now();
        function step(now) {
            const t = Math.min(1, (now - start) / duration);
            const easedT = 1 - Math.pow(1 - t, 3);
            const current = from + (to - from) * easedT;
            el.textContent = formatNumber(current, prefix);
            if (t < 1) requestAnimationFrame(step);
            else el.textContent = formatNumber(to, prefix);
        }
        requestAnimationFrame(step);
    }

    function tick() {
        const ranges = LIVE_CFG.ranges || {};
        Object.keys(ranges).forEach(key => {
            const [min, max] = ranges[key] || [0, 0];
            if (max <= 0) return;
            const delta = randInRange(min, max);
            if (delta === 0) return;

            const el = document.querySelector(`[data-counter="${key}"]`);
            if (!el) return;

            const prefix = key === 'commissions_eur' ? '€' : '';
            const from = state[key];
            const to = from + delta;
            state[key] = to;
            animate(el, from, to, prefix);
            flash(el);
        });
    }

    // Sekme görünürken çalışsın, arka plandayken durdur (performans)
    let timer = null;
    function start() { if (!timer) timer = setInterval(tick, LIVE_CFG.interval_ms || 25000); }
    function stop()  { if (timer) { clearInterval(timer); timer = null; } }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stop(); else start();
    });

    // İlk artışı hemen 3-5 sn sonra başlat (sayfa yüklenir yüklenmez artma hissi)
    setTimeout(() => { tick(); start(); }, 3500);
})();
</script>

{{-- Kazanç Hesaplayıcı JavaScript --}}
<script nonce="{{ $cspNonce ?? '' }}">
(function() {
    // Komisyon tablosu (PDF'ten)
    const TIERS_LEAD = [
        { min: 1,   max: 10,  rate: 200, name: '🥉 Bronz' },
        { min: 11,  max: 25,  rate: 250, name: '🥈 Gümüş' },
        { min: 26,  max: 50,  rate: 300, name: '🥇 Altın' },
        { min: 51,  max: 100, rate: 320, name: '💎 Platin' },
        { min: 101, max: 9999, rate: 400, name: '👑 Elmas' },
    ];
    const TIERS_FREELANCE = [
        { min: 1,  max: 15,  rate: 500, name: '🚀 Aktif' },
        { min: 16, max: 30,  rate: 600, name: '⭐ Uzman' },
        { min: 31, max: 9999, rate: 750, name: '🏆 Elit' },
    ];

    const LANG_RATE = 100; // Dil okulu sabit tahmin
    const VISA_RATE = 75;  // Vize danışmanlığı sabit tahmin
    const WELCOME_BONUS = 100;

    const state = {
        plan: 'lead',
        uni: 10,
        lang: 5,
        visa: 3,
    };

    const $ = (id) => document.getElementById(id);

    function getTier() {
        const tiers = state.plan === 'lead' ? TIERS_LEAD : TIERS_FREELANCE;
        return tiers.find(t => state.uni >= t.min && state.uni <= t.max) || tiers[0];
    }

    function formatEur(n) {
        return '€' + Math.round(n).toLocaleString('tr-TR');
    }

    function recalc() {
        const tier = getTier();
        const uniTotal = state.uni * tier.rate;
        const langTotal = state.lang * LANG_RATE;
        const visaTotal = state.visa * VISA_RATE;
        const total = uniTotal + langTotal + visaTotal + WELCOME_BONUS;

        $('calc-annual').textContent = formatEur(total);
        $('calc-total').textContent = formatEur(total);
        $('calc-tier').innerHTML = tier.name + ' Seviye · €' + tier.rate + '/kayıt';
        $('calc-rate').textContent = '€' + tier.rate;
        $('calc-uni-show').textContent = state.uni;
        $('calc-lang-show').textContent = state.lang;
        $('calc-visa-show').textContent = state.visa;
        $('calc-uni-total').textContent = formatEur(uniTotal);
        $('calc-lang-total').textContent = formatEur(langTotal);
        $('calc-visa-total').textContent = formatEur(visaTotal);
        $('calc-monthly').textContent = formatEur(total / 12);
    }

    function bindSlider(sliderId, numId, key) {
        const slider = $(sliderId);
        const num = $(numId);
        slider.addEventListener('input', (e) => {
            state[key] = parseInt(e.target.value) || 0;
            num.value = state[key];
            recalc();
        });
        num.addEventListener('input', (e) => {
            const val = Math.max(0, Math.min(parseInt(slider.max), parseInt(e.target.value) || 0));
            state[key] = val;
            slider.value = val;
            recalc();
        });
    }

    // Plan toggle
    document.querySelectorAll('#calc-plan-toggle button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#calc-plan-toggle button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            state.plan = btn.dataset.plan;
            recalc();

            // PostHog event
            if (window.posthog) {
                window.posthog.capture('calc_plan_changed', { plan: state.plan });
            }
        });
    });

    bindSlider('calc-uni',  'calc-uni-num',  'uni');
    bindSlider('calc-lang', 'calc-lang-num', 'lang');
    bindSlider('calc-visa', 'calc-visa-num', 'visa');

    recalc();
})();
</script>

{{-- Analytics: PostHog snippet (consent varsa) + Consent banner --}}
<x-analytics.posthog-snippet :portal="'public'" />
<x-analytics.consent-banner />

</body>
</html>
