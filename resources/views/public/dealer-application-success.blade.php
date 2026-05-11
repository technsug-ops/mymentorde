<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@php $brand = $brandName ?? config('brand.name', 'MentorDE'); @endphp
<title>Başvurunuz Alındı — {{ $brand }}</title>
@include('partials.favicon')
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary:#7e58bf; --primary-dark:#6c47a8; --primary-deep:#5a3a8d;
    --primary-soft:#efe9fb; --neutral:#e9e7e2; --accent:#7e58bf;
    --text:#1a1325; --muted:#6b6377; --success:#16a34a; --line:#e3dcec;
    --font-base:"Space Grotesk", "Plus Jakarta Sans", -apple-system, sans-serif;
}
* { box-sizing:border-box; }
html, body { margin:0; padding:0; }
body { font-family:var(--font-base); background:linear-gradient(140deg, #f7f3ff 0%, #faf9f5 50%, #e9e7e2 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; -webkit-font-smoothing:antialiased; }
.wrap { max-width:620px; width:100%; text-align:center; }
.icon-success { font-size:80px; margin-bottom:20px; animation:pop .5s ease-out; }
@keyframes pop { 0% { transform:scale(0); } 70% { transform:scale(1.1); } 100% { transform:scale(1); } }
h1 { font-family:var(--font-base); font-weight:700; font-size:40px; color:var(--primary-deep); margin:0 0 14px; line-height:1.15; letter-spacing:-1px; }
.sub { color:var(--muted); font-size:16px; margin:0 0 30px; }

.card { background:#fff; border:1px solid var(--line); border-radius:20px; padding:32px; box-shadow:0 16px 40px rgba(126,88,191,.10); text-align:left; }
.ref-box { background:var(--primary-soft); border-radius:12px; padding:18px; margin-bottom:20px; text-align:center; border:1px solid rgba(126,88,191,.2); }
.ref-box .lbl { font-size:11px; text-transform:uppercase; letter-spacing:.12em; color:var(--primary); font-weight:700; }
.ref-box .num { font-family:var(--font-base); font-weight:700; font-size:32px; color:var(--primary-deep); margin-top:4px; letter-spacing:-1px; }

h3 { font-size:14px; text-transform:uppercase; letter-spacing:.08em; color:var(--primary); margin:20px 0 10px; }
ul.steps { padding-left:20px; color:var(--text); font-size:14px; }
ul.steps li { margin-bottom:8px; }
ul.steps strong { color:var(--primary-deep); }

.cta-row { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; margin-top:24px; }
.btn-primary { background:var(--primary); color:#fff; padding:14px 24px; border-radius:10px; font-weight:700; font-size:14px; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
.btn-ghost { border:2px solid var(--primary); color:var(--primary); padding:12px 22px; border-radius:10px; font-weight:700; font-size:14px; text-decoration:none; display:inline-flex; align-items:center; gap:8px; background:#fff; }
</style>
</head>
<body>

<div class="wrap">
    <div class="icon-success">🎉</div>
    <h1>Başvurunuz Alındı!</h1>
    <p class="sub">
        @if ($app)
            Hoş geldin <strong>{{ $app->first_name }}</strong> — başvurun sistemimize kaydedildi.
        @else
            Tebrikler! Başvurun sistemimize kaydedildi.
        @endif
    </p>

    <div class="card">
        @if ($app)
            <div class="ref-box">
                <div class="lbl">Başvuru Referans No</div>
                <div class="num">{{ $app->reference_code }}</div>
            </div>
        @endif

        <h3>📅 Sonraki Adımlar</h3>
        <ul class="steps">
            <li><strong>48 saat içinde:</strong> Ekibimiz email/WhatsApp'tan sizinle iletişime geçecek.</li>
            <li><strong>Kısa değerlendirme görüşmesi:</strong> Plan önerisi + sorularınızın cevaplanması (15 dk).</li>
            <li><strong>Onay + Panel Erişimi:</strong> Hesabınıza <strong>100€ Hoş Geldin Bonusu</strong> tanımlanır.</li>
            <li><strong>İlk Yönlendirme:</strong> Pazarlama materyalleriniz panelinizde hazır.</li>
        </ul>

        <h3>📞 Bu Sırada</h3>
        <ul class="steps">
            <li>Almanya eğitim fırsatlarını çevrenize anlatmaya başlayabilirsiniz — onay sonrası yönlendirdiğiniz adaylar hemen sisteme alınır.</li>
            <li>Sorularınız için hızlıca ulaşın: <strong>+49 1520 325 3691</strong></li>
        </ul>

        <div class="cta-row">
            <a href="https://wa.me/4915203253691?text=Merhaba%2C%20sat%C4%B1%C5%9F%20orta%C4%9Fl%C4%B1%C4%9F%C4%B1%20ba%C5%9Fvurumla%20ilgili%20bilgi%20almak%20istiyorum.@if($app)%20Referans%3A%20{{ $app->reference_code }}@endif"
               target="_blank" rel="noopener" class="btn-primary">
                💬 WhatsApp'tan Hızlı Destek
            </a>
            <a href="{{ route('public.dealer-landing') }}" class="btn-ghost">
                ← Program sayfasına dön
            </a>
        </div>
    </div>

    <p style="font-size:12px; color:var(--muted); margin-top:20px;">
        Email: <a href="mailto:info@mentorde.com" style="color:var(--primary);">info@mentorde.com</a>
    </p>
</div>

<x-analytics.posthog-snippet :portal="'public'" />
<x-analytics.consent-banner />

</body>
</html>
