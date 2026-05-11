<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Yasal' }} — {{ config('brand.name', 'MentorDE') }}</title>
    @include('partials.favicon')
    <meta name="robots" content="index,follow">
    <link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">
    <style>
        :root {
            /* Brandbook (29 Nisan): #7e58bf primary + Space Grotesk + cream — eski purple+gold KULLANILMAZ */
            --bg: #f4f2ee;
            --surface: #ffffff;
            --text: #1a1a1a;
            --muted: #6b5894;
            --primary: #7e58bf;
            --primary-2: #6a47a8;
            --primary-3: #a07ed9;
            --primary-soft: #f1e8fb;
            --primary-soft-2: #ede9fe;
            --line: #e8dffa;
            --line-soft: #f0ecf6;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Space Grotesk", -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
            color: var(--text);
            background: linear-gradient(135deg, #e9e7e2 0%, #f4f2ee 100%);
            line-height: 1.65;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .wrap-top { max-width: 1080px; margin: 0 auto; padding: 18px 20px; width: 100%; }
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            /* Brandbook orijinal logo stili — platform-landing ile birebir hizalı */
            font-family: "Space Grotesk", -apple-system, sans-serif;
            color: var(--primary);
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
            line-height: 1;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .logo span {
            color: var(--primary-3);
            font-style: italic;
            font-weight: 600;
        }
        .logo img { height: 36px; width: auto; max-width: 200px; display: block; }
        .nav-links { display: flex; gap: 18px; align-items: center; }
        .nav-links a {
            color: var(--muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: color .15s;
        }
        .nav-links a:hover, .nav-links a.active { color: var(--primary); }
        .nav-links .login-btn {
            display: inline-flex;
            align-items: center;
            background: var(--primary);
            color: #fff;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 700;
        }
        .nav-links .login-btn:hover { background: var(--primary-2); color: #fff; }

        main.wrap {
            flex: 1;
            max-width: 1080px;
            width: 100%;
            margin: 8px auto 32px;
            padding: 0 20px;
        }
        .doc {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 36px 42px;
            box-shadow: 0 4px 20px rgba(126,88,191,.08);
        }
        .doc h1 {
            margin: 0 0 6px;
            font-family: "Space Grotesk", -apple-system, sans-serif;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
            line-height: 1.25;
            color: var(--text);
        }
        .doc h2 {
            margin: 28px 0 10px;
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-2);
        }
        .doc h3 {
            margin: 18px 0 8px;
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
        }
        .doc p { margin: 0 0 14px; font-size: 15px; }
        .doc ul, .doc ol { padding-left: 22px; margin: 0 0 14px; }
        .doc li { margin-bottom: 6px; font-size: 15px; }
        .doc a {
            color: var(--primary);
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .doc a:hover { color: var(--primary-2); }
        .doc strong { color: var(--text); font-weight: 700; }
        .doc table {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0;
            font-size: 14px;
        }
        .doc table th, .doc table td {
            border: 1px solid var(--line);
            padding: 9px 12px;
            text-align: left;
            vertical-align: top;
        }
        .doc table th { background: var(--primary-soft); font-weight: 700; color: var(--primary-2); }
        .doc blockquote {
            background: var(--primary-soft);
            border-left: 3px solid var(--primary);
            padding: 12px 18px;
            border-radius: 0 10px 10px 0;
            margin: 16px 0;
            color: var(--text);
        }
        .doc code {
            background: var(--primary-soft);
            color: var(--primary-2);
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 13px;
        }
        @media (max-width: 640px) {
            .doc { padding: 24px 20px; border-radius: 12px; }
            .doc h1 { font-size: 26px; }
            .doc h2 { font-size: 17px; }
            .nav-links { gap: 10px; }
            .nav-links .desktop-only { display: none; }
        }
    </style>
</head>
<body>
    <header class="wrap-top">
        <nav class="nav">
            @php $logoUrl = config('brand.logo_url') ?: null; @endphp
            <a href="/" class="logo">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ config('brand.name', 'MentorDE') }}">
                @else
                    mentor<span>de</span>
                @endif
            </a>
            <div class="nav-links">
                <a href="{{ route('legal.privacy') }}" class="desktop-only {{ request()->routeIs('legal.privacy') || request()->routeIs('legal.datenschutz') ? 'active' : '' }}">Gizlilik</a>
                <a href="{{ route('legal.terms') }}" class="desktop-only {{ request()->routeIs('legal.terms') || request()->routeIs('legal.agb') ? 'active' : '' }}">Kullanım Koşulları</a>
                <a href="/login" class="login-btn">Giriş</a>
            </div>
        </nav>
    </header>

    <main class="wrap">
        <article class="doc">
            {{ $slot ?? '' }}
            @yield('content')
        </article>
    </main>

    @include('partials.legal-footer')
    @include('partials.cookie-consent')
</body>
</html>
