<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Yasal' }} — {{ config('brand.name', 'MentorDE') }}</title>
    <meta name="robots" content="index,follow">
    <style>
        :root {
            --bg: #f7faff;
            --panel: #ffffff;
            --ink: #11243d;
            --muted: #5f7392;
            --primary: #1f66d1;
            --primary-2: #1149a8;
            --line: #d8e2f0;
            --soft: #eff4fc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, system-ui, sans-serif;
            color: var(--ink);
            background: var(--bg);
            line-height: 1.65;
        }
        .top {
            background: #fff;
            border-bottom: 1px solid var(--line);
            padding: 14px 24px;
        }
        .top-inner {
            max-width: 960px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }
        .brand {
            font-weight: 800;
            font-size: 18px;
            color: var(--ink);
            text-decoration: none;
        }
        .brand span { color: var(--primary); }
        .top-nav { display: flex; gap: 14px; font-size: 14px; }
        .top-nav a {
            color: var(--muted);
            text-decoration: none;
            font-weight: 600;
        }
        .top-nav a:hover, .top-nav a.active { color: var(--primary); }
        .wrap {
            max-width: 860px;
            margin: 32px auto 48px;
            padding: 0 24px;
        }
        .doc {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 36px 42px;
            box-shadow: 0 4px 20px rgba(15,30,60,.04);
        }
        .doc h1 {
            margin: 0 0 6px;
            font-size: 26px;
            font-weight: 800;
        }
        .lead {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 28px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--line);
        }
        .doc h2 {
            margin: 28px 0 10px;
            font-size: 18px;
            font-weight: 700;
            color: var(--ink);
        }
        .doc h3 {
            margin: 18px 0 6px;
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
        }
        .doc p { margin: 0 0 12px; font-size: 15px; }
        .doc ul { padding-left: 22px; margin: 0 0 14px; }
        .doc li { margin-bottom: 6px; font-size: 15px; }
        .doc a { color: var(--primary); text-decoration: underline; text-underline-offset: 2px; }
        .doc strong { color: var(--ink); }
        .callout {
            background: var(--soft);
            border-left: 3px solid var(--primary);
            padding: 12px 16px;
            border-radius: 0 8px 8px 0;
            font-size: 14px;
            color: var(--ink);
            margin: 16px 0;
        }
        .tbl {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 16px;
            font-size: 14px;
        }
        .tbl th, .tbl td {
            border: 1px solid var(--line);
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        .tbl th { background: var(--soft); font-weight: 700; }
        .foot {
            max-width: 960px;
            margin: 0 auto;
            padding: 24px;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
        }
        .foot a { color: var(--primary); text-decoration: none; margin: 0 8px; }
        @media (max-width: 640px) {
            .doc { padding: 24px 20px; }
            .doc h1 { font-size: 22px; }
        }
    </style>
</head>
<body>
    <header class="top">
        <div class="top-inner">
            <a href="/login" class="brand">{{ config('brand.name', 'MentorDE') }}<span>.</span></a>
            <nav class="top-nav">
                <a href="{{ route('legal.privacy') }}" class="{{ request()->routeIs('legal.privacy') ? 'active' : '' }}">Gizlilik</a>
                <a href="{{ route('legal.terms') }}" class="{{ request()->routeIs('legal.terms') ? 'active' : '' }}">Kullanım Koşulları</a>
                <a href="/login">Giriş</a>
            </nav>
        </div>
    </header>

    <main class="wrap">
        <article class="doc">
            {{ $slot ?? '' }}
            @yield('content')
        </article>
    </main>

    <footer class="foot">
        © {{ date('Y') }} {{ config('brand.name', 'MentorDE') }} ·
        <a href="{{ route('legal.privacy') }}">Gizlilik Politikası</a> ·
        <a href="{{ route('legal.terms') }}">Kullanım Koşulları</a> ·
        <a href="mailto:destek@mentorde.com">İletişim</a>
    </footer>
</body>
</html>
