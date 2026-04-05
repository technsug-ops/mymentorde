<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MentorDE - Almanya Başvuru Süreci</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f9fafd;
            --surface: #ffffff;
            --text: #12233a;
            --muted: #5e7187;
            --primary: #5b2e91;
            --primary-soft: #f1e8fb;
            --accent: #e8b931;
            --line: #d9e2ee;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Plus Jakarta Sans", -apple-system, sans-serif;
            color: var(--text);
            background: linear-gradient(140deg, #f7f3ff 0%, #f9fafd 42%, #fff8e8 100%);
        }
        .wrap { max-width: 1080px; margin: 0 auto; padding: 20px; }
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
        }
        .logo {
            font-family: "DM Serif Display", serif;
            color: var(--primary);
            font-size: 28px;
            text-decoration: none;
        }
        .logo span { color: var(--accent); }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid transparent;
            padding: 10px 14px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-primary {
            background: var(--primary);
            color: #fff;
        }
        .btn-ghost {
            background: #fff;
            border-color: var(--line);
            color: var(--text);
        }
        .hero {
            margin-top: 18px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 28px;
        }
        .badge {
            display: inline-block;
            background: #e8f5ed;
            color: #2d8b55;
            padding: 6px 10px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 12px;
        }
        h1 {
            margin: 14px 0 10px;
            font-family: "DM Serif Display", serif;
            font-size: clamp(34px, 5vw, 50px);
            line-height: 1.1;
        }
        h1 em { color: var(--primary); font-style: normal; }
        .sub { color: var(--muted); max-width: 780px; line-height: 1.7; }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        .grid {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px;
        }
        .card h3 { margin: 0 0 8px; }
        .muted { color: var(--muted); font-size: 14px; }
        .footer-note {
            margin-top: 18px;
            color: var(--muted);
            font-size: 12px;
        }
        @media (max-width: 880px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <header class="nav">
        <a class="logo" href="/landing/mentorde">Mentor<span>DE</span></a>
        <a class="btn btn-ghost" href="/apply" data-apply-link>Başvuru Formu</a>
    </header>

    <section class="hero">
        <span class="badge">%95+ universite kabul orani</span>
        <h1>Almanya sürecini <em>adım adım</em> takip edin</h1>
        <p class="sub">
            MentorDE ile başvuru, belge, outcome ve bildirim süreçleri tek yerde toplanir.
            Reklam kaynak veriniz UTM ile otomatik takip edilir, başvuruya manuel kampanya secimi gerekmez.
        </p>
        <div class="actions">
            <a class="btn btn-primary" href="/apply" data-apply-link>Ücretsiz On Görüşme Al</a>
            <a class="btn btn-ghost" href="/apply" data-apply-link>Başvuruya Basla</a>
        </div>
    </section>

    <section class="grid">
        <article class="card">
            <h3>1. Kayıt ve Eslesme</h3>
            <p class="muted">Form verisi + UTM + tıklama kimligi birlikte kaydedilir.</p>
        </article>
        <article class="card">
            <h3>2. Dönüşüm Takibi</h3>
            <p class="muted">Guest -> Student dönüşümunde funnel adımlari otomatik güncellenir.</p>
        </article>
        <article class="card">
            <h3>3. KPI ve Rapor</h3>
            <p class="muted">Marketing panelinde kaynak, kampanya, CPL/CPA ve rapor indirme mevcuttur.</p>
        </article>
    </section>

    <p class="footer-note">
        Not: Bu landing sayfasi reklam linkleri için UTM passthrough kullanir. Gelen query parametreleri form linklerine tasinir.
    </p>
</div>

<script defer src="{{ Vite::asset('resources/js/landing-utm.js') }}"></script>
@include('partials.cookie-consent')
</body>
</html>
