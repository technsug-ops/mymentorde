<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uzmanlar — Almanya Yolunda Birebir Danışmanlık · MentörDE</title>
    @include('partials.favicon')
    <meta name="description" content="Almanya'da üniversite başvurusu, vize ve belge süreci için sertifikalı MentörDE uzmanları. Ücretsiz tanışma görüşmesi al.">
    <meta property="og:title" content="MentörDE Uzmanlar — Almanya Yolunda Birebir Destek">
    <meta property="og:description" content="Uzman danışmanlardan ücretsiz tanışma görüşmesi al. Üniversite, vize, belge, kariyer.">
    <meta property="og:type" content="website">
    <meta name="robots" content="index, follow">

    <link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">

    <style nonce="{{ $cspNonce ?? '' }}">
        :root {
            --primary:#7e58bf;
            --primary-dark:#6c47a8;
            --primary-deep:#5a3a8d;
            --primary-mid:#a07ed9;
            --primary-light:#b79ae9;
            --primary-soft:#efe9fb;
            --neutral:#e9e7e2;
            --neutral-soft:#faf9f5;
            --text:#1a1325;
            --muted:#6b6377;
            --line:#e3dcec;
            --line-soft:#f0ecf6;
            --surface:#ffffff;
            --bg:#faf9f5;
            --success-bg:#e8f5ed;
            --success-text:#2d8b55;
            --warning-bg:#fef4e6;
            --warning-text:#a16207;
            --font-base:"Space Grotesk", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        * { box-sizing:border-box; }
        html, body { margin:0; padding:0; }
        body {
            font-family:var(--font-base);
            color:var(--text);
            background:linear-gradient(160deg, #f7f3ff 0%, #faf9f5 55%, #f1edfb 100%);
            line-height:1.6;
            font-size:15px;
            -webkit-font-smoothing:antialiased;
        }
        a { color:var(--primary); text-decoration:none; }
        a:hover { text-decoration:underline; }
        .container { max-width:1180px; margin:0 auto; padding:0 22px; }

        /* === NAV === */
        .d-nav {
            position:sticky; top:0; z-index:50;
            background:rgba(255,255,255,.92); backdrop-filter:blur(12px);
            border-bottom:1px solid var(--line);
        }
        .d-nav-inner { max-width:1180px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; padding:14px 22px; gap:16px; }
        .d-logo { font-size:24px; font-weight:700; color:var(--primary); letter-spacing:-.5px; }
        .d-logo span { color:var(--primary-deep); font-style:italic; }
        .d-nav-links { display:flex; gap:24px; font-size:14px; font-weight:600; }
        .d-nav-links a { color:var(--muted); }
        .d-nav-links a:hover { color:var(--primary); text-decoration:none; }
        .d-nav-cta {
            padding:10px 18px; background:var(--primary); color:#fff !important;
            border-radius:10px; font-size:13px; font-weight:700;
        }
        .d-nav-cta:hover { background:var(--primary-dark); text-decoration:none !important; }
        @media(max-width:720px){ .d-nav-links { display:none; } }

        /* === HERO === */
        .d-hero { padding:64px 0 28px; }
        .d-hero-badge {
            display:inline-flex; align-items:center; gap:6px;
            background:var(--primary-soft); color:var(--primary-deep);
            padding:6px 14px; border-radius:999px;
            font-size:12px; font-weight:700; margin-bottom:18px;
            letter-spacing:.02em;
        }
        .d-hero h1 {
            font-size:46px; line-height:1.1; margin:0 0 18px;
            font-weight:700; letter-spacing:-1.5px; max-width:780px;
        }
        .d-hero h1 em { font-style:italic; color:var(--primary); font-weight:600; }
        .d-hero p { font-size:17px; color:var(--muted); max-width:640px; margin:0 0 28px; }
        .d-hero-meta { display:flex; gap:24px; flex-wrap:wrap; font-size:14px; color:var(--muted); }
        .d-hero-meta-item { display:inline-flex; align-items:center; gap:8px; }
        .d-hero-meta-item strong { color:var(--text); font-weight:700; }
        @media(max-width:720px){ .d-hero h1 { font-size:34px; } .d-hero p { font-size:15px; } }

        /* === FILTER BAR === */
        .d-filter-wrap { padding:8px 0 32px; }
        .d-filter {
            background:var(--surface); border:1px solid var(--line);
            border-radius:18px; padding:14px;
            box-shadow:0 2px 12px rgba(126,88,191,.06);
            display:grid; grid-template-columns:2fr 1fr 1fr auto; gap:10px;
            align-items:center;
        }
        @media(max-width:780px){ .d-filter { grid-template-columns:1fr 1fr; } .d-filter > button { grid-column:1 / -1; } }
        @media(max-width:480px){ .d-filter { grid-template-columns:1fr; } }
        .d-filter-input, .d-filter-select {
            border:1px solid var(--line); background:#fcfbfd;
            border-radius:12px; padding:12px 14px;
            font:inherit; font-size:14px; color:var(--text);
            width:100%; outline:none;
            transition:border-color .15s, background .15s;
        }
        .d-filter-input:focus, .d-filter-select:focus {
            border-color:var(--primary-mid); background:#fff;
        }
        .d-filter-search-wrap { position:relative; }
        .d-filter-search-wrap .d-filter-input { padding-left:42px; }
        .d-filter-search-wrap svg {
            position:absolute; left:14px; top:50%; transform:translateY(-50%);
            color:var(--muted); pointer-events:none;
        }
        .d-filter button[type=submit] {
            background:var(--primary); color:#fff; border:none;
            border-radius:12px; padding:12px 22px;
            font:inherit; font-size:14px; font-weight:700;
            cursor:pointer; transition:background .15s;
            white-space:nowrap;
        }
        .d-filter button[type=submit]:hover { background:var(--primary-dark); }
        .d-active-filters {
            margin-top:14px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;
            font-size:13px; color:var(--muted);
        }
        .d-chip {
            display:inline-flex; align-items:center; gap:6px;
            background:var(--primary-soft); color:var(--primary-deep);
            padding:4px 10px 4px 12px; border-radius:999px;
            font-size:12px; font-weight:600;
        }
        .d-chip a { color:var(--primary-deep); line-height:0; padding:2px; border-radius:6px; }
        .d-chip a:hover { background:rgba(126,88,191,.15); text-decoration:none; }

        /* === RESULTS HEADER === */
        .d-results-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; }
        .d-results-head h2 { font-size:18px; font-weight:700; margin:0; color:var(--text); }
        .d-results-head .d-count { color:var(--muted); font-size:13px; font-weight:500; }

        /* === GRID === */
        .d-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:22px; }
        @media(max-width:900px){ .d-grid { grid-template-columns:repeat(2, 1fr); } }
        @media(max-width:600px){ .d-grid { grid-template-columns:1fr; } }

        /* === CARD === */
        .d-card {
            background:var(--surface); border:1px solid var(--line);
            border-radius:20px; padding:22px;
            transition:transform .2s, box-shadow .2s, border-color .2s;
            display:flex; flex-direction:column; gap:14px;
            position:relative; overflow:hidden;
        }
        .d-card::before {
            content:''; position:absolute; top:0; left:0; right:0; height:3px;
            background:linear-gradient(90deg, var(--primary), var(--primary-light));
            opacity:0; transition:opacity .2s;
        }
        .d-card:hover {
            transform:translateY(-3px);
            box-shadow:0 12px 30px rgba(126,88,191,.13);
            border-color:var(--primary-light);
        }
        .d-card:hover::before { opacity:1; }
        .d-card-head { display:flex; gap:14px; align-items:center; }
        .d-avatar {
            width:60px; height:60px; border-radius:50%;
            background:linear-gradient(135deg, var(--primary), var(--primary-deep));
            color:#fff; display:flex; align-items:center; justify-content:center;
            font-size:22px; font-weight:700; letter-spacing:.5px;
            flex-shrink:0; overflow:hidden;
        }
        .d-avatar img { width:100%; height:100%; object-fit:cover; }
        .d-name { font-size:17px; font-weight:700; margin:0 0 2px; color:var(--text); }
        .d-tagline { font-size:13px; color:var(--muted); margin:0; }
        .d-bio {
            font-size:13px; color:var(--muted); line-height:1.55;
            margin:0; min-height:42px;
        }
        .d-badges { display:flex; flex-wrap:wrap; gap:6px; }
        .d-badge {
            display:inline-flex; align-items:center; gap:4px;
            font-size:11px; font-weight:600; padding:4px 10px;
            border-radius:999px;
            background:var(--neutral-soft); color:var(--muted);
            border:1px solid var(--line-soft);
        }
        .d-badge--lang { background:#eef6ff; color:#1d4ed8; border-color:#dbeafe; }
        .d-badge--topic { background:var(--primary-soft); color:var(--primary-deep); border-color:#ddd0f3; }
        .d-card-meta {
            display:flex; justify-content:space-between; align-items:center;
            padding-top:14px; border-top:1px solid var(--line-soft);
            font-size:12px; color:var(--muted);
        }
        .d-card-meta-item { display:inline-flex; align-items:center; gap:5px; }
        .d-card-meta-item strong { color:var(--text); font-weight:700; }
        .d-card-cta {
            display:flex; align-items:center; justify-content:center; gap:8px;
            background:var(--primary); color:#fff !important;
            border:none; border-radius:12px;
            padding:12px 18px; font:inherit; font-size:14px; font-weight:700;
            cursor:pointer; text-decoration:none;
            transition:background .15s;
            width:100%;
        }
        .d-card-cta:hover { background:var(--primary-dark); text-decoration:none !important; }
        .d-card-cta-secondary {
            background:transparent; color:var(--primary) !important;
            border:1px solid var(--primary-light);
        }
        .d-card-cta-secondary:hover { background:var(--primary-soft); }

        /* === EMPTY STATE === */
        .d-empty {
            text-align:center; padding:60px 22px;
            background:var(--surface); border:1px dashed var(--line);
            border-radius:20px;
        }
        .d-empty-icon {
            width:64px; height:64px; margin:0 auto 16px;
            background:var(--primary-soft); color:var(--primary);
            border-radius:50%; display:flex; align-items:center; justify-content:center;
        }
        .d-empty h3 { font-size:20px; font-weight:700; margin:0 0 8px; }
        .d-empty p { color:var(--muted); margin:0 0 22px; max-width:440px; margin-left:auto; margin-right:auto; }

        /* === PAGINATION === */
        .d-pagination { display:flex; justify-content:center; padding:36px 0 0; }
        .d-pagination nav { display:flex; gap:6px; }
        .d-pagination a, .d-pagination span {
            display:inline-flex; align-items:center; justify-content:center;
            min-width:38px; height:38px; padding:0 12px;
            border-radius:10px; font-size:13px; font-weight:600;
            border:1px solid var(--line); background:var(--surface);
            color:var(--text);
        }
        .d-pagination a:hover { background:var(--primary-soft); border-color:var(--primary-light); text-decoration:none; }
        .d-pagination .active span, .d-pagination span[aria-current=page] {
            background:var(--primary); color:#fff; border-color:var(--primary);
        }
        .d-pagination .disabled span { opacity:.4; cursor:not-allowed; }

        /* === HOW IT WORKS === */
        .d-section { padding:80px 0; }
        .d-section-title { font-size:32px; font-weight:700; text-align:center; margin:0 0 12px; letter-spacing:-.8px; }
        .d-section-sub { text-align:center; color:var(--muted); max-width:560px; margin:0 auto 48px; font-size:15px; }
        .d-steps { display:grid; grid-template-columns:repeat(3, 1fr); gap:24px; }
        @media(max-width:780px){ .d-steps { grid-template-columns:1fr; } }
        .d-step {
            background:var(--surface); border:1px solid var(--line);
            border-radius:18px; padding:28px 24px; text-align:center;
            transition:transform .2s, box-shadow .2s;
        }
        .d-step:hover { transform:translateY(-3px); box-shadow:0 10px 25px rgba(126,88,191,.1); }
        .d-step-num {
            width:44px; height:44px; margin:0 auto 18px;
            background:var(--primary); color:#fff;
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            font-size:18px; font-weight:700;
        }
        .d-step h3 { font-size:18px; font-weight:700; margin:0 0 8px; }
        .d-step p { color:var(--muted); font-size:14px; margin:0; }

        /* === SOCIAL PROOF === */
        .d-trust {
            background:var(--surface); border:1px solid var(--line);
            border-radius:24px; padding:40px 32px;
            display:grid; grid-template-columns:repeat(4, 1fr); gap:24px;
            text-align:center;
        }
        @media(max-width:780px){ .d-trust { grid-template-columns:repeat(2, 1fr); } }
        .d-trust-stat strong { display:block; font-size:32px; font-weight:700; color:var(--primary); margin-bottom:4px; letter-spacing:-1px; }
        .d-trust-stat span { font-size:13px; color:var(--muted); }

        /* === FAQ === */
        .d-faq { max-width:780px; margin:0 auto; }
        .d-faq-item {
            background:var(--surface); border:1px solid var(--line);
            border-radius:14px; margin-bottom:12px;
            overflow:hidden;
        }
        .d-faq-item summary {
            padding:18px 22px; font-weight:600; cursor:pointer;
            display:flex; justify-content:space-between; align-items:center;
            list-style:none;
        }
        .d-faq-item summary::-webkit-details-marker { display:none; }
        .d-faq-item summary::after { content:'+'; font-size:22px; font-weight:300; color:var(--primary); transition:transform .2s; }
        .d-faq-item[open] summary::after { transform:rotate(45deg); }
        .d-faq-item-body { padding:0 22px 20px; color:var(--muted); font-size:14px; line-height:1.65; }

        /* === FOOTER === */
        .d-footer {
            background:#0f172a; color:rgba(255,255,255,.7);
            padding:48px 0 24px; font-size:13px; margin-top:60px;
        }
        .d-footer .container { display:grid; grid-template-columns:2fr 1fr 1fr; gap:40px; }
        @media(max-width:720px){ .d-footer .container { grid-template-columns:1fr; } }
        .d-footer h5 { color:#fff; font-size:13px; text-transform:uppercase; letter-spacing:.08em; margin:0 0 12px; }
        .d-footer ul { list-style:none; padding:0; margin:0; }
        .d-footer li { margin-bottom:8px; }
        .d-footer a { color:rgba(255,255,255,.7); }
        .d-footer a:hover { color:#fff; }
        .d-footer-bottom { border-top:1px solid rgba(255,255,255,.1); margin-top:32px; padding-top:24px; text-align:center; font-size:12px; opacity:.6; }
    </style>
</head>
<body>

{{-- ── NAV ────────────────────────────────────────────────────── --}}
<nav class="d-nav">
    <div class="d-nav-inner">
        <a href="/" class="d-logo">Mentör<span>DE</span></a>
        <div class="d-nav-links">
            <a href="/platform">Platform</a>
            <a href="/randevu">Uzmanlar</a>
            <a href="/sss">Sıkça Sorulan</a>
            <a href="/uni-match">UniMatch</a>
        </div>
        <a href="/randevu" class="d-nav-cta">Hemen Randevu Al</a>
    </div>
</nav>

{{-- ── HERO ────────────────────────────────────────────────────── --}}
<section class="d-hero">
    <div class="container">
        <div class="d-hero-badge">
            <x-icon name="sparkles" size="14" />
            <span>Marketplace · Uzman Danışmanlar</span>
        </div>
        <h1>Almanya yolculuğunda <em>uzman desteği</em> al</h1>
        <p>Üniversite başvurusu, vize süreci, belge hazırlığı ve kariyer planlaması için sertifikalı MentörDE uzmanlarıyla birebir tanışma görüşmesi planla. İlk görüşme ücretsiz.</p>
        <div class="d-hero-meta">
            <span class="d-hero-meta-item">
                <x-icon name="users" size="16" />
                <strong>{{ $totalCount }}</strong> aktif uzman
            </span>
            <span class="d-hero-meta-item">
                <x-icon name="calendar" size="16" />
                Hafta içi her gün müsaitlik
            </span>
            <span class="d-hero-meta-item">
                <x-icon name="shield-check" size="16" />
                Onaylı profil
            </span>
        </div>
    </div>
</section>

{{-- ── FILTER BAR ──────────────────────────────────────────────── --}}
<section class="d-filter-wrap">
    <div class="container">
        <form method="get" action="{{ route('booking.public.directory') }}" class="d-filter">
            <div class="d-filter-search-wrap">
                <x-icon name="search" size="18" />
                <input type="text" name="q" class="d-filter-input"
                       placeholder="Uzman adı, alan veya anahtar kelime ara"
                       value="{{ $filters['q'] }}"
                       aria-label="Uzman ara"
                       maxlength="120">
            </div>
            <select name="lang" class="d-filter-select" aria-label="Konuşma dili">
                <option value="">Tüm diller</option>
                @foreach($languages as $code => $label)
                    <option value="{{ $code }}" @selected($filters['lang'] === $code)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="topic" class="d-filter-select" aria-label="Uzmanlık konusu">
                <option value="">Tüm konular</option>
                @foreach($topics as $key => $label)
                    <option value="{{ $key }}" @selected($filters['topic'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit">Uzmanları Filtrele</button>
        </form>

        @if($filters['q'] !== '' || $filters['lang'] !== '' || $filters['topic'] !== '')
            <div class="d-active-filters">
                <span>Aktif filtreler:</span>
                @if($filters['q'] !== '')
                    <span class="d-chip">"{{ $filters['q'] }}"
                        <a href="{{ route('booking.public.directory', array_merge(request()->query(), ['q' => null])) }}" aria-label="Aramayı kaldır"><x-icon name="x" size="12" /></a>
                    </span>
                @endif
                @if($filters['lang'] !== '')
                    <span class="d-chip">{{ $languages[$filters['lang']] }}
                        <a href="{{ route('booking.public.directory', array_merge(request()->query(), ['lang' => null])) }}" aria-label="Dil filtresini kaldır"><x-icon name="x" size="12" /></a>
                    </span>
                @endif
                @if($filters['topic'] !== '')
                    <span class="d-chip">{{ $topics[$filters['topic']] }}
                        <a href="{{ route('booking.public.directory', array_merge(request()->query(), ['topic' => null])) }}" aria-label="Konu filtresini kaldır"><x-icon name="x" size="12" /></a>
                    </span>
                @endif
                <a href="{{ route('booking.public.directory') }}" style="margin-left:auto; font-size:12px; font-weight:600;">Tümünü temizle</a>
            </div>
        @endif
    </div>
</section>

{{-- ── RESULTS ─────────────────────────────────────────────────── --}}
<section style="padding-bottom:60px;">
    <div class="container">
        <div class="d-results-head">
            <h2>Uzmanlar</h2>
            <span class="d-count">{{ $paginator->total() }} sonuç</span>
        </div>

        @if(count($items) === 0)
            <div class="d-empty">
                <div class="d-empty-icon">
                    <x-icon name="users" size="32" />
                </div>
                <h3>Bu kriterlere uygun uzman bulunamadı</h3>
                <p>Filtreleri değiştirip tekrar deneyebilir veya bekleme listesine kaydolarak uygun uzman çıktığında haberdar olabilirsin.</p>
                <a href="{{ route('booking.landing') }}" class="d-card-cta" style="display:inline-flex; width:auto;">
                    <x-icon name="bell" size="16" />
                    Bekleme Listesine Katıl
                </a>
            </div>
        @else
            <div class="d-grid">
                @foreach($items as $item)
                    <article class="d-card">
                        <div class="d-card-head">
                            <div class="d-avatar">
                                @if($item['avatar'])
                                    <img src="{{ $item['avatar'] }}" alt="{{ $item['name'] }}" loading="lazy">
                                @else
                                    {{ $item['initials'] }}
                                @endif
                            </div>
                            <div style="flex:1; min-width:0;">
                                <h3 class="d-name">{{ $item['name'] }}</h3>
                                @if($item['tagline'])
                                    <p class="d-tagline">{{ $item['tagline'] }}</p>
                                @endif
                            </div>
                        </div>

                        @if($item['bio'])
                            <p class="d-bio">{{ $item['bio'] }}</p>
                        @endif

                        @if(!empty($item['languages']) || !empty($item['topics']))
                            <div class="d-badges">
                                @foreach($item['languages'] as $lang)
                                    @if(isset($languages[$lang]))
                                        <span class="d-badge d-badge--lang"><x-icon name="globe" size="11" /> {{ $languages[$lang] }}</span>
                                    @endif
                                @endforeach
                                @foreach($item['topics'] as $tp)
                                    @if(isset($topics[$tp]))
                                        <span class="d-badge d-badge--topic">{{ $topics[$tp] }}</span>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <div class="d-card-meta">
                            <span class="d-card-meta-item">
                                <x-icon name="clock" size="14" />
                                <strong>{{ $item['slot_minutes'] }}dk</strong> seans
                            </span>
                            @if(!empty($item['avg_rating']) && ($item['total_reviews'] ?? 0) > 0)
                                <span class="d-card-meta-item" style="color:#a16207;">
                                    <x-icon name="star-filled" size="14" />
                                    <strong>{{ number_format((float) $item['avg_rating'], 1) }}</strong>
                                    ({{ $item['total_reviews'] }})
                                </span>
                            @endif
                            @if($item['sessions'] > 0)
                                <span class="d-card-meta-item">
                                    <x-icon name="check" size="14" />
                                    <strong>{{ $item['sessions'] }}+</strong> görüşme
                                </span>
                            @endif
                            <span class="d-card-meta-item" style="color:var(--success-text); font-weight:600;">
                                {{ $item['price_label'] }}
                            </span>
                        </div>

                        <div style="display:flex; gap:8px; margin-top:14px;">
                            <a href="{{ route('booking.public.profile', ['slug' => $item['slug']]) }}"
                               class="d-card-cta-secondary"
                               style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px; padding:11px 14px; border-radius:8px; font-weight:600; font-size:13.5px;">
                                Profili Gör
                            </a>
                            <a href="{{ route('booking.public.show', ['slug' => $item['slug']]) }}"
                               class="d-card-cta"
                               style="flex:1.4;">
                                <x-icon name="calendar" size="16" />
                                Randevu Al
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="d-pagination">
                {{ $paginator->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</section>

{{-- ── HOW IT WORKS ────────────────────────────────────────────── --}}
<section class="d-section" style="background:var(--surface); border-top:1px solid var(--line);">
    <div class="container">
        <h2 class="d-section-title">Nasıl çalışır?</h2>
        <p class="d-section-sub">3 basit adımda uzman görüşmen hazır</p>
        <div class="d-steps">
            <div class="d-step">
                <div class="d-step-num">1</div>
                <h3>Uzmanını seç</h3>
                <p>Dil, konu ve uzmanlık alanına göre filtre uygula; sana en uygun uzmanı kart üzerinden incele.</p>
            </div>
            <div class="d-step">
                <div class="d-step-num">2</div>
                <h3>Müsait saati seç</h3>
                <p>Uzmanın takvimini gör, sana uygun günü ve saati seç. Ad-soyad ve email yeterli, kayıt zorunlu değil.</p>
            </div>
            <div class="d-step">
                <div class="d-step-num">3</div>
                <h3>Görüşmeye bağlan</h3>
                <p>Email ile gelen Google Meet linkinden randevu saatinde katıl. İptal etmek tek tıkla.</p>
            </div>
        </div>
    </div>
</section>

{{-- ── TRUST / SOCIAL PROOF ────────────────────────────────────── --}}
<section class="d-section" style="padding-top:40px;">
    <div class="container">
        <div class="d-trust">
            <div class="d-trust-stat">
                <strong>{{ $totalCount }}+</strong>
                <span>Aktif Uzman</span>
            </div>
            <div class="d-trust-stat">
                <strong>2.500+</strong>
                <span>Tamamlanan Görüşme</span>
            </div>
            <div class="d-trust-stat">
                <strong>4.8★</strong>
                <span>Ortalama Memnuniyet</span>
            </div>
            <div class="d-trust-stat">
                <strong>%96</strong>
                <span>Tavsiye Oranı</span>
            </div>
        </div>
    </div>
</section>

{{-- ── FAQ ─────────────────────────────────────────────────────── --}}
<section class="d-section" style="padding-top:40px;">
    <div class="container">
        <h2 class="d-section-title">Sıkça sorulanlar</h2>
        <p class="d-section-sub">Tanışma görüşmesi nasıl ilerler, ne kadar sürer, ne soracaksın?</p>
        <div class="d-faq">
            <details class="d-faq-item">
                <summary>Tanışma görüşmesi gerçekten ücretsiz mi?</summary>
                <div class="d-faq-item-body">Evet. İlk 30 dakikalık tanışma görüşmesi tamamen ücretsiz. Süreç hakkında bilgi alır, hedeflerinin uzmanın deneyimiyle ne kadar uyuştuğunu görürsün.</div>
            </details>
            <details class="d-faq-item">
                <summary>Görüşme nerede yapılır?</summary>
                <div class="d-faq-item-body">Tüm görüşmeler Google Meet üzerinden online yapılır. Randevuyu aldıktan sonra email'ine takvim daveti ve katılım linki gelir.</div>
            </details>
            <details class="d-faq-item">
                <summary>Randevumu iptal edebilir miyim?</summary>
                <div class="d-faq-item-body">Evet, randevudan en az 6 saat önce iptal email'indeki linkle tek tıkla iptal edebilirsin.</div>
            </details>
            <details class="d-faq-item">
                <summary>Hangi konularda destek alabilirim?</summary>
                <div class="d-faq-item-body">Üniversite seçimi (Bachelor & Master), uni-assist başvurusu, vize süreci, APS, sperrkonto, Anmeldung, kariyer ve sektör tavsiyeleri en sık sorulan konular.</div>
            </details>
            <details class="d-faq-item">
                <summary>Uzman olarak listeye katılmak istersem?</summary>
                <div class="d-faq-item-body"><a href="/platform">/platform</a> sayfasında danışman başvurusu seçeneğini görebilirsin. Başvurun değerlendirilip onay sonrası buraya eklenirsin.</div>
            </details>
        </div>
    </div>
</section>

{{-- ── FOOTER ──────────────────────────────────────────────────── --}}
<footer class="d-footer">
    <div class="container">
        <div>
            <div style="font-size:22px; font-weight:700; color:#fff; margin-bottom:10px;">Mentör<em style="color:var(--primary-light);">DE</em></div>
            <p style="margin:0;">Almanya'da yüksek eğitim yolculuğunda birebir uzman desteği. Sertifikalı danışmanlar, şeffaf süreç.</p>
        </div>
        <div>
            <h5>Platform</h5>
            <ul>
                <li><a href="/platform">Hakkında</a></li>
                <li><a href="/randevu">Uzmanlar</a></li>
                <li><a href="/uni-match">UniMatch</a></li>
                <li><a href="/sss">SSS</a></li>
            </ul>
        </div>
        <div>
            <h5>İletişim</h5>
            <ul>
                <li><a href="mailto:hello@mentorde.com">hello@mentorde.com</a></li>
                <li><a href="/randevu">Randevu Al</a></li>
            </ul>
        </div>
    </div>
    <div class="d-footer-bottom container" style="display:block;">
        © {{ date('Y') }} MentörDE — Tüm hakları saklıdır.
        · @include('partials.vendor-credit')
    </div>
</footer>

</body>
</html>
