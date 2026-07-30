{{--
    PARTNER TEMPLATE · MERCAN ENERJİ
    Oyunbaz ve enerjik: sticker rozetler (hafif döndürülmüş), gradient CTA, kesikli (dashed)
    ayraçlar, sert offset gölgeler, bento hizmet ızgarası. Kaynak tasarım: "Mercan Enerji" (DC).

    Kurallar: JS YOK (S.S.S. <details>), font SADECE lokal, uydurma veri YOK.
    Bölümler modüler: mercan/sections/*.blade.php — sıra + aç/kapa $sections'tan.
    Veri sözleşmesi: App\Support\PartnerSiteData::forDealer — bkz. _starter.blade.php
--}}
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    $accent   = \App\Support\PartnerSiteData::accent($accentColor ?? null);
    $siteName = $brandName ?? config('brand.name', 'MentorDE');
    $icon     = fn (string $k) => \App\Support\PartnerSiteData::icon($k);
    $waDigits = $whatsapp ? preg_replace('/\D+/', '', $whatsapp) : '';
    $waUrl    = $waDigits !== '' ? 'https://wa.me/' . $waDigits : null;
    $heroImg  = !empty($dealer?->site_hero_image_path)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($dealer->site_hero_image_path)
        : null;
    // Bento: ilk 2 hizmet büyük kart, kalanlar küçük kart
    $svcBig   = array_slice($services, 0, 2);
    $svcSmall = array_slice($services, 2);

    /** Satır başına kart sayısı — eşit sıralar; eksik son sıra ortalanır. */
    $cols = function (int $n): int {
        if ($n <= 0)      { return 1; }
        if ($n <= 3)      { return $n; }
        if ($n % 4 === 0) { return 4; }
        if ($n % 3 === 0) { return 3; }
        return $n % 2 === 0 ? min(intdiv($n, 2), 4) : 3;
    };
@endphp
<title>{{ $siteName }} — Almanya Eğitim Danışmanlığı</title>
@include('partials.favicon')
<meta name="description" content="{{ Str::limit(strip_tags($heroSubtitle ?? ''), 155) }}">
<meta name="robots" content="index, follow">
<meta property="og:title" content="{{ $siteName }} — Almanya Eğitim Danışmanlığı">
<meta property="og:description" content="{{ Str::limit(strip_tags($heroSubtitle ?? ''), 200) }}">
<meta property="og:type" content="website">
<meta name="theme-color" content="{{ $accent }}">
{{-- Fontlar SADECE lokal (DSGVO) --}}
<link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">
<style>
:root{
    --accent:{{ $accent }};
    /* İkinci renk accent'ten türetilir (gradient + ikincil vurgular) */
    --accent-2:color-mix(in srgb, var(--accent) 62%, #ffb14c);
    --accent-deep:color-mix(in srgb, var(--accent) 80%, #200608);
    --accent-pale:color-mix(in srgb, var(--accent) 13%, #fff);
    --tint:color-mix(in srgb, var(--accent) 5%, #fff);
    --line:color-mix(in srgb, var(--accent) 20%, #fff);
    --ink:color-mix(in srgb, var(--accent) 14%, #261013);
    --ink-2:color-mix(in srgb, var(--accent) 18%, #4a2f33);
    --muted:color-mix(in srgb, var(--accent) 20%, #6f5457);
    --faint:color-mix(in srgb, var(--accent) 30%, #a08589);
    --on-ink:#fff8f7;
    --on-ink-soft:color-mix(in srgb, var(--accent) 34%, #c8aeb1);
    --grad:linear-gradient(120deg, var(--accent), var(--accent-2));
    --display:"Sora","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --body:"IBM Plex Sans","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --mono:"IBM Plex Mono",ui-monospace,SFMono-Regular,monospace;
    --maxw:1280px;
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;scroll-behavior:smooth;overflow-x:hidden;}
body{background:var(--tint);color:var(--ink);font-family:var(--body);font-size:15px;line-height:1.6;-webkit-font-smoothing:antialiased;}
svg{width:1em;height:1em;}
img{max-width:100%;}
a{color:var(--accent-deep);}
.wrap{max-width:var(--maxw);margin:0 auto;padding:0 26px;}
.sec{padding:60px 0;}
.sec-white{background:#fff;border-top:2px dashed var(--line);border-bottom:2px dashed var(--line);}
.lbl{font:700 12px/1 var(--mono);letter-spacing:.1em;color:var(--accent);text-transform:uppercase;}
h1,h2,h3{margin:0;font-family:var(--display);}
.h2{font:800 clamp(25px,3.4vw,36px)/1.12 var(--display);letter-spacing:-1.4px;margin:12px 0 0;text-wrap:balance;}
.sec-head{text-align:center;max-width:600px;margin:0 auto 38px;}
.sec-head p{font-size:15px;color:var(--muted);margin:10px 0 0;}

/* ─── Butonlar ─── */
.btn{display:inline-flex;align-items:center;gap:9px;text-decoration:none;font:700 16px/1 var(--body);padding:17px 34px;border-radius:30px;transition:transform .18s ease,box-shadow .18s ease;}
.btn-grad{background:var(--grad);color:#fff;box-shadow:0 14px 30px color-mix(in srgb, var(--accent) 34%, transparent);}
.btn-grad:hover{transform:translateY(-2px) rotate(-1deg);box-shadow:0 18px 38px color-mix(in srgb, var(--accent) 44%, transparent);}
.btn-line{background:#fff;border:2px solid var(--line);color:var(--ink);font-size:15px;padding:15px 28px;}
.btn-line:hover{border-color:var(--accent);transform:translateY(-2px);}

/* ─── Nav ─── */
.nav{position:sticky;top:0;z-index:50;background:color-mix(in srgb, var(--tint) 92%, transparent);backdrop-filter:blur(12px);border-bottom:1px solid var(--line);}
.nav-in{max-width:var(--maxw);margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:16px 26px;}
.brand{display:flex;align-items:center;gap:10px;text-decoration:none;}
.brand-name{font:800 20px/1 var(--display);color:var(--accent);}
.brand img{max-height:38px;width:auto;display:block;}
.nav-links{display:flex;align-items:center;gap:24px;flex-wrap:wrap;}
.nav-links a{text-decoration:none;font:600 14px/1 var(--body);color:var(--muted);}
.nav-links a:hover{color:var(--accent);}
.nav-cta{background:var(--accent);color:#fff !important;font-weight:700;font-size:13px;padding:12px 20px;border-radius:30px;}
.nav-cta:hover{filter:brightness(1.06);}
@media(max-width:760px){.nav-links .nav-link{display:none;}}

/* ─── Hero (ortalanmış + sticker rozetler) ─── */
.hero{max-width:880px;margin:0 auto;text-align:center;padding:60px 26px 54px;}
.stickers{display:inline-flex;gap:10px;margin-bottom:24px;flex-wrap:wrap;justify-content:center;}
.sticker{display:inline-flex;align-items:center;gap:7px;font:700 12px/1 var(--body);background:var(--accent-pale);color:var(--accent-deep);padding:9px 15px;border-radius:30px;box-shadow:3px 3px 0 color-mix(in srgb, var(--accent) 25%, #fff);}
.sticker:nth-child(odd){transform:rotate(-2deg);}
.sticker:nth-child(even){transform:rotate(1.5deg);background:color-mix(in srgb, var(--accent-2) 16%, #fff);color:color-mix(in srgb, var(--accent-2) 82%, #000);box-shadow:3px 3px 0 color-mix(in srgb, var(--accent-2) 28%, #fff);}
.hero h1{font:800 clamp(34px,5.6vw,58px)/1.04 var(--display);letter-spacing:-2.2px;margin:0 0 18px;text-wrap:balance;}
.hero p{font-size:17px;line-height:1.58;color:var(--muted);margin:0 auto 30px;max-width:520px;}
.hero-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;}
.hero-photo{max-width:900px;margin:44px auto 0;padding:0 26px;}
.hero-photo div{position:relative;aspect-ratio:16/9;border-radius:26px;overflow:hidden;border:3px solid #fff;box-shadow:0 18px 40px color-mix(in srgb, var(--ink) 16%, transparent);}
.hero-photo img{width:100%;height:100%;object-fit:cover;display:block;}

/* ─── Döndürülmüş üniversite şeridi ─── */
.uni-strip{background:var(--accent);transform:rotate(-1deg) scale(1.02);margin:10px -10px 0;}
.uni-strip-in{max-width:var(--maxw);margin:0 auto;padding:16px 26px;display:flex;align-items:center;gap:34px;flex-wrap:wrap;justify-content:center;}
.uni-strip .u{font:700 15px/1 var(--display);color:#fff;letter-spacing:.02em;}
.uni-strip .lbl-w{font:700 11px/1 var(--mono);color:color-mix(in srgb, #fff 78%, var(--accent));text-transform:uppercase;letter-spacing:.1em;}

/* ─── Izgara + kartlar ─── */
.grid{display:flex;flex-wrap:wrap;justify-content:center;gap:var(--gap,16px);--cols:3;--min:250px;}
.grid>*{flex:0 1 calc((100% - (var(--cols) - 1) * var(--gap,16px)) / var(--cols));min-width:min(var(--min),100%);}
.card{background:#fff;border:2px solid var(--line);border-radius:24px;padding:30px 28px;display:flex;flex-direction:column;transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease;}
.card:hover{transform:translateY(-4px) rotate(-.4deg);box-shadow:6px 8px 0 color-mix(in srgb, var(--accent) 18%, #fff);border-color:var(--accent);}
.card-sm{border-radius:20px;padding:24px 22px;}
.card-sm:hover{transform:translateY(-3px) rotate(.5deg);border-color:var(--accent-2);box-shadow:6px 8px 0 color-mix(in srgb, var(--accent-2) 16%, #fff);}
.card-ic{width:54px;height:54px;border-radius:16px;background:var(--grad);color:#fff;display:flex;align-items:center;justify-content:center;margin-bottom:16px;font-size:27px;}
.card-ic-2{width:44px;height:44px;border-radius:13px;background:color-mix(in srgb, var(--accent-2) 16%, #fff);color:color-mix(in srgb, var(--accent-2) 82%, #000);font-size:22px;margin-bottom:13px;}
.card h3{font:700 20px/1.2 var(--display);margin:0 0 8px;}
.card-sm h3{font:700 15.5px/1.25 var(--display);margin:0 0 7px;}
.card p{font-size:14px;line-height:1.58;color:var(--muted);margin:0 0 16px;}
.card-sm p{font-size:13px;line-height:1.5;margin:0;}
.ticks{list-style:none;padding:16px 0 0;margin:auto 0 0;border-top:2px dashed var(--line);display:flex;flex-direction:column;gap:9px;}
.ticks li{display:flex;align-items:flex-start;gap:9px;font-size:13.5px;line-height:1.4;color:var(--ink-2);}
.ticks svg{color:var(--accent);flex-shrink:0;margin-top:1px;font-size:16px;}

/* ─── Süreç (kesikli bağlantı çizgisi + gradient daireler) ─── */
.timeline{position:relative;display:flex;flex-wrap:wrap;justify-content:center;gap:20px;--cols:4;--min:200px;}
.timeline>.tl-line{position:absolute;top:27px;left:12%;right:12%;height:3px;background:repeating-linear-gradient(90deg, var(--accent) 0 10px, transparent 10px 20px);opacity:.35;flex:none;}
.timeline>.step{flex:0 1 calc((100% - (var(--cols) - 1) * 20px) / var(--cols));min-width:min(var(--min),100%);position:relative;text-align:center;padding:0 8px;}
.step .n{width:54px;height:54px;margin:0 auto 16px;border-radius:50%;background:var(--grad);color:#fff;display:flex;align-items:center;justify-content:center;font:800 18px/1 var(--display);border:4px solid #fff;box-shadow:0 8px 20px color-mix(in srgb, var(--accent) 30%, transparent);}
.step h3{font:700 16px/1.25 var(--display);margin:0 0 8px;}
.step p{font-size:13.5px;line-height:1.55;color:var(--muted);margin:0;}

/* ─── İstatistik sticker'ları ─── */
.stat-stickers{display:flex;flex-wrap:wrap;gap:16px;justify-content:center;}
.stat-sticker{background:#fff;border:2px solid var(--line);border-radius:22px;padding:24px 28px;text-align:center;min-width:180px;box-shadow:5px 6px 0 color-mix(in srgb, var(--accent) 14%, #fff);}
.stat-sticker:nth-child(odd){transform:rotate(-1.5deg);}
.stat-sticker:nth-child(even){transform:rotate(1.5deg);box-shadow:5px 6px 0 color-mix(in srgb, var(--accent-2) 16%, #fff);}
.stat-sticker .v{font:800 34px/1 var(--display);letter-spacing:-1.2px;color:var(--accent-deep);}
.stat-sticker .l{font:700 11px/1.3 var(--mono);color:var(--faint);text-transform:uppercase;letter-spacing:.08em;margin-top:8px;}

/* ─── Yorum / ekip ─── */
.quote{background:#fff;border:2px solid var(--line);border-radius:22px;padding:26px;transition:transform .2s ease,border-color .2s ease;}
.quote:hover{transform:translateY(-3px) rotate(-.5deg);border-color:var(--accent);}
.quote p{font-size:14.5px;line-height:1.6;color:var(--ink-2);margin:0 0 20px;}
.who{display:flex;align-items:center;gap:12px;}
.avatar{width:44px;height:44px;border-radius:50%;background:var(--grad);color:#fff;display:flex;align-items:center;justify-content:center;font:800 16px/1 var(--display);flex-shrink:0;overflow:hidden;}
.avatar img{width:100%;height:100%;object-fit:cover;}
.who b{display:block;font:700 14px/1.1 var(--display);}
.who span{font:500 12px/1.2 var(--mono);color:var(--faint);}
.member{background:#fff;border:2px solid var(--line);border-radius:20px;padding:22px;display:flex;align-items:center;gap:14px;}
.member .avatar{width:50px;height:50px;font-size:18px;}
.trust{background:var(--ink);border-radius:20px;padding:22px;display:flex;align-items:center;gap:14px;}
.trust-ic{width:50px;height:50px;border-radius:14px;background:color-mix(in srgb, var(--accent) 26%, transparent);color:color-mix(in srgb, var(--accent) 62%, #fff);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:25px;}
.trust b{font:700 15px/1.2 var(--display);color:var(--on-ink);}
.trust p{font-size:12.5px;line-height:1.5;color:var(--on-ink-soft);margin:4px 0 0;}

/* ─── Paketler ─── */
.pkg{background:#fff;border:2px solid var(--line);border-radius:24px;padding:28px 26px;display:flex;flex-direction:column;transition:transform .2s ease,box-shadow .2s ease;}
.pkg:nth-child(odd){transform:rotate(-1deg);}
.pkg:nth-child(even){transform:rotate(1deg);}
.pkg:hover{transform:rotate(0deg) translateY(-4px);box-shadow:6px 8px 0 color-mix(in srgb, var(--accent) 18%, #fff);}
.pkg-tag{font:700 11px/1 var(--body);text-transform:uppercase;letter-spacing:.04em;color:var(--accent-deep);background:var(--accent-pale);padding:7px 12px;border-radius:20px;align-self:flex-start;}
.pkg h3{font:800 22px/1.2 var(--display);margin:16px 0 8px;}
.pkg p{font-size:13.5px;line-height:1.55;color:var(--muted);margin:0 0 18px;}
.pkg .ticks{padding:18px 0 0;margin:0 0 20px;}
.pkg-btn{margin-top:auto;text-decoration:none;text-align:center;background:var(--accent-pale);color:var(--accent-deep);font:700 14px/1 var(--body);padding:15px;border-radius:30px;transition:filter .15s ease;}
.pkg-btn:hover{filter:brightness(1.06);}
.pkg-hi{background:var(--ink);border-color:var(--ink);}
.pkg-hi h3{color:var(--on-ink);}
.pkg-hi p{color:var(--on-ink-soft);}
.pkg-hi .pkg-tag{background:color-mix(in srgb, var(--accent) 34%, transparent);color:color-mix(in srgb, var(--accent) 60%, #fff);}
.pkg-hi .ticks{border-color:rgba(255,255,255,.18);}
.pkg-hi .ticks li{color:#f6e7e8;}
.pkg-hi .ticks svg{color:color-mix(in srgb, var(--accent) 60%, #fff);}
.pkg-hi .pkg-btn{background:var(--grad);color:#fff;}

/* ─── S.S.S. (JS'siz) ─── */
.faq{max-width:900px;margin:0 auto;}
.faq details{background:var(--tint);border:2px solid var(--line);border-radius:18px;overflow:hidden;margin-bottom:12px;}
.faq summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:20px 22px;font:600 16px/1.35 var(--body);transition:background .15s ease;}
.faq summary::-webkit-details-marker{display:none;}
.faq summary:hover{background:var(--accent-pale);}
.faq summary .ico{width:28px;height:28px;flex-shrink:0;border-radius:50%;background:var(--grad);color:#fff;display:flex;align-items:center;justify-content:center;font:700 18px/1 var(--body);transition:transform .15s ease;}
.faq details[open] summary .ico{transform:rotate(45deg);}
.faq p{font-size:14.5px;line-height:1.62;color:var(--muted);margin:0;padding:0 22px 20px;}

/* ─── CTA ─── */
.cta{background:var(--ink);border-radius:28px;padding:52px 48px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:44px;align-items:center;position:relative;overflow:hidden;}
.cta-blob{position:absolute;width:320px;height:320px;border-radius:50%;background:var(--grad);filter:blur(70px);opacity:.5;top:-110px;right:-60px;pointer-events:none;}
.cta-l{position:relative;}
.cta h2{font:800 clamp(26px,3.5vw,36px)/1.1 var(--display);letter-spacing:-1.4px;margin:0 0 14px;color:var(--on-ink);}
.cta-l>p{font-size:16px;line-height:1.6;color:var(--on-ink-soft);margin:0 0 22px;}
.cta-ticks{display:flex;flex-direction:column;gap:12px;}
.cta-ticks span{display:inline-flex;align-items:center;gap:11px;font:600 14px/1.3 var(--body);color:#f6e7e8;}
.cta-ticks svg{color:color-mix(in srgb, var(--accent) 60%, #fff);flex-shrink:0;font-size:18px;}
.cta-r{position:relative;background:#fff;border-radius:22px;padding:28px;}
.cta-r h3{font:800 19px/1.25 var(--display);margin:0 0 8px;}
.cta-r>p{font-size:13.5px;line-height:1.55;color:var(--muted);margin:0 0 18px;}
.cta-r .btn{width:100%;justify-content:center;font-size:15px;}
.contact{list-style:none;padding:18px 0 0;margin:18px 0 0;border-top:2px dashed var(--line);display:flex;flex-direction:column;gap:11px;}
.contact li{display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--muted);}
.contact svg{color:var(--accent);flex-shrink:0;font-size:17px;}
.contact a{color:var(--ink);text-decoration:none;font-weight:600;}
.contact a:hover{color:var(--accent);}

/* ─── Footer ─── */
.foot{border-top:2px dashed var(--line);}
.foot-in{max-width:var(--maxw);margin:0 auto;padding:32px 26px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;}
.foot-txt{font:500 13px/1.5 var(--mono);color:var(--faint);}
.foot-dim{font:500 12px/1.5 var(--mono);color:color-mix(in srgb, var(--faint) 76%, #fff);}
.foot-soc a{color:var(--faint);display:inline-flex;font-size:19px;transition:color .15s ease,transform .15s ease;}
.foot-soc a:hover{color:var(--accent);transform:translateY(-2px);}

/* ─── WhatsApp ─── */
.wa-float{position:fixed;right:24px;bottom:24px;z-index:60;text-decoration:none;display:inline-flex;align-items:center;gap:10px;background:var(--grad);color:#fff;font:700 14px/1 var(--body);padding:14px 18px;border-radius:30px;box-shadow:0 14px 30px color-mix(in srgb, var(--accent) 40%, transparent);transition:transform .18s ease;}
.wa-float:hover{transform:translateY(-3px) rotate(-1deg);}
.wa-float svg{font-size:20px;}
</style>
</head>
<body>

{{-- ═══ NAV ═══ --}}
<div class="nav">
    <div class="nav-in">
        <a href="#" class="brand">
            @if($brandLogoUrl)
                <img src="{{ $brandLogoUrl }}" alt="{{ $siteName }}">
            @else
                <span class="brand-name">{{ $siteName }}</span>
            @endif
        </a>
        <div class="nav-links">
            @foreach($navLinks as $nl)
                <a href="{{ $nl['href'] }}" class="nav-link">{{ $nl['label'] }}</a>
            @endforeach
            <a href="{{ $applyUrl }}" class="nav-cta" data-track="cta_clicked" data-ph-cta-name="nav_apply" data-ph-location="partner_mercan_nav">Başvur</a>
        </div>
    </div>
</div>

{{-- ═══ HERO ═══ --}}
<div class="hero">
    {{-- Sticker rozetler: SADECE partnerin girdiği istatistikler (uydurma rakam yok) --}}
    @if(!empty($heroTrust))
        <div class="stickers">
            @foreach($heroTrust as $i => $ht)
                <span class="sticker">{!! $icon($i === 0 ? 'cap' : 'check') !!}{{ $ht['value'] }} {{ $ht['label'] }}</span>
            @endforeach
        </div>
    @endif
    <h1>{{ $heroTitle }}</h1>
    <p>{{ $heroSubtitle }}</p>
    <div class="hero-btns">
        <a href="{{ $applyUrl }}" class="btn btn-grad" data-track="cta_clicked" data-ph-cta-name="hero_apply" data-ph-location="partner_mercan_hero">Ücretsiz danışmanlık {!! $icon('arrow') !!}</a>
        @if(!empty($navLinks))<a href="{{ $navLinks[0]['href'] }}" class="btn btn-line">{{ $navLinks[0]['label'] }}</a>@endif
    </div>
</div>

@if($heroImg)
    <div class="hero-photo"><div><img src="{{ $heroImg }}" alt="{{ $siteName }}"></div></div>
@endif

{{-- ═══ SIRALANABİLİR BÖLÜMLER ═══ --}}
@foreach($sections as $sectionKey)
    @includeIf('public.partner-templates.mercan.sections.' . $sectionKey)
@endforeach

{{-- ═══ BAŞVURU / İLETİŞİM ═══ --}}
<div id="basvuru" class="wrap" style="padding:8px 26px 72px;">
    <div class="cta">
        <div class="cta-blob"></div>
        <div class="cta-l">
            <h2>Yolculuğun bugün başlıyor</h2>
            <p>Başvur, ekibimiz en kısa sürede seninle iletişime geçsin.</p>
            <div class="cta-ticks">
                <span>{!! $icon('check') !!}Uçtan uca, tek elden yönetim</span>
                <span>{!! $icon('check') !!}Her adım panelden şeffaf takip</span>
            </div>
        </div>
        <div class="cta-r">
            <h3>Ücretsiz ön görüşme</h3>
            <p>Formu doldur; hedeflerini dinleyip sana özel bir yol haritası çıkaralım.</p>
            <a href="{{ $applyUrl }}" class="btn btn-grad" data-track="cta_clicked" data-ph-cta-name="footer_apply" data-ph-location="partner_mercan_cta">Başvuru formunu aç {!! $icon('arrow') !!}</a>
            @if($waUrl || $phone || $address || $instagram)
                <ul class="contact">
                    @if($waUrl)<li>{!! $icon('wa') !!}<a href="{{ $waUrl }}" target="_blank" rel="noopener">WhatsApp'tan yaz</a></li>@endif
                    @if($phone)<li>{!! $icon('phone') !!}<a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a></li>@endif
                    @if($address)<li>{!! $icon('pin') !!}{{ $address }}</li>@endif
                    @if($instagram)<li>{!! $icon('instagram') !!}<a href="https://instagram.com/{{ ltrim($instagram, '@') }}" target="_blank" rel="noopener">{{ '@' . ltrim($instagram, '@') }}</a></li>@endif
                </ul>
            @endif
        </div>
    </div>
</div>

{{-- ═══ FOOTER ═══ --}}
<div class="foot">
    <div class="foot-in">
        <div class="brand">
            @if($brandLogoUrl)
                <img src="{{ $brandLogoUrl }}" alt="{{ $siteName }}">
            @else
                <span class="brand-name" style="font-size:18px;">{{ $siteName }}</span>
            @endif
        </div>
        @if($address)<span class="foot-txt">{{ $address }}</span>@endif
        <span class="foot-dim">
            © {{ now()->year }} {{ $siteName }}
            @if($showBadge)
                · Powered by <a href="https://panel.mentorde.com" target="_blank" rel="noopener">{{ config('brand.name', 'MentorDE') }}</a>
            @endif
        </span>
        @if($instagram)
            <div class="foot-soc">
                <a href="https://instagram.com/{{ ltrim($instagram, '@') }}" target="_blank" rel="noopener" aria-label="Instagram">{!! $icon('instagram') !!}</a>
            </div>
        @endif
    </div>
</div>

@if($waUrl)
    <a href="{{ $waUrl }}" class="wa-float" target="_blank" rel="noopener" data-track="cta_clicked" data-ph-cta-name="whatsapp_float" data-ph-location="partner_mercan_float">
        {!! $icon('wa') !!} WhatsApp
    </a>
@endif

</body>
</html>
