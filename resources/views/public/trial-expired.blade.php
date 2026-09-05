<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Trial süresi doldu — {{ $brandName }}</title>
@include('partials.favicon')
<meta name="robots" content="noindex,nofollow">

<link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --primary:#7e58bf;
    --primary-dark:#6c47a8;
    --primary-deep:#5a3a8d;
    --primary-soft:#efe9fb;
    --warn:#ea580c;
    --danger:#dc2626;
    --text:#1a1325;
    --muted:#6b6377;
    --line:#e3dcec;
    --neutral-soft:#faf9f5;
    --font-base:"Space Grotesk", "Plus Jakarta Sans", system-ui, sans-serif;
}
* { box-sizing:border-box; }
html, body { margin:0; padding:0; }
body {
    font-family:var(--font-base); color:var(--text);
    background:linear-gradient(180deg, #fff7ed 0%, #faf9f5 60%, #e9e7e2 100%);
    line-height:1.6; font-size:15px;
    -webkit-font-smoothing:antialiased;
    min-height:100vh;
    display:flex; flex-direction:column;
}

.p-nav { background:rgba(255,255,255,.92); backdrop-filter:blur(12px); border-bottom:1px solid var(--line); }
.p-nav-inner { max-width:1100px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; padding:14px 22px; }
.p-logo { font-size:26px; color:var(--primary); font-weight:700; letter-spacing:-.5px; }
.p-logo span { color:#b79ae9; font-style:italic; font-weight:600; }
.nav-link { font-size:13px; color:var(--muted); font-weight:600; }

.wrap { flex:1; max-width:780px; margin:0 auto; padding:60px 22px 80px; text-align:center; }

.icon {
    width:90px; height:90px; margin:0 auto 24px;
    background:linear-gradient(135deg, #fb923c, #ea580c);
    border-radius:24px; display:flex; align-items:center; justify-content:center;
    font-size:48px; box-shadow:0 16px 40px rgba(234,88,12,.25);
}

.kicker {
    display:inline-block; color:var(--warn); text-transform:uppercase;
    letter-spacing:.18em; font-size:12px; font-weight:800; margin-bottom:18px;
    background:#ffedd5; padding:6px 14px; border-radius:20px;
}
h1 {
    font-family:var(--font-base); font-style:italic; font-weight:600;
    font-size:clamp(32px, 4.5vw, 48px); line-height:1.1; color:var(--primary-deep);
    letter-spacing:-1.5px; margin:0 0 18px;
}
.lead {
    font-size:17px; color:var(--muted); max-width:580px; margin:0 auto 36px;
    line-height:1.55;
}

.data-safe {
    background:#fff; border:1px solid var(--line); border-radius:14px;
    padding:24px 28px; max-width:560px; margin:0 auto 36px;
    display:flex; align-items:center; gap:16px; text-align:left;
    box-shadow:0 6px 24px rgba(126,88,191,.06);
}
.data-safe .ico { font-size:32px; flex:0 0 auto; }
.data-safe strong { display:block; font-size:15px; color:var(--text); margin-bottom:2px; }
.data-safe span { font-size:13px; color:var(--muted); line-height:1.5; }

.cta-row {
    display:flex; gap:12px; justify-content:center; flex-wrap:wrap; margin-bottom:36px;
}
.btn-primary {
    padding:16px 32px;
    background:linear-gradient(140deg, #7e58bf 0%, #5a3a8d 100%);
    color:#fff; border-radius:12px; font-size:15px; font-weight:800;
    text-decoration:none; box-shadow:0 12px 32px rgba(126,88,191,.32);
    transition:transform .15s, box-shadow .15s;
    display:inline-flex; align-items:center; gap:8px;
}
.btn-primary:hover { transform:translateY(-2px); box-shadow:0 18px 44px rgba(126,88,191,.4); }
.btn-secondary {
    padding:16px 24px; background:#fff; color:var(--primary-deep);
    border:1.5px solid var(--line); border-radius:12px; font-size:14px; font-weight:700;
    text-decoration:none; display:inline-flex; align-items:center; gap:8px;
}
.btn-secondary:hover { border-color:var(--primary); }

.expired-info {
    background:#fff; border:1px solid var(--line); border-radius:14px;
    padding:18px 22px; max-width:560px; margin:0 auto 24px;
    font-size:13.5px; color:var(--muted); line-height:1.55;
}
.expired-info strong { color:var(--text); }

.foot {
    text-align:center; padding:28px 22px; border-top:1px solid var(--line);
    font-size:12.5px; color:var(--muted); background:#fff;
}
.foot a { color:var(--primary); font-weight:600; }
</style>
</head>
<body>

<nav class="p-nav">
    <div class="p-nav-inner">
        <a href="/fiyatlar" class="p-logo">{{ $brandName }}<span>.</span></a>
        <div>
            <form method="POST" action="/logout" style="display:inline;">
                @csrf
                <button type="submit" class="nav-link" style="background:none;border:none;cursor:pointer;font-family:inherit;">
                    Çıkış yap
                </button>
            </form>
        </div>
    </div>
</nav>

<div class="wrap">
    <div class="icon">⏰</div>
    <div class="kicker">TRIAL SÜRESİ DOLDU</div>

    <h1>
        @if($company)
            {{ $company->name }} hesabının 14 günlük trial süresi bitti
        @else
            Trial sürenin bitti
        @endif
    </h1>

    <p class="lead">
        Tüm Gold özelliklerini 14 gün boyunca ücretsiz test ettin. Hesabını açık tutmak ve devam etmek için bir plan seç — yükseltme anında etkili olur.
    </p>

    <div class="data-safe">
        <div class="ico">🔒</div>
        <div>
            <strong>Verilerin tamamen güvende</strong>
            <span>Tüm öğrencilerin, sözleşmelerin, danışman hesapların ve modül ayarların korunuyor. Plan seçimi yaptığın anda her şey kaldığı yerden devam eder.</span>
        </div>
    </div>

    @if($expiredDaysAgo !== null)
        <div class="expired-info">
            <strong>Trial bitiş:</strong>
            @if($company && $company->trial_ends_at)
                {{ $company->trial_ends_at->locale('tr')->isoFormat('D MMMM YYYY') }}
                @if($expiredDaysAgo > 0)
                    — {{ $expiredDaysAgo }} gün önce.
                @else
                    — bugün.
                @endif
            @endif
        </div>
    @endif

    <div class="cta-row">
        <a href="{{ $planUrl }}" class="btn-primary">
            🚀 Plan Seç ve Devam Et
        </a>
        <a href="/fiyatlar" class="btn-secondary">
            Fiyatları gör
        </a>
    </div>

    <div style="font-size:13px;color:var(--muted);">
        Sorularınız mı var? <a href="mailto:{{ $supportEmail }}" style="color:var(--primary);font-weight:700;">{{ $supportEmail }}</a> üzerinden bize ulaşın — genelde 1 saat içinde döneriz.
    </div>
</div>

<footer class="foot">
    © {{ date('Y') }} {{ $brandName }} ·
    <a href="/fiyatlar">Planlar</a> ·
    <a href="/legal/privacy">Gizlilik</a> ·
    <a href="/durum">Sistem Durumu</a> ·
    @include('partials.vendor-credit')
</footer>

</body>
</html>
