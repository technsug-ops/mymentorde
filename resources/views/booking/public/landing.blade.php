<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Al — Yurt Dışı Eğitim Danışmanları · {{ $brandName ?? 'MentorDE' }}</title>
    <meta name="description" content="Almanya yurt dışı eğitim başvurunuz için uzman danışmanlarla birebir görüşme planlayın. Üniversite seçimi, belge süreci, vize ve daha fazlası.">

    <link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            /* MentörDE Brand Palette (brandbook 2026-01-26) */
            --primary:#7e58bf;
            --primary-dark:#6c47a8;
            --primary-deep:#5a3a8d;
            --primary-mid:#a07ed9;
            --primary-light:#b79ae9;
            --primary-soft:#efe9fb;
            --neutral:#e9e7e2;
            --neutral-soft:#faf9f5;
            --accent:#7e58bf;
            --accent-dark:#6c47a8;
            --text:#1a1325;
            --muted:#6b6377;
            --line:#e3dcec;
            --surface:#ffffff;
            --bg:#faf9f5;
            --success-bg:#e8f5ed;
            --success-text:#2d8b55;
            --font-base:"Space Grotesk", var(--font-base);
        }
        * { box-sizing:border-box; }
        html, body { margin:0; padding:0; }
        body {
            font-family:var(--font-base);
            color:var(--text);
            background:linear-gradient(140deg, #f7f3ff 0%, #faf9f5 50%, #e9e7e2 100%);
            line-height:1.6;
            font-size:15px;
            -webkit-font-smoothing:antialiased;
            font-feature-settings:"ss01", "ss02";
        }
        .serif { font-family:var(--font-base); font-weight:600; font-style:italic; }
        a { color:var(--primary); text-decoration:none; }
        a:hover { text-decoration:underline; }

        /* === NAV === */
        .l-nav {
            position:sticky; top:0; z-index:50;
            background:rgba(255,255,255,.92); backdrop-filter:blur(10px);
            border-bottom:1px solid var(--line);
        }
        .l-nav-inner { max-width:1180px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; padding:14px 22px; gap:16px; }
        .l-logo { font-family:var(--font-base); font-size:28px; color:var(--primary); letter-spacing:-.5px; line-height:1; display:inline-flex; align-items:center; gap:2px; }
        .l-logo span { color:var(--primary-dark); font-style:italic; }
        .l-logo img { height:36px; width:auto; max-width:180px; }
        .l-nav-links { display:flex; gap:24px; font-size:14px; font-weight:600; }
        .l-nav-links a { color:var(--muted); }
        .l-nav-links a:hover { color:var(--primary); text-decoration:none; }
        .l-nav-cta {
            padding:10px 18px; background:var(--primary); color:#fff !important;
            border-radius:10px; font-size:13px; font-weight:700;
        }
        .l-nav-cta:hover { background:var(--primary-dark); text-decoration:none !important; }
        @media(max-width:720px){ .l-nav-links { display:none; } }

        /* === HERO — 2 column === */
        .l-hero { padding:60px 22px 80px; position:relative; }
        .l-hero-inner { max-width:1180px; margin:0 auto; display:grid; grid-template-columns:1.1fr .9fr; gap:48px; align-items:center; }
        @media(max-width:900px){ .l-hero-inner { grid-template-columns:1fr; gap:36px; } }
        .l-hero-badge {
            display:inline-flex; align-items:center; gap:6px;
            background:var(--success-bg); color:var(--success-text);
            padding:6px 14px; border-radius:999px;
            font-size:12px; font-weight:700; margin-bottom:20px;
        }
        .l-hero h1 {
            font-family:var(--font-base); font-weight:normal;
            font-size:44px; line-height:1.08; margin:0 0 18px; color:var(--text);
        }
        .l-hero h1 em { color:var(--primary); font-style:italic; }
        .l-hero-sub {
            font-size:17px; color:var(--muted);
            margin:0 0 30px; line-height:1.65;
        }
        .l-hero-ctas { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:40px; }
        .l-btn {
            display:inline-flex; align-items:center; gap:8px;
            padding:13px 24px; border-radius:10px;
            font-weight:700; font-size:14px;
            transition:all .18s; cursor:pointer; border:none;
            font-family:inherit; text-decoration:none;
        }
        .l-btn-primary { background:var(--primary); color:#fff !important; }
        .l-btn-primary:hover { background:var(--primary-dark); text-decoration:none !important; }
        .l-btn-accent { background:var(--accent); color:var(--primary-deep) !important; }
        .l-btn-accent:hover { background:var(--accent-dark); text-decoration:none !important; }
        .l-btn-ghost { background:var(--surface); color:var(--text) !important; border:1px solid var(--line); }
        .l-btn-ghost:hover { border-color:var(--primary); color:var(--primary) !important; text-decoration:none !important; }
        .l-hero-trust {
            display:flex; gap:32px; flex-wrap:wrap;
            padding-top:28px; border-top:1px solid var(--line);
        }
        .l-hero-trust > div {
            position:relative; padding-left:14px;
        }
        .l-hero-trust > div::before {
            content:''; position:absolute; left:0; top:4px; bottom:4px; width:3px;
            background:var(--gradient-mid, linear-gradient(180deg, #a07ed9, #7e58bf));
            border-radius:2px;
        }
        .l-trust-num {
            font-family:var(--font-base); font-weight:700;
            font-size:34px; color:var(--primary-deep); line-height:1;
            letter-spacing:-1px;
        }
        .l-trust-lbl { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-top:6px; font-weight:600; }
        @media(max-width:720px){ .l-hero h1 { font-size:32px; } .l-hero-sub { font-size:15px; } .l-hero-trust { gap:20px; } .l-trust-num { font-size:24px; } }

        /* === Hero Right Side: Video/Welcome === */
        .l-hero-media {
            background:var(--surface);
            border:1px solid var(--line);
            border-radius:16px;
            overflow:hidden;
            position:relative;
        }
        .l-hero-media-video {
            position:relative; width:100%; padding-bottom:56.25%;
            background:#000;
        }
        .l-hero-media-video iframe {
            position:absolute; top:0; left:0; width:100%; height:100%; border:0;
        }
        .l-hero-media-close {
            position:absolute; top:8px; right:8px; z-index:3;
            width:32px; height:32px; border-radius:50%;
            background:rgba(0,0,0,.6); color:#fff; border:none;
            display:none; align-items:center; justify-content:center;
            cursor:pointer; font-size:16px; font-weight:700;
            line-height:1; padding:0;
            transition:background .15s;
        }
        .l-hero-media-close:hover { background:rgba(0,0,0,.85); }

        /* === Sticky floating (scroll ile) === */
        .l-hero-media {
            transition:all .35s cubic-bezier(.4,0,.2,1);
        }
        .l-hero-media.is-floating {
            position:fixed;
            bottom:20px; right:20px;
            width:340px; max-width:calc(100vw - 40px);
            z-index:60;
            border:2px solid var(--primary-soft);
            box-shadow:0 12px 40px rgba(91,46,145,.28);
            border-radius:12px;
        }
        .l-hero-media.is-floating .l-hero-media-close { display:flex; }
        .l-hero-media.is-floating .l-hero-welcome,
        .l-hero-media.is-floating .l-hero-video-caption { display:none; }
        .l-hero-media-anchor {
            transition:min-height .35s;
        }
        @media(max-width:520px){
            .l-hero-media.is-floating { width:280px; bottom:12px; right:12px; }
        }

        .l-hero-welcome {
            padding:32px 26px;
            background:linear-gradient(135deg, var(--primary-soft) 0%, #fff 60%);
            border-radius:16px;
        }
        .l-hero-welcome-icon {
            width:52px; height:52px; border-radius:14px;
            background:var(--primary); color:#fff;
            display:flex; align-items:center; justify-content:center;
            font-size:26px; margin-bottom:18px;
        }
        .l-hero-welcome h3 {
            font-family:var(--font-base); font-weight:normal;
            font-size:24px; color:var(--text); margin:0 0 12px; line-height:1.25;
        }
        .l-hero-welcome p {
            font-size:14px; color:var(--muted); line-height:1.7; margin:0 0 10px;
        }
        .l-hero-welcome p:last-child { margin-bottom:0; }
        .l-hero-video-caption {
            padding:14px 20px; background:var(--primary-soft);
            font-size:13px; color:var(--primary-dark); font-weight:600;
        }

        /* === SECTIONS === */
        .l-sec { padding:72px 22px; }
        .l-sec-alt { background:var(--surface); border-top:1px solid var(--line); border-bottom:1px solid var(--line); }
        .l-sec-inner { max-width:1180px; margin:0 auto; }
        .l-sec-head { text-align:center; max-width:700px; margin:0 auto 52px; }
        .l-sec-eyebrow {
            display:inline-block; color:var(--primary);
            font-size:12px; font-weight:800;
            text-transform:uppercase; letter-spacing:.12em; margin-bottom:12px;
        }
        .l-sec-title {
            font-family:var(--font-base); font-weight:normal;
            font-size:36px; color:var(--text);
            margin:0 0 14px; line-height:1.2;
        }
        .l-sec-sub { font-size:16px; color:var(--muted); line-height:1.65; margin:0; }

        /* === BENEFITS — centered flex === */
        .l-benefits {
            display:flex; flex-wrap:wrap;
            justify-content:center; gap:18px;
        }
        .l-benefit {
            flex:0 1 270px; max-width:300px;
            background:var(--surface); border:1px solid var(--line);
            border-radius:14px; padding:26px 22px;
            transition:all .2s;
        }
        .l-benefit:hover { border-color:var(--primary); transform:translateY(-3px); }
        .l-benefit-icon {
            width:48px; height:48px; border-radius:12px;
            background:var(--primary-soft); color:var(--primary);
            display:flex; align-items:center; justify-content:center;
            font-size:24px; margin-bottom:16px;
        }
        .l-benefit h3 { margin:0 0 8px; font-size:16px; font-weight:700; color:var(--text); }
        .l-benefit p { margin:0; font-size:14px; color:var(--muted); line-height:1.65; }

        /* === STEPS — centered flex === */
        .l-steps {
            display:flex; flex-wrap:wrap;
            justify-content:center; gap:24px;
        }
        .l-step {
            flex:0 1 300px; max-width:340px;
            background:var(--surface); border:1px solid var(--line);
            border-radius:14px; padding:32px 24px;
            text-align:center; position:relative; transition:border-color .2s;
        }
        .l-step:hover { border-color:var(--primary); }
        .l-step-num {
            position:absolute; top:-18px; left:50%; transform:translateX(-50%);
            width:42px; height:42px; border-radius:50%;
            background:var(--primary); color:#fff;
            display:flex; align-items:center; justify-content:center;
            font-family:var(--font-base); font-size:20px;
            border:3px solid var(--bg);
        }
        .l-step-icon { font-size:44px; display:block; margin:18px 0 10px; }
        .l-step h3 { margin:0 0 10px; font-size:18px; font-weight:700; color:var(--text); }
        .l-step p { margin:0; font-size:14px; color:var(--muted); line-height:1.65; }

        /* === SENIORS === */
        .l-seniors-bar {
            background:var(--surface); border:1px solid var(--line);
            border-radius:12px; padding:14px;
            margin-bottom:26px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;
        }
        .l-seniors-bar input {
            flex:1; min-width:240px; padding:11px 16px;
            border:1px solid var(--line); border-radius:10px;
            font-size:14px; outline:none; font-family:inherit;
            background:var(--bg); transition:all .15s;
        }
        .l-seniors-bar input:focus { border-color:var(--primary); background:#fff; }
        .l-seniors-grid {
            display:flex; flex-wrap:wrap;
            justify-content:center; gap:18px;
        }
        .l-senior-card {
            flex:0 1 290px; max-width:320px;
            background:var(--surface); border:1px solid var(--line);
            border-radius:14px; padding:24px;
            transition:all .2s; display:flex; flex-direction:column;
        }
        .l-senior-card:hover { transform:translateY(-3px); border-color:var(--primary); }
        .l-senior-avatar {
            width:64px; height:64px; border-radius:50%;
            background:linear-gradient(135deg, var(--primary-soft), #e4d4f5);
            display:flex; align-items:center; justify-content:center;
            font-family:var(--font-base); font-size:26px;
            color:var(--primary); margin-bottom:16px; overflow:hidden;
            border:3px solid #fff;
        }
        .l-senior-avatar img { width:100%; height:100%; object-fit:cover; }
        .l-senior-card h3 { margin:0 0 4px; font-size:17px; font-weight:700; color:var(--text); }
        .l-senior-sub { color:var(--muted); font-size:12px; margin-bottom:14px; }
        .l-senior-bio { font-size:13px; color:#334155; line-height:1.65; margin-bottom:14px; flex:1; }
        .l-senior-tags { display:flex; gap:5px; flex-wrap:wrap; margin-bottom:14px; }
        .l-senior-tag { background:var(--primary-soft); color:var(--primary); padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600; }
        .l-senior-meta { display:flex; gap:14px; font-size:12px; color:var(--muted); margin-bottom:16px; }
        .l-senior-btn {
            display:block; text-align:center;
            padding:11px 16px; background:var(--primary); color:#fff !important;
            border-radius:10px; font-weight:700; font-size:13px;
            transition:background .15s; text-decoration:none;
        }
        .l-senior-btn:hover { background:var(--primary-dark); text-decoration:none !important; }

        .l-empty {
            padding:60px 30px; text-align:center;
            background:var(--surface); border-radius:16px;
            border:1px dashed var(--line);
            max-width:640px; margin:0 auto;
        }
        .l-empty-icon { font-size:54px; margin-bottom:16px; }
        .l-empty h3 { margin:0 0 10px; font-size:22px; color:var(--text); font-family:var(--font-base); font-weight:normal; }
        .l-empty p { margin:0 0 22px; color:var(--muted); font-size:14px; max-width:460px; margin-left:auto; margin-right:auto; line-height:1.65; }

        /* === Track tabs (Bachelor/Master/Diğer) === */
        .l-track-tabs {
            display:grid; grid-template-columns:repeat(3, 1fr); gap:10px;
            margin-bottom:26px;
        }
        @media(max-width:720px) { .l-track-tabs { grid-template-columns:1fr; } }
        .l-track-tab {
            display:flex; align-items:center; gap:14px;
            background:var(--surface); border:2px solid var(--line); border-radius:14px;
            padding:16px 20px; cursor:pointer; font-family:inherit;
            text-align:left; transition:all .15s;
        }
        .l-track-tab:hover { border-color:var(--primary-light); transform:translateY(-2px); }
        .l-track-tab.active {
            border-color:var(--primary);
            background:linear-gradient(140deg, var(--primary-soft) 0%, #fff 100%);
            box-shadow:0 12px 28px rgba(126,88,191,.18);
        }
        .l-track-emoji { font-size:30px; flex-shrink:0; }
        .l-track-text { flex:1; display:flex; flex-direction:column; gap:2px; }
        .l-track-text strong { font-size:15px; font-weight:700; color:var(--primary-deep); letter-spacing:-.3px; }
        .l-track-text small { font-size:11.5px; color:var(--muted); font-weight:600; }
        .l-track-count {
            background:var(--primary); color:#fff;
            min-width:28px; height:28px; border-radius:14px;
            display:inline-flex; align-items:center; justify-content:center;
            font-weight:800; font-size:12.5px; padding:0 8px;
            box-shadow:0 4px 12px rgba(126,88,191,.32);
        }
        .l-track-tab:not(.active) .l-track-count { background:var(--muted); box-shadow:none; opacity:.7; }

        /* === Bekleme listesi formu (lead-quality) === */
        .l-waitlist-card {
            max-width:600px; margin:0 auto;
            background:var(--surface); border:1px solid var(--line);
            border-radius:18px; padding:36px 30px; text-align:center;
            box-shadow:0 12px 32px rgba(126,88,191,.08);
        }
        .l-waitlist-icon { font-size:54px; margin-bottom:14px; }
        .l-waitlist-card h3 {
            font-family:var(--font-base); font-weight:700; font-size:22px;
            color:var(--primary-deep); margin:0 0 10px; letter-spacing:-.4px;
        }
        .l-waitlist-accent {
            background:linear-gradient(180deg, transparent 60%, var(--primary-light) 60%, var(--primary-light) 92%, transparent 92%);
            padding:0 4px;
        }
        .l-waitlist-card p { margin:0 0 26px; color:var(--muted); font-size:14px; line-height:1.6; }
        .l-waitlist-form { text-align:left; }
        .l-waitlist-row { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px; }
        .l-waitlist-row:last-of-type, .l-waitlist-row:has(textarea) { grid-template-columns:1fr; }
        .l-waitlist-form input, .l-waitlist-form textarea {
            width:100%; padding:11px 14px; border:1px solid var(--line); border-radius:10px;
            font-family:inherit; font-size:14px; background:#fff; color:var(--text);
            resize:vertical;
        }
        .l-waitlist-form input:focus, .l-waitlist-form textarea:focus {
            outline:none; border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-soft);
        }
        .l-waitlist-form button { margin-top:6px; padding:14px 22px; font-size:14.5px; }
        .l-waitlist-fineprint {
            margin-top:14px; font-size:11.5px; color:var(--muted); text-align:center; line-height:1.6;
        }
        .l-waitlist-success, .l-waitlist-error {
            max-width:600px; margin:0 auto 22px; padding:14px 18px; border-radius:10px;
            font-size:13.5px; font-weight:600; text-align:center;
        }
        .l-waitlist-success { background:rgba(22,163,74,.1); color:#15803d; border:1px solid rgba(22,163,74,.3); }
        .l-waitlist-error { background:rgba(220,38,38,.08); color:rgb(185,28,28); border:1px solid rgba(220,38,38,.3); }

        /* === CTA BANNER — primary gradient === */
        .l-cta {
            background:linear-gradient(135deg, var(--primary) 0%, var(--primary-deep) 100%);
            color:#fff; padding:72px 22px; text-align:center;
            position:relative; overflow:hidden;
        }
        .l-cta::before {
            content:''; position:absolute; top:-20%; right:-10%;
            width:400px; height:400px; border-radius:50%;
            background:radial-gradient(circle, rgba(233,231,226,.18) 0%, transparent 70%);
        }
        .l-cta-inner { position:relative; z-index:1; max-width:780px; margin:0 auto; }
        .l-cta h2 {
            font-family:var(--font-base); font-weight:normal;
            margin:0 0 16px; font-size:34px;
        }
        .l-cta h2 em { color:var(--accent); font-style:italic; }
        .l-cta p { margin:0 0 30px; font-size:16px; opacity:.92; line-height:1.65; }
        .l-cta .l-btn { margin:0 6px 8px; }

        /* === FAQ === */
        .l-faq { max-width:780px; margin:0 auto; }
        .l-faq details {
            background:var(--surface); border:1px solid var(--line);
            border-radius:12px; padding:0;
            margin-bottom:10px; overflow:hidden;
            transition:border-color .2s;
        }
        .l-faq details[open] { border-color:var(--primary); }
        .l-faq summary {
            cursor:pointer; padding:18px 22px;
            font-weight:700; font-size:15px; color:var(--text);
            display:flex; align-items:center; justify-content:space-between;
            list-style:none;
        }
        .l-faq summary::after {
            content:'+'; font-size:24px; color:var(--primary);
            transition:transform .2s; margin-left:16px; flex-shrink:0;
        }
        .l-faq details[open] summary::after { content:'−'; }
        .l-faq summary::-webkit-details-marker { display:none; }
        .l-faq-body { padding:0 22px 20px; color:var(--muted); font-size:14px; line-height:1.75; }

        /* === FOOTER — primary-deep (marka uyumlu) === */
        .l-foot {
            background:var(--primary-deep);
            color:rgba(255,255,255,.68);
            padding:48px 22px 28px;
        }
        .l-foot-inner { max-width:1180px; margin:0 auto; }
        .l-foot-cols { display:grid; grid-template-columns:2fr 1fr 1fr; gap:36px; margin-bottom:32px; }
        @media(max-width:720px){ .l-foot-cols { grid-template-columns:1fr; gap:24px; } }
        .l-foot-logo {
            font-family:var(--font-base); font-size:28px; font-weight:700;
            color:#fff; margin-bottom:14px; display:inline-flex; align-items:center; line-height:1;
        }
        .l-foot-logo span { color:var(--neutral); font-style:italic; opacity:.95; }
        .l-foot-logo img {
            height:38px; width:auto; max-width:180px;
            background:#fff; padding:6px 12px; border-radius:8px;
            box-shadow:0 4px 14px rgba(0,0,0,.18);
        }
        .l-foot h4 { color:#fff; margin:0 0 14px; font-size:14px; font-weight:700; }
        .l-foot a { color:rgba(255,255,255,.72); display:block; margin-bottom:8px; font-size:13px; }
        .l-foot a:hover { color:#fff; text-decoration:none; }
        .l-foot p { color:rgba(255,255,255,.68); font-size:13px; line-height:1.65; margin:0 0 14px; max-width:340px; }
        .l-foot-bottom {
            border-top:1px solid rgba(255,255,255,.12); padding-top:22px;
            font-size:12px; text-align:center; color:rgba(255,255,255,.5);
        }
    </style>
</head>
<body>

{{-- ══════════════ NAV ══════════════ --}}
<nav class="l-nav">
    <div class="l-nav-inner">
        <a href="/" class="l-logo">
            @if (!empty($brandLogoUrl))
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandName ?? 'MentorDE' }}">
            @else
                Mentor<span>DE</span>
            @endif
        </a>
        <div class="l-nav-links">
            <a href="#nasil-calisir">Nasıl Çalışır</a>
            <a href="#danismanlar">Danışmanlar</a>
            <a href="#sss">SSS</a>
        </div>
        <a href="#danismanlar" class="l-nav-cta">📅 Randevu Al</a>
    </div>
</nav>

{{-- ══════════════ HERO — 2 kolon ══════════════ --}}
<section class="l-hero">
    <div class="l-hero-inner">
        {{-- SOL: Metin + CTA'lar + Trust --}}
        <div>
            <div class="l-hero-badge">✓ Almanya'da 106+ öğrenci, %95 memnuniyet</div>
            <h1>Almanya'da eğitim yolculuğun<br><em>bir görüşme ötende</em></h1>
            <p class="l-hero-sub">
                Üniversite seçimi, belge hazırlığı, vize süreci — uzman eğitim danışmanlarımızla birebir görüş,
                kafandaki soruları net cevaplara dönüştür. Dakikalar içinde müsait saatini bul, tek tıkla randevunu al.
            </p>
            <div class="l-hero-ctas">
                <a href="#danismanlar" class="l-btn l-btn-primary"
                   data-track="cta_clicked"
                   data-ph-cta-name="book_appointment_hero"
                   data-ph-location="booking_landing_hero">📅 Hemen Randevu Al</a>
                <a href="#nasil-calisir" class="l-btn l-btn-ghost"
                   data-track="cta_clicked"
                   data-ph-cta-name="learn_how_hero"
                   data-ph-location="booking_landing_hero">Nasıl Çalışır? ↓</a>
            </div>
            <div class="l-hero-trust">
                <div>
                    <div class="l-trust-num">106+</div>
                    <div class="l-trust-lbl">Öğrenci Almanya'da</div>
                </div>
                <div>
                    <div class="l-trust-num">50+</div>
                    <div class="l-trust-lbl">Üniversite Kabulü</div>
                </div>
                <div>
                    <div class="l-trust-num">%95</div>
                    <div class="l-trust-lbl">Memnuniyet</div>
                </div>
                <div>
                    <div class="l-trust-num">7+</div>
                    <div class="l-trust-lbl">Yıllık Deneyim</div>
                </div>
            </div>
        </div>

        {{-- SAĞ: Video veya Welcome kartı (CMS kontrollü) --}}
        {{-- Anchor sticky davranışta orijinal yeri tutar, video floating olunca boşluk kalmasın diye --}}
        <div class="l-hero-media-anchor" id="heroMediaAnchor">
            <div class="l-hero-media" id="heroMedia">
                @if (!empty($landingCms['video_url']))
                    @php
                        // Autoplay + mute base params (modern browser autoplay policy)
                        $baseUrl = $landingCms['video_url'];
                        $sep = str_contains($baseUrl, '?') ? '&' : '?';
                        // İlk yüklemede autoplay KAPALI; 3 saniye sonra JS ile yeniden yükleyeceğiz
                        $initialSrc = $baseUrl . $sep . 'mute=1&rel=0&playsinline=1';
                    @endphp
                    <button type="button" class="l-hero-media-close" id="heroMediaClose" aria-label="Videoyu kapat" title="Videoyu kapat">✕</button>
                    <div class="l-hero-media-video">
                        <iframe
                            id="heroMediaIframe"
                            src="{{ $initialSrc }}"
                            data-base-src="{{ $baseUrl }}"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                    </div>
                    @if (!empty($landingCms['welcome_body']))
                        <div class="l-hero-video-caption">
                            💬 {{ \Illuminate\Support\Str::limit($landingCms['welcome_body'], 200) }}
                        </div>
                    @endif
                @else
                    <div class="l-hero-welcome">
                        <div class="l-hero-welcome-icon">👋</div>
                        <h3>{{ $landingCms['welcome_title'] ?? 'Hoş Geldin!' }}</h3>
                        @foreach (explode("\n", trim((string) ($landingCms['welcome_body'] ?? ''))) as $para)
                            @if (trim($para) !== '')
                                <p>{{ $para }}</p>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- ══════════════ HOW IT WORKS — odaklı, üst sıraya alındı ══════════════ --}}
<section class="l-sec" id="nasil-calisir">
    <div class="l-sec-inner">
        <div class="l-sec-head">
            <div class="l-sec-eyebrow">Nasıl Çalışır</div>
            <h2 class="l-sec-title">3 adımda danışmanınla tanış</h2>
            <p class="l-sec-sub">Kafanda karışık soru varsa, cevaplamak 30 dakika sürer. Süreci 3 basit adıma indirdik.</p>
        </div>
        <div class="l-steps">
            <div class="l-step">
                <div class="l-step-num">1</div>
                <div class="l-step-icon">🔍</div>
                <h3>Danışman Seç</h3>
                <p>Aşağıdan senin için uygun uzmanı bul. Uzmanlık alanı (vize / üniversite / burs) veya dile göre filtrele.</p>
            </div>
            <div class="l-step">
                <div class="l-step-num">2</div>
                <div class="l-step-icon">📆</div>
                <h3>Müsait Saati Belirle</h3>
                <p>Gerçek zamanlı takvim — 30 gün içinde uygun olan saatleri gör. Tek tıkla seç, dakikalar içinde onaylansın.</p>
            </div>
            <div class="l-step">
                <div class="l-step-num">3</div>
                <div class="l-step-icon">💬</div>
                <h3>Görüş & Yol Haritanı Al</h3>
                <p>Online görüşme linki mailine gelir. 30-60 dakikalık birebir görüşmeden sonra kişisel yol haritan hazır.</p>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════ SENIORS ══════════════ --}}
<section class="l-sec l-sec-alt" id="danismanlar">
    <div class="l-sec-inner">
        <div class="l-sec-head">
            <div class="l-sec-eyebrow">Danışmanlarımız</div>
            <h2 class="l-sec-title">Müsait uzmanlar</h2>
            <p class="l-sec-sub">Her biri Almanya'da eğitim görmüş veya hâlâ aktif çalışan profesyoneller. Deneyimlerini senin adımlarına dönüştürüyorlar.</p>
        </div>

        {{-- 3 kategori tab — Bachelor / Master / Diğer --}}
        @php
            $tracks = [
                'bachelor' => ['emoji' => '🎓', 'title' => 'Bachelor', 'subtitle' => 'Lisans (4 yıllık üniversite)'],
                'master'   => ['emoji' => '🎯', 'title' => 'Master',   'subtitle' => 'Yüksek Lisans · Doktora'],
                'other'    => ['emoji' => '🌟', 'title' => 'Diğer',    'subtitle' => 'Dil okulu · Ausbildung · Vize'],
            ];
            $defaultTrack = ! empty($byTrack['bachelor']) && count($byTrack['bachelor']) > 0 ? 'bachelor'
                : (! empty($byTrack['master']) && count($byTrack['master']) > 0 ? 'master' : 'other');
        @endphp

        <div class="l-track-tabs">
            @foreach($tracks as $code => $t)
                <button type="button"
                        class="l-track-tab {{ $defaultTrack === $code ? 'active' : '' }}"
                        data-track-tab="{{ $code }}">
                    <span class="l-track-emoji">{{ $t['emoji'] }}</span>
                    <span class="l-track-text">
                        <strong>{{ $t['title'] }}</strong>
                        <small>{{ $t['subtitle'] }}</small>
                    </span>
                    <span class="l-track-count">{{ count($byTrack[$code] ?? []) }}</span>
                </button>
            @endforeach
        </div>

        @if(session('waitlist_success'))
            <div class="l-waitlist-success">✅ {{ session('waitlist_success') }}</div>
        @endif
        @error('waitlist')
            <div class="l-waitlist-error">⚠ {{ $message }}</div>
        @enderror

        @foreach($tracks as $code => $t)
            <div class="l-track-panel" data-track-panel="{{ $code }}" style="display:{{ $defaultTrack === $code ? 'block' : 'none' }};">
                @if(! empty($byTrack[$code]) && count($byTrack[$code]) > 0)
                    <div class="l-seniors-bar">
                        <input type="text" class="l-track-search" data-track-search="{{ $code }}" placeholder="🔍 {{ $t['title'] }} kategorisinde danışman ara...">
                    </div>
                    <div class="l-seniors-grid">
                        @foreach($byTrack[$code] as $s)
                            <div class="l-senior-card" data-search="{{ strtolower(($s['name'] ?? '').' '.($s['display_name'] ?? '').' '.implode(' ', (array)$s['expertise'])) }}">
                                <div class="l-senior-avatar">
                                    @if (!empty($s['photo_url']))
                                        <img src="{{ $s['photo_url'] }}" alt="{{ $s['name'] }}">
                                    @else
                                        {{ strtoupper(mb_substr($s['name'] ?? 'D', 0, 1)) }}
                                    @endif
                                </div>
                                <h3>{{ $s['name'] ?? $s['display_name'] }}</h3>
                                <div class="l-senior-sub">{{ $s['display_name'] }}</div>
                                @if (!empty($s['bio']))
                                    <div class="l-senior-bio">{{ \Illuminate\Support\Str::limit($s['bio'], 120) }}</div>
                                @else
                                    <div class="l-senior-bio" style="color:#94a3b8;font-style:italic;">Uzman tanıtımı yakında eklenecek.</div>
                                @endif
                                @if (!empty($s['expertise']))
                                    <div class="l-senior-tags">
                                        @foreach (array_slice($s['expertise'], 0, 5) as $tag)
                                            <span class="l-senior-tag">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="l-senior-meta">
                                    <span>⏱ {{ $s['slot_duration'] }} dk</span>
                                    <span>🌍 {{ $s['timezone'] }}</span>
                                </div>
                                <a href="{{ route('booking.public.show', ['slug' => $s['slug']]) }}" class="l-senior-btn">Müsait Saatleri Gör →</a>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Pozitif framing — "kişisel danışman ataması" hissi --}}
                    <div class="l-waitlist-card">
                        <div class="l-waitlist-icon">{{ $t['emoji'] }}</div>
                        <h3>{{ $t['title'] }} sürecin için <span class="l-waitlist-accent">sana özel danışman</span></h3>
                        <p>Bilgilerini bırak, <strong>{{ $t['title'] }}</strong> alanında uzman danışmanımız <strong>1-2 iş günü</strong> içinde email + telefondan ulaşıp ücretsiz tanışma görüşmesini birlikte planlayalım.</p>
                        <form method="POST" action="{{ route('booking.waitlist.join') }}" class="l-waitlist-form">
                            @csrf
                            <input type="hidden" name="track" value="{{ $code }}">
                            <div class="l-waitlist-row">
                                <input type="text" name="name" required maxlength="120" placeholder="Adın Soyadın *">
                                <input type="email" name="email" required maxlength="180" placeholder="Email *">
                            </div>
                            <div class="l-waitlist-row">
                                <input type="tel" name="phone" maxlength="32" placeholder="Telefon (+90 ...)">
                            </div>
                            <div class="l-waitlist-row">
                                <textarea name="message" maxlength="1000" rows="2" placeholder="Aklındaki kısa not — hangi alanı düşünüyorsun? (opsiyonel)"></textarea>
                            </div>
                            <button type="submit" class="l-btn l-btn-primary" style="width:100%;">
                                ✨ Danışmanım Beni Arasın
                            </button>
                            <div class="l-waitlist-fineprint">
                                ✓ Tanışma görüşmesi ücretsiz · ✓ Spam yok · ✓ İstediğin zaman silebilirsin
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</section>

{{-- ══════════════ MİNİ FAQ — kompakt 4 soru, sayfa odağını dağıtmaz ══════════════ --}}
<section class="l-sec l-sec-alt" id="sss">
    <div class="l-sec-inner">
        <div class="l-sec-head">
            <div class="l-sec-eyebrow">Hızlı Cevaplar</div>
            <h2 class="l-sec-title">Önce bunları sormak istersin</h2>
        </div>
        <div class="l-faq">
            <details open>
                <summary>Randevu ücretli mi?</summary>
                <div class="l-faq-body">
                    Hayır — <strong>ücretsiz</strong>. Tanışma + hedef analizi görüşmeleri tek kuruş alınmıyor. Tam danışmanlık paketine geçersen oradan itibaren paket ücretlendirmesi başlar.
                </div>
            </details>
            <details>
                <summary>Görüşme ne kadar sürer ve nasıl olur?</summary>
                <div class="l-faq-body">
                    30-60 dakika online — Google Meet veya Zoom. Randevu onaylandığında link mailine + Google Takvim'e otomatik düşer.
                </div>
            </details>
            <details>
                <summary>İptal / erteleme yapabilir miyim?</summary>
                <div class="l-faq-body">
                    Evet. Onay mailindeki link üzerinden <strong>24 saat öncesine kadar</strong> ücretsiz iptal/yeniden planla.
                </div>
            </details>
            <details>
                <summary>Sonrasında ne oluyor?</summary>
                <div class="l-faq-body">
                    İlk görüşmeden sonra durumuna uygun paketler (belge yönetimi / tam başvuru / premium) gösterilir. Paket seçersen sözleşme imzalanır, Uni-Assist'ten Immatrikulation'a kadar süreç platformda ilerler.
                </div>
            </details>
        </div>
    </div>
</section>

{{-- ══════════════ FOOTER — primary-deep (marka uyumlu) ══════════════ --}}
<footer class="l-foot">
    <div class="l-foot-inner">
        <div class="l-foot-cols">
            <div>
                <span class="l-foot-logo">
                    @if (!empty($brandLogoUrl))
                        <img src="{{ $brandLogoUrl }}" alt="{{ $brandName ?? 'MentorDE' }}">
                    @else
                        Mentor<span>DE</span>
                    @endif
                </span>
                <p>Türkiye'den Almanya'ya uzanan yol haritanı kolaylaştıran yurt dışı eğitim danışmanlık platformu.</p>
            </div>
            <div>
                <h4>Platform</h4>
                <a href="#nasil-calisir">Nasıl Çalışır</a>
                <a href="#danismanlar">Danışmanlar</a>
                <a href="/sss">Sık Sorulan Sorular</a>
                <a href="/login">Giriş Yap</a>
            </div>
            <div>
                <h4>Yasal</h4>
                <a href="{{ route('legal.privacy') }}">Gizlilik Politikası</a>
                <a href="{{ route('legal.terms') }}">Kullanım Koşulları</a>
                <a href="mailto:support@mentorde.com">support@mentorde.com</a>
            </div>
        </div>
        <div class="l-foot-bottom">
            © {{ date('Y') }} {{ $brandName ?? 'MentorDE' }} · Tüm hakları saklıdır
        </div>
    </div>
</footer>

<script>
// Track tab switch (Bachelor / Master / Diğer)
(function(){
    document.querySelectorAll('.l-track-tab').forEach(function(btn){
        btn.addEventListener('click', function(){
            var track = btn.getAttribute('data-track-tab');
            document.querySelectorAll('.l-track-tab').forEach(function(t){ t.classList.toggle('active', t === btn); });
            document.querySelectorAll('.l-track-panel').forEach(function(p){
                p.style.display = (p.getAttribute('data-track-panel') === track) ? 'block' : 'none';
            });
        });
    });
})();

// Per-track senior arama
(function(){
    document.querySelectorAll('.l-track-search').forEach(function(input){
        input.addEventListener('input', function(){
            var q = input.value.toLowerCase().trim();
            var panel = input.closest('.l-track-panel');
            if (!panel) return;
            panel.querySelectorAll('.l-senior-card').forEach(function(card){
                var haystack = card.getAttribute('data-search') || '';
                card.style.display = (q === '' || haystack.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    });
})();

// Hero video: 3 sn sonra autoplay + scroll ile floating + close butonu
(function(){
    var iframe = document.getElementById('heroMediaIframe');
    var media  = document.getElementById('heroMedia');
    var anchor = document.getElementById('heroMediaAnchor');
    var closeBtn = document.getElementById('heroMediaClose');
    if (!iframe || !media || !anchor) return;

    var dismissed = false;  // Kullanıcı X ile kapattıysa bir daha floating gösterme
    var autoplayStarted = false;

    // Helper: iframe src'ine autoplay param ekle
    function enableAutoplay() {
        if (autoplayStarted) return;
        autoplayStarted = true;
        var baseSrc = iframe.getAttribute('data-base-src') || iframe.src;
        var sep = baseSrc.indexOf('?') !== -1 ? '&' : '?';
        iframe.src = baseSrc + sep + 'autoplay=1&mute=1&rel=0&playsinline=1';
    }

    // 3 saniye sonra otomatik oynat
    setTimeout(enableAutoplay, 3000);

    // IntersectionObserver — anchor viewport dışına çıkınca floating
    if ('IntersectionObserver' in window) {
        var anchorHeight = 0;
        var syncAnchorHeight = function(){
            // Floating olmadan önceki yüksekliği kilitleyelim ki layout shift olmasın
            if (!media.classList.contains('is-floating')) {
                anchorHeight = media.offsetHeight;
            }
        };
        syncAnchorHeight();
        window.addEventListener('resize', syncAnchorHeight);

        var observer = new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if (dismissed) {
                    media.classList.remove('is-floating');
                    anchor.style.minHeight = '';
                    return;
                }
                if (entry.isIntersecting) {
                    // Orijinal yerine dön
                    media.classList.remove('is-floating');
                    anchor.style.minHeight = '';
                } else {
                    // Scroll aşağı — floating moda geç
                    if (anchorHeight > 0) {
                        anchor.style.minHeight = anchorHeight + 'px';
                    }
                    media.classList.add('is-floating');
                    // Floating'e geçerken autoplay henüz başlamadıysa başlat
                    enableAutoplay();
                }
            });
        }, { threshold: 0, rootMargin: '-80px 0px 0px 0px' });

        observer.observe(anchor);
    }

    // Close butonu — floating pencereyi gizle (kalıcı, bu oturum)
    if (closeBtn) {
        closeBtn.addEventListener('click', function(){
            dismissed = true;
            media.classList.remove('is-floating');
            media.style.display = 'none';
            anchor.style.minHeight = '';
            // Video'yu durdur (src'yi sıfırla)
            if (iframe) iframe.src = 'about:blank';
        });
    }
})();
</script>

</body>
</html>
