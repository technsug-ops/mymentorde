@php
    /** @var \App\Models\DiscountCode $code */
    /** @var int $templateId */
    /** @var string $title, $subtitle, $ctaText, $disclaimer, $discountText, $applyUrl */
    $brandName = config('brand.name', 'MentorDE');
    $tagline   = config('brand.tagline', 'Almanya Eğitim Danışmanlığı');
    $shareUrl  = url('/promo/' . $code->code);
    $expiryStr = $code->valid_until?->format('d.m.Y');
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

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; font-family: 'Inter', system-ui, sans-serif; }
        body { min-height: 100vh; padding: 16px; }

        .promo-toolbar { max-width: 600px; margin: 0 auto 16px; display: flex; gap: 8px; flex-wrap: wrap; }
        .promo-toolbar button, .promo-toolbar a {
            padding: 10px 16px; font-size: 13px; font-weight: 600; border-radius: 8px;
            border: 1px solid rgba(255,255,255,.3); background: rgba(255,255,255,.15);
            color: white; cursor: pointer; text-decoration: none; backdrop-filter: blur(10px);
        }
        .promo-toolbar button:hover, .promo-toolbar a:hover { background: rgba(255,255,255,.25); }

        .promo-card-wrap { max-width: 600px; margin: 0 auto; }
        .promo-card {
            border-radius: 20px; padding: 36px 28px; box-shadow: 0 20px 60px rgba(0,0,0,.3);
            position: relative; overflow: hidden;
        }
        .promo-brand { font-size: 14px; font-weight: 700; opacity: .85; letter-spacing: .5px; margin-bottom: 4px; }
        .promo-tagline { font-size: 12px; opacity: .65; margin-bottom: 24px; }
        .promo-title { font-size: 32px; font-weight: 900; line-height: 1.1; margin: 0 0 12px 0; }
        .promo-subtitle { font-size: 15px; line-height: 1.5; opacity: .85; margin-bottom: 26px; }

        .promo-discount-bar { font-size: 14px; font-weight: 800; padding: 10px 16px;
            border-radius: 999px; display: inline-block; letter-spacing: 1px; margin-bottom: 16px; }

        .promo-code-box {
            border: 2px dashed rgba(255,255,255,.5); border-radius: 14px;
            padding: 20px; text-align: center; margin: 22px 0;
            background: rgba(255,255,255,.1);
        }
        .promo-code-label { font-size: 11px; opacity: .7; letter-spacing: 1px; margin-bottom: 6px; }
        .promo-code-value {
            font-family: 'Inter', monospace; font-size: 36px; font-weight: 900;
            letter-spacing: 4px; line-height: 1;
        }

        .promo-expiry { font-size: 12px; opacity: .7; margin-bottom: 20px; }
        .promo-cta {
            display: block; width: 100%; padding: 16px; font-size: 16px; font-weight: 700;
            border-radius: 12px; text-align: center; text-decoration: none;
            border: none; cursor: pointer; letter-spacing: .5px;
            transition: transform .15s;
        }
        .promo-cta:hover { transform: translateY(-2px); }
        .promo-disclaimer { font-size: 11px; opacity: .55; margin-top: 18px; line-height: 1.5; text-align: center; }

        @media print { .promo-toolbar { display:none; } body { padding:0; } }
        @media (max-width: 500px) {
            .promo-card { padding: 28px 22px; }
            .promo-title { font-size: 26px; }
            .promo-code-value { font-size: 28px; letter-spacing: 3px; }
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
            html2canvas(card, { scale: 2, backgroundColor: null, useCORS: true }).then(function(canvas){
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
