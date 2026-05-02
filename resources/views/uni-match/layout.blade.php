<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php $brand = config('brand.name', 'MentorDE'); @endphp
    <title>@yield('title', $brand . ' UniMatch — Sana Özel Almanya Üniversite Rehberi')</title>

    {{-- Sosyal medya / paylaşım meta tag'leri (OG + Twitter) --}}
    <meta name="description" content="@yield('og_description', 'Almanya\'da sana en uygun programı bul. 5 dakikalık akıllı sihirbaz, 15.000+ Almanya programı arasından profil ve hedeflerine en uygun olanları sıralar.')">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $brand }}">
    <meta property="og:title" content="@yield('og_title', 'UniMatch — Almanya\'da Sana Uygun Programı Bul')">
    <meta property="og:description" content="@yield('og_description', '15.000+ Almanya programı, 9-faktör akıllı eşleştirme, 5 dakikada sana özel öneriler. Ücretsiz, login gerekmez.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ url('/img/unimatch-og.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="tr_TR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'UniMatch — Almanya\'da Sana Uygun Programı Bul')">
    <meta name="twitter:description" content="@yield('og_description', '15.000+ Almanya programı, 9-faktör akıllı eşleştirme, 5 dakikada sana özel öneriler.')">
    <meta name="twitter:image" content="{{ url('/img/unimatch-og.png') }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Space Grotesk', sans-serif;
            background: linear-gradient(135deg, #e9e7e2 0%, #f4f2ee 100%);
            color: #1a1a1a;
            min-height: 100vh;
            line-height: 1.6;
        }
        .sb-container { max-width: 720px; margin: 0 auto; padding: 32px 20px; }

        /* Header */
        .sb-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .sb-logo {
            font-size: 26px; font-weight: 700; color: #7e58bf;
            text-decoration: none; letter-spacing: -0.5px;
            display: inline-flex; align-items: center; gap: 8px; line-height: 1;
        }
        .sb-logo span { color: #a07ed9; font-style: italic; font-weight: 600; }
        .sb-logo img { height: 36px; width: auto; max-width: 180px; display: block; }
        .sb-back { color: #7e58bf; text-decoration: none; font-size: 14px; font-weight: 500; }
        .sb-back:hover { text-decoration: underline; }

        /* Progress bar */
        .sb-progress-wrap { margin-bottom: 28px; }
        .sb-progress-meta { display: flex; justify-content: space-between; font-size: 12px; color: #6b5894; margin-bottom: 6px; font-weight: 600; }
        .sb-progress-bar { height: 6px; background: #d8d2e8; border-radius: 999px; overflow: hidden; }
        .sb-progress-fill {
            height: 100%; background: linear-gradient(90deg, #7e58bf, #a07ed9);
            border-radius: 999px;
            transition: width .6s cubic-bezier(.4,0,.2,1);
            position: relative;
        }
        .sb-progress-fill::after {
            content: ''; position: absolute; inset: 0; border-radius: inherit;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.45), transparent);
            transform: translateX(-100%);
        }

        /* Card */
        .sb-card {
            background: #fff;
            border-radius: 18px;
            padding: 36px 28px;
            box-shadow: 0 8px 32px rgba(126, 88, 191, 0.08);
            border: 1px solid rgba(126, 88, 191, 0.1);
        }
        .sb-title {
            font-size: 26px; font-weight: 700; color: #1a1a1a;
            margin-bottom: 8px; letter-spacing: -0.5px; line-height: 1.3;
        }
        .sb-subtitle {
            font-size: 14.5px; color: #6b5894; margin-bottom: 28px; line-height: 1.55;
        }

        /* Option cards (radio-as-cards) */
        .sb-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
        .sb-options.cols-2 { grid-template-columns: repeat(2, 1fr); }
        .sb-options.cols-1 { grid-template-columns: 1fr; }
        .sb-option {
            position: relative;
            background: #fff;
            border: 2px solid #e9e7e2;
            border-radius: 12px;
            padding: 18px 16px;
            cursor: pointer;
            transition: border-color .2s cubic-bezier(.4,0,.2,1),
                        transform .25s cubic-bezier(.4,0,.2,1),
                        box-shadow .25s cubic-bezier(.4,0,.2,1),
                        background .2s cubic-bezier(.4,0,.2,1);
            text-align: left;
            display: flex; align-items: flex-start; gap: 12px;
            font-family: inherit;
            width: 100%;
            will-change: transform;
        }
        .sb-option:hover {
            border-color: #b79ae9;
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 8px 24px rgba(126, 88, 191, 0.14);
        }
        .sb-option:active { transform: translateY(0) scale(.99); transition-duration: .08s; }
        .sb-option input[type="radio"] { position: absolute; opacity: 0; }
        .sb-option.selected { border-color: #7e58bf; background: linear-gradient(135deg, rgba(126, 88, 191, 0.08), rgba(167, 126, 217, 0.04)); }
        .sb-option.selected::before {
            content: '✓';
            position: absolute;
            top: 12px; right: 12px;
            background: #7e58bf; color: #fff;
            width: 22px; height: 22px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700;
        }
        /* Print-friendly: Ctrl+P direkt güzel çıktı */
        @media print {
            body { background: #fff !important; }
            .sb-header, .sb-nav, .sb-back, .fav-btn, [data-favorite-toggle] { display: none !important; }
            .sb-card { box-shadow: none !important; border: 1px solid #ccc !important; page-break-inside: avoid; }
            a { color: #1a1a1a !important; text-decoration: none !important; }
            .sb-btn { display: none !important; }
        }

        /* Accessibility: focus visible (keyboard navigation) */
        .sb-option:focus-within { outline: 3px solid #7e58bf; outline-offset: 2px; box-shadow: 0 0 0 6px rgba(126, 88, 191, 0.18); }
        button:focus-visible, a:focus-visible, input:focus-visible { outline: 3px solid #7e58bf; outline-offset: 2px; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
        .sb-option-icon { font-size: 26px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; }
        .sb-option-icon .lucide-icon { width: 28px; height: 28px; color: #7e58bf; transition: color .15s; }
        .sb-option:hover .sb-option-icon .lucide-icon { color: #5e3f9c; }
        .sb-option.selected .sb-option-icon .lucide-icon { color: #7e58bf; }
        .sb-option-text { flex: 1; }
        .sb-option-label { font-size: 14.5px; font-weight: 600; color: #1a1a1a; margin-bottom: 2px; }
        .sb-option-desc { font-size: 12px; color: #6b5894; line-height: 1.4; }

        /* Footer / nav */
        .sb-nav { display: flex; justify-content: space-between; align-items: center; margin-top: 28px; gap: 12px; }
        .sb-btn {
            padding: 12px 24px; border: none; border-radius: 10px;
            font-family: inherit; font-size: 14.5px; font-weight: 600; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
            transition: transform .2s cubic-bezier(.4,0,.2,1),
                        box-shadow .25s cubic-bezier(.4,0,.2,1),
                        background .2s cubic-bezier(.4,0,.2,1);
            will-change: transform;
        }
        .sb-btn-primary {
            background: linear-gradient(135deg, #7e58bf, #6a47a8);
            color: #fff;
            box-shadow: 0 4px 14px rgba(126, 88, 191, 0.25);
            position: relative;
            overflow: hidden;
        }
        .sb-btn-primary::after {
            content: ''; position: absolute; top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.22), transparent);
            transition: left .55s cubic-bezier(.4,0,.2,1);
        }
        .sb-btn-primary:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 24px rgba(126, 88, 191, 0.42);
        }
        .sb-btn-primary:hover::after { left: 100%; }
        .sb-btn-primary:active { transform: translateY(0) scale(.99); transition-duration: .08s; }
        .sb-btn-primary:disabled { background: #cdc7d8; cursor: not-allowed; box-shadow: none; transform: none; }
        .sb-btn-primary:disabled::after { display: none; }
        .sb-btn-ghost { background: transparent; color: #6b5894; }
        .sb-btn-ghost:hover { background: rgba(126, 88, 191, 0.08); transform: translateX(-2px); }

        /* Hero (landing) */
        .sb-hero { text-align: center; padding: 60px 20px 40px; }
        .sb-hero-badge {
            display: inline-block; padding: 6px 14px;
            background: rgba(126, 88, 191, 0.12); color: #7e58bf;
            border-radius: 999px; font-size: 12.5px; font-weight: 600;
            margin-bottom: 16px; letter-spacing: .3px;
        }
        .sb-hero-title { font-size: 38px; font-weight: 700; color: #1a1a1a; margin-bottom: 12px; line-height: 1.15; letter-spacing: -1px; }
        .sb-hero-subtitle { font-size: 17px; color: #6b5894; margin-bottom: 28px; max-width: 540px; margin-left: auto; margin-right: auto; line-height: 1.55; }
        .sb-hero-cta { display: inline-flex; padding: 16px 36px; font-size: 16px; font-weight: 700; }
        .sb-hero-meta { font-size: 13px; color: #8a7baf; margin-top: 16px; }

        /* Stats */
        .sb-stats { display: flex; justify-content: center; gap: 36px; margin-top: 48px; flex-wrap: wrap; }
        .sb-stat { text-align: center; }
        .sb-stat-num { font-size: 32px; font-weight: 700; color: #7e58bf; letter-spacing: -1px; }
        .sb-stat-label { font-size: 12.5px; color: #6b5894; margin-top: 4px; }

        @media (max-width: 600px) {
            .sb-hero-title { font-size: 28px; }
            .sb-card { padding: 24px 18px; }
            .sb-title { font-size: 22px; }
        }

        /* ── Linear tarzı entry animasyonları (reduced-motion respect) ── */
        @media (prefers-reduced-motion: no-preference) {
            .sb-card {
                animation: sb-card-in .42s cubic-bezier(.4,0,.2,1) both;
            }
            @keyframes sb-card-in {
                0%   { opacity: 0; transform: translateY(14px) scale(.985); }
                100% { opacity: 1; transform: translateY(0) scale(1); }
            }

            .sb-progress-fill::after {
                animation: sb-progress-shimmer 2.6s cubic-bezier(.4,0,.2,1) infinite;
            }
            @keyframes sb-progress-shimmer {
                0%   { transform: translateX(-100%); }
                60%  { transform: translateX(100%); }
                100% { transform: translateX(100%); }
            }

            .sb-options .sb-option {
                animation: sb-option-in .38s cubic-bezier(.4,0,.2,1) both;
            }
            .sb-options .sb-option:nth-child(1) { animation-delay: 70ms; }
            .sb-options .sb-option:nth-child(2) { animation-delay: 130ms; }
            .sb-options .sb-option:nth-child(3) { animation-delay: 190ms; }
            .sb-options .sb-option:nth-child(4) { animation-delay: 250ms; }
            .sb-options .sb-option:nth-child(5) { animation-delay: 310ms; }
            .sb-options .sb-option:nth-child(6) { animation-delay: 370ms; }
            .sb-options .sb-option:nth-child(n+7) { animation-delay: 430ms; }
            @keyframes sb-option-in {
                0%   { opacity: 0; transform: translateY(8px); }
                100% { opacity: 1; transform: translateY(0); }
            }

            .sb-title, .sb-subtitle {
                animation: sb-title-in .5s cubic-bezier(.4,0,.2,1) both;
            }
            .sb-subtitle { animation-delay: 80ms; }
            @keyframes sb-title-in {
                0%   { opacity: 0; transform: translateY(-6px); }
                100% { opacity: 1; transform: translateY(0); }
            }

            .sb-hero > * {
                animation: sb-hero-in .55s cubic-bezier(.4,0,.2,1) both;
            }
            .sb-hero > *:nth-child(2) { animation-delay: 90ms; }
            .sb-hero > *:nth-child(3) { animation-delay: 180ms; }
            .sb-hero > *:nth-child(4) { animation-delay: 270ms; }
            .sb-hero > *:nth-child(n+5) { animation-delay: 360ms; }
            @keyframes sb-hero-in {
                0%   { opacity: 0; transform: translateY(12px); }
                100% { opacity: 1; transform: translateY(0); }
            }

            /* Form submit: button "yükleniyor" feedback */
            .sb-btn-primary.is-loading {
                pointer-events: none;
                opacity: .85;
            }
            .sb-btn-primary.is-loading::before {
                content: '';
                width: 14px; height: 14px;
                border: 2px solid rgba(255,255,255,.4);
                border-top-color: #fff;
                border-radius: 50%;
                animation: sb-spin .7s linear infinite;
                margin-right: 4px;
            }
            @keyframes sb-spin { to { transform: rotate(360deg); } }
        }

        @media (prefers-reduced-motion: reduce) {
            * { animation-duration: .01ms !important; transition-duration: .01ms !important; }
        }

        /* ════════════════════════════════════════════════════════════════
           Studyportals tarzı premium wizard mode (body.sb-wizard-mode ile aktif)
           - Floating gradient blobs (animated)
           - Pill segmented progress
           - Bigger typography (32px+ desktop)
           - Larger option cards
           - Sticky bottom nav bar
        ════════════════════════════════════════════════════════════════ */
        body.sb-wizard-mode {
            background: linear-gradient(140deg, #f7f3ff 0%, #faf9f5 50%, #efe9fb 100%);
            min-height: 100vh;
            overflow-x: hidden;
            padding-bottom: 96px; /* sticky CTA için boşluk */
        }
        body.sb-wizard-mode .sb-container { max-width: 880px; padding: 24px 20px 48px; position: relative; z-index: 1; }

        /* Floating animated gradient blobs (background) */
        .sb-bg-blobs { position: fixed; inset: 0; pointer-events: none; overflow: hidden; z-index: 0; }
        .sb-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .55;
            will-change: transform;
        }
        .sb-blob-1 {
            width: 480px; height: 480px;
            background: radial-gradient(circle, #c4a5ff 0%, transparent 70%);
            top: -160px; left: -120px;
            animation: sb-blob-drift-1 22s cubic-bezier(.4,0,.6,1) infinite;
        }
        .sb-blob-2 {
            width: 540px; height: 540px;
            background: radial-gradient(circle, #f3e0ff 0%, transparent 70%);
            top: 30%; right: -200px;
            animation: sb-blob-drift-2 28s cubic-bezier(.4,0,.6,1) infinite;
        }
        .sb-blob-3 {
            width: 360px; height: 360px;
            background: radial-gradient(circle, #d8c5f5 0%, transparent 70%);
            bottom: -120px; left: 35%;
            animation: sb-blob-drift-3 26s cubic-bezier(.4,0,.6,1) infinite;
        }
        @media (prefers-reduced-motion: no-preference) {
            @keyframes sb-blob-drift-1 {
                0%, 100% { transform: translate(0, 0) scale(1); }
                50%      { transform: translate(60px, 80px) scale(1.1); }
            }
            @keyframes sb-blob-drift-2 {
                0%, 100% { transform: translate(0, 0) scale(1); }
                50%      { transform: translate(-90px, -50px) scale(1.08); }
            }
            @keyframes sb-blob-drift-3 {
                0%, 100% { transform: translate(0, 0) scale(1); }
                50%      { transform: translate(40px, -60px) scale(1.12); }
            }
        }

        /* Pill segmented progress */
        .sb-pill-progress {
            display: flex; gap: 6px; flex-wrap: wrap; align-items: center;
            margin-bottom: 36px;
        }
        .sb-pill {
            flex: 1; min-width: 14px; max-width: 36px;
            height: 6px; border-radius: 999px;
            background: rgba(126, 88, 191, .15);
            transition: background .3s cubic-bezier(.4,0,.2,1), transform .3s cubic-bezier(.4,0,.2,1);
        }
        .sb-pill.is-done { background: linear-gradient(90deg, #7e58bf, #a07ed9); }
        .sb-pill.is-active {
            background: linear-gradient(90deg, #7e58bf, #a07ed9);
            transform: scaleY(1.6);
            box-shadow: 0 2px 8px rgba(126,88,191,.4);
        }
        .sb-pill-meta {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 12.5px; color: #6b5894; font-weight: 600;
            margin-bottom: 14px;
        }
        .sb-pill-meta .sb-pill-step { color: #7e58bf; font-weight: 700; }

        /* Wizard mode card — daha büyük, daha az glass */
        body.sb-wizard-mode .sb-card {
            background: rgba(255, 255, 255, .96);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(126, 88, 191, .12);
            box-shadow: 0 12px 48px rgba(126, 88, 191, .12);
            padding: 48px 40px;
            border-radius: 24px;
        }
        body.sb-wizard-mode .sb-title {
            font-size: 32px; line-height: 1.2; letter-spacing: -.8px;
            margin-bottom: 12px;
        }
        body.sb-wizard-mode .sb-subtitle {
            font-size: 16px; line-height: 1.6; margin-bottom: 32px;
        }

        /* Wizard mode option cards — daha büyük, ikon önde, hover daha dramatik */
        body.sb-wizard-mode .sb-options { gap: 14px; }
        body.sb-wizard-mode .sb-options:not(.cols-1):not(.cols-2) { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        body.sb-wizard-mode .sb-option {
            padding: 22px 20px;
            border-width: 2px;
            border-radius: 16px;
            min-height: 80px;
            gap: 16px;
        }
        body.sb-wizard-mode .sb-option:hover {
            transform: translateY(-3px) scale(1.015);
            box-shadow: 0 14px 32px rgba(126, 88, 191, .18);
            border-color: #b79ae9;
        }
        body.sb-wizard-mode .sb-option.selected {
            box-shadow: 0 8px 24px rgba(126, 88, 191, .22);
        }
        body.sb-wizard-mode .sb-option-icon {
            width: 48px; height: 48px;
            background: rgba(126, 88, 191, .1);
            border-radius: 12px;
            transition: background .25s cubic-bezier(.4,0,.2,1);
        }
        body.sb-wizard-mode .sb-option-icon .lucide-icon { width: 26px; height: 26px; }
        body.sb-wizard-mode .sb-option:hover .sb-option-icon { background: rgba(126, 88, 191, .18); }
        body.sb-wizard-mode .sb-option.selected .sb-option-icon {
            background: linear-gradient(135deg, #7e58bf, #a07ed9);
        }
        body.sb-wizard-mode .sb-option.selected .sb-option-icon .lucide-icon { color: #fff; }
        body.sb-wizard-mode .sb-option-label { font-size: 15.5px; font-weight: 700; }
        body.sb-wizard-mode .sb-option-desc { font-size: 12.5px; }

        /* Sticky bottom nav bar — Studyportals tarzı CTA her zaman görünür */
        body.sb-wizard-mode .sb-nav {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            z-index: 50;
            background: rgba(255, 255, 255, .92);
            backdrop-filter: blur(14px);
            border-top: 1px solid rgba(126, 88, 191, .12);
            padding: 14px 20px;
            margin-top: 0;
            box-shadow: 0 -8px 24px rgba(126, 88, 191, .08);
        }
        body.sb-wizard-mode .sb-nav-inner {
            max-width: 880px; margin: 0 auto;
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
        }
        body.sb-wizard-mode .sb-btn-primary {
            padding: 14px 32px; font-size: 15px;
        }

        @media (max-width: 600px) {
            body.sb-wizard-mode .sb-card { padding: 32px 22px; }
            body.sb-wizard-mode .sb-title { font-size: 24px; }
            body.sb-wizard-mode .sb-subtitle { font-size: 14.5px; }
            body.sb-wizard-mode .sb-option { padding: 18px 16px; min-height: 72px; }
            body.sb-wizard-mode .sb-option-icon { width: 42px; height: 42px; }
        }
    </style>
    @stack('head')
</head>
<body>
    {{-- Wizard step pages için body class (head'de @push'lanan flag) --}}
    <script nonce="{{ $cspNonce ?? '' }}">
        if (document.documentElement.classList.contains('sb-wizard-active')) {
            document.body.classList.add('sb-wizard-mode');
        }
    </script>

    {{-- Toast: session flash mesajları (success/info/error) --}}
    @if(session('success') || session('info') || session('error'))
        @php
            $flashType = session('success') ? 'success' : (session('info') ? 'info' : 'error');
            $flashMsg = session('success') ?? session('info') ?? session('error');
            $flashColor = match($flashType) { 'success' => '#16a34a', 'info' => '#7e58bf', 'error' => '#dc2626' };
            $flashIcon = match($flashType) { 'success' => '✓', 'info' => 'ℹ', 'error' => '⚠' };
        @endphp
        <div id="sb-toast"
             role="status"
             style="position:fixed;top:20px;left:50%;transform:translateX(-50%) translateY(-20px);background:{{ $flashColor }};color:#fff;padding:14px 22px;border-radius:10px;font-size:14px;font-weight:600;z-index:10000;box-shadow:0 8px 28px rgba(0,0,0,.2);opacity:0;transition:all .35s cubic-bezier(.4,0,.2,1);max-width:90%;display:flex;align-items:center;gap:10px;">
            <span style="font-size:18px;">{!! $flashIcon !!}</span>
            <span>{{ $flashMsg }}</span>
        </div>
        <script nonce="{{ $cspNonce ?? '' }}">
            (function(){
                var t = document.getElementById('sb-toast');
                setTimeout(function(){ t.style.opacity='1'; t.style.transform='translateX(-50%) translateY(0)'; }, 80);
                setTimeout(function(){ t.style.opacity='0'; t.style.transform='translateX(-50%) translateY(-20px)'; setTimeout(function(){ t.remove(); }, 400); }, 4000);
            })();
        </script>
    @endif

    @php $logoUrl = config('brand.logo_url') ?: null; @endphp
    <div class="sb-container">
        <header class="sb-header">
            <a href="/" class="sb-logo" aria-label="{{ $brand }}">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $brand }}">
                @else
                    mentor<span>de</span>
                @endif
            </a>
            @hasSection('back-link')
                @yield('back-link')
            @endif
        </header>

        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
