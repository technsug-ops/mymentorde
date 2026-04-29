@php
    /** @var \App\Models\DiscountCode $code */
    /** @var int $templateId */
    /** @var string $title, $subtitle, $ctaText, $disclaimer, $discountText, $applyUrl */
    $brandName    = config('brand.name', 'MentorDE');
    $brandAccent  = config('brand.accent', 'DE');
    $brandShort   = str_ireplace($brandAccent, '', $brandName);
    $tagline      = config('brand.tagline', 'Almanya Eğitim Danışmanlığı');
    $logoUrl      = config('brand.logo_url') ?: null; // boşsa SVG wordmark
    $shareUrl     = url('/promo/' . $code->code);
    $expiryStr    = $code->valid_until?->format('d.m.Y');
    $daysLeft     = $code->valid_until ? max(0, (int) now()->diffInDays($code->valid_until, false)) : null;
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title }} — {{ $brandName }}</title>

    {{-- Open Graph (WhatsApp, Insta, FB önizleme) --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $shareUrl }}">
    <meta property="og:title" content="{{ $title }} — {{ $brandName }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($subtitle, 200) }}">
    <meta property="og:locale" content="tr_TR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit($subtitle, 200) }}">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Bebas+Neue&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; font-family: 'Inter', -apple-system, system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        body { min-height: 100vh; padding: 16px; }

        /* ── Toolbar ───────────────────────────────────────────────── */
        .promo-toolbar { max-width: 600px; margin: 0 auto 16px; display: flex; gap: 8px; flex-wrap: wrap; }
        .promo-toolbar button, .promo-toolbar a {
            padding: 10px 16px; font-size: 13px; font-weight: 600; border-radius: 8px;
            border: 1px solid rgba(255,255,255,.3); background: rgba(255,255,255,.18);
            color: white; cursor: pointer; text-decoration: none; backdrop-filter: blur(10px);
        }
        .promo-toolbar button:hover, .promo-toolbar a:hover { background: rgba(255,255,255,.28); }

        /* ── Card frame ──────────────────────────────────────────── */
        .promo-card-wrap { max-width: 600px; margin: 0 auto; }
        .promo-card {
            border-radius: 24px; padding: 0; box-shadow: 0 25px 70px rgba(0,0,0,.35);
            position: relative; overflow: hidden; isolation: isolate;
        }
        .promo-card-inner { padding: 36px 32px; position: relative; z-index: 2; }

        /* ── Logo ────────────────────────────────────────────────── */
        .promo-logo {
            display: flex; align-items: center; gap: 10px; margin-bottom: 4px;
        }
        .promo-logo .logo-mark {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 900; font-size: 16px; letter-spacing: -.5px;
            background: rgba(255,255,255,.95); color: var(--brand-color, #6d28d9);
            box-shadow: 0 4px 12px rgba(0,0,0,.15);
        }
        .promo-logo .logo-text {
            font-size: 18px; font-weight: 800; letter-spacing: -.3px;
        }
        .promo-logo .logo-text .accent { font-weight: 900; opacity: .9; }
        .promo-logo img.logo-img { height: 36px; width: auto; }

        .promo-tagline { font-size: 11.5px; opacity: .7; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 22px; }

        /* ── Slots ──────────────────────────────────────────────── */
        .promo-discount-bar { font-size: 13px; font-weight: 800; padding: 8px 16px;
            border-radius: 999px; display: inline-block; letter-spacing: 1.5px; margin-bottom: 14px; text-transform: uppercase; }
        .promo-title { font-size: 32px; font-weight: 900; line-height: 1.08; margin: 0 0 12px 0; letter-spacing: -.5px; }
        .promo-subtitle { font-size: 15px; line-height: 1.55; opacity: .88; margin-bottom: 24px; }

        .promo-code-box {
            border: 2px dashed rgba(255,255,255,.5); border-radius: 16px;
            padding: 22px; text-align: center; margin: 22px 0; position: relative;
            background: rgba(255,255,255,.1);
        }
        .promo-code-box::before, .promo-code-box::after {
            content: ''; position: absolute; top: 50%; transform: translateY(-50%);
            width: 24px; height: 24px; border-radius: 50%; background: var(--card-bg-circle, #6d28d9);
        }
        .promo-code-box::before { left: -12px; }
        .promo-code-box::after  { right: -12px; }

        .promo-code-label { font-size: 11px; opacity: .7; letter-spacing: 2px; margin-bottom: 8px; text-transform: uppercase; }
        .promo-code-value {
            font-family: 'Inter', monospace; font-size: 36px; font-weight: 900;
            letter-spacing: 5px; line-height: 1;
        }

        .promo-expiry { font-size: 12.5px; opacity: .8; margin-bottom: 22px; display:flex; align-items:center; gap:6px; }
        .promo-expiry .pill { background: rgba(0,0,0,.25); padding: 3px 10px; border-radius: 999px; font-weight: 700; font-size: 11.5px; letter-spacing: .5px; }

        .promo-cta {
            display: block; width: 100%; padding: 17px; font-size: 15px; font-weight: 800;
            border-radius: 14px; text-align: center; text-decoration: none;
            border: none; cursor: pointer; letter-spacing: .8px; text-transform: uppercase;
            transition: transform .15s, box-shadow .15s;
            box-shadow: 0 8px 20px rgba(0,0,0,.15);
        }
        .promo-cta:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(0,0,0,.25); }
        .promo-disclaimer { font-size: 11px; opacity: .6; margin-top: 18px; line-height: 1.5; text-align: center; }

        /* ── Decorative SVGs (decorative, common positioning) ──── */
        .promo-deco { position: absolute; pointer-events: none; z-index: 1; }
        .promo-deco-tr { top: -40px; right: -40px; }
        .promo-deco-bl { bottom: -50px; left: -50px; }
        .promo-deco-br { bottom: -40px; right: -40px; }
        .promo-deco-tl { top: -40px; left: -40px; }

        /* ── Bayrak şeridi (Almanya — Classic + Premium) ───────── */
        .promo-flag-strip { display: flex; height: 6px; border-radius: 0; overflow: hidden; margin-bottom: 26px; }
        .promo-flag-strip > div { flex: 1; }
        .promo-flag-black { background: #1a1a1a; }
        .promo-flag-red   { background: #dc2626; }
        .promo-flag-gold  { background: #fbbf24; }

        @media print { .promo-toolbar { display:none; } body { padding:0; } }
        @media (max-width: 500px) {
            .promo-card-inner { padding: 28px 22px; }
            .promo-title { font-size: 26px; }
            .promo-code-value { font-size: 28px; letter-spacing: 4px; }
        }

        @include('promo.templates.styles')
    </style>
</head>
<body class="promo-tpl-{{ $templateId }}">

    <div class="promo-toolbar">
        <button id="downloadBtn" type="button">📥 Görsel İndir</button>
        <button id="copyLinkBtn" type="button" data-url="{{ $shareUrl }}">🔗 Linki Kopyala</button>
        <a href="{{ $applyUrl }}">✨ Başvuruyu Aç</a>
    </div>

    <div class="promo-card-wrap">
        <div id="promoCard" class="promo-card">
            @include('promo.templates.' . $templateId)
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script>
    (function(){
        var dl = document.getElementById('downloadBtn');
        var cp = document.getElementById('copyLinkBtn');
        var card = document.getElementById('promoCard');

        if (dl) dl.addEventListener('click', function(){
            var orig = dl.textContent; dl.textContent = '⏳ Görsel hazırlanıyor…'; dl.disabled = true;
            html2canvas(card, { scale: 2, backgroundColor: null, useCORS: true, logging: false }).then(function(canvas){
                var link = document.createElement('a');
                link.download = '{{ $brandName }}-{{ $code->code }}.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                dl.textContent = orig; dl.disabled = false;
            }).catch(function(e){
                console.error(e);
                dl.textContent = '⚠ Hata — tekrar dene';
                setTimeout(function(){ dl.textContent = orig; dl.disabled = false; }, 2000);
            });
        });

        if (cp) cp.addEventListener('click', function(){
            var url = cp.getAttribute('data-url');
            var done = function(){
                var orig = cp.textContent; cp.textContent = '✓ Kopyalandı!';
                setTimeout(function(){ cp.textContent = orig; }, 1500);
            };
            if (navigator.clipboard) navigator.clipboard.writeText(url).then(done);
            else {
                var ta = document.createElement('textarea'); ta.value = url; document.body.appendChild(ta);
                ta.select(); document.execCommand('copy'); document.body.removeChild(ta); done();
            }
        });
    })();
    </script>
</body>
</html>
