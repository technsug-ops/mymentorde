{{--
    PARTNER TEMPLATE · MANYETA
    Neon karanlık mod: koyu zemin, parlayan blob'lar, gradient başlık vurgusu, glow kenarlar.
    Hizmetler numaralı SATIR listesi (kart değil) — bu şablonun imzası.
    Kaynak tasarım: "Manyeta" (DC).

    Kurallar: JS YOK (S.S.S. <details>), font SADECE lokal, uydurma veri YOK.
    Bölümler modüler: manyeta/sections/*.blade.php — sıra + aç/kapa $sections'tan.
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
    /* İkinci neon renk accent'ten türetilir (mora kaydırma) */
    --accent-2:color-mix(in srgb, var(--accent) 58%, #7b4bff);
    --bg:color-mix(in srgb, var(--accent) 10%, #150c1c);
    --bg-2:color-mix(in srgb, var(--accent) 13%, #1e1327);
    --surface:color-mix(in srgb, var(--accent) 9%, #221830);
    --hair:rgba(255,255,255,.09);
    --hair-2:rgba(255,255,255,.16);
    --text:#f7eef8;
    --muted:color-mix(in srgb, var(--accent) 26%, #a892b4);
    --faint:color-mix(in srgb, var(--accent) 20%, #7d6a89);
    --grad:linear-gradient(120deg, var(--accent), var(--accent-2));
    --display:"Sora","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --body:"IBM Plex Sans","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --mono:"IBM Plex Mono",ui-monospace,SFMono-Regular,monospace;
    --maxw:1240px;
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;scroll-behavior:smooth;overflow-x:hidden;}
body{background:var(--bg);color:var(--text);font-family:var(--body);font-size:15px;line-height:1.6;-webkit-font-smoothing:antialiased;}
svg{width:1em;height:1em;}
img{max-width:100%;}
a{color:var(--accent);}
.wrap{max-width:var(--maxw);margin:0 auto;padding:0 26px;}
.sec{padding:62px 0 56px;}
.lbl{font:600 12px/1 var(--mono);letter-spacing:.1em;color:var(--accent);text-transform:uppercase;}
h1,h2,h3{margin:0;font-family:var(--display);}
.h2{font:800 clamp(25px,3.4vw,36px)/1.12 var(--display);letter-spacing:-1.4px;margin:12px 0 0;color:#fff;text-wrap:balance;}
.sec-head{max-width:600px;margin-bottom:38px;}
.sec-head.center{text-align:center;margin-left:auto;margin-right:auto;}
.sec-head p{font-size:15px;line-height:1.6;color:var(--muted);margin:10px 0 0;}
.glow-blob{position:absolute;border-radius:50%;filter:blur(110px);pointer-events:none;}

/* ─── Butonlar ─── */
.btn{display:inline-flex;align-items:center;gap:9px;text-decoration:none;font:700 16px/1 var(--body);padding:17px 32px;border-radius:12px;transition:transform .18s ease,box-shadow .18s ease,border-color .15s ease;}
.btn-grad{background:var(--grad);color:#fff;box-shadow:0 14px 34px color-mix(in srgb, var(--accent) 38%, transparent);}
.btn-grad:hover{transform:translateY(-2px);box-shadow:0 18px 44px color-mix(in srgb, var(--accent) 50%, transparent);}
.btn-ghost{border:1px solid var(--hair-2);color:var(--text);font-weight:600;padding:16px 28px;}
.btn-ghost:hover{border-color:var(--accent);}

/* ─── Nav ─── */
.nav{position:sticky;top:0;z-index:50;background:color-mix(in srgb, var(--bg) 88%, transparent);backdrop-filter:blur(14px);border-bottom:1px solid var(--hair);}
.nav-in{max-width:var(--maxw);margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:16px 26px;}
.brand{display:flex;align-items:center;gap:10px;text-decoration:none;}
.brand-name{font:800 20px/1 var(--display);background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent;}
.brand img{max-height:38px;width:auto;display:block;}
.nav-links{display:flex;align-items:center;gap:24px;flex-wrap:wrap;}
.nav-links a{text-decoration:none;font:500 14px/1 var(--body);color:var(--muted);}
.nav-links a:hover{color:#fff;}
.nav-cta{background:var(--grad);color:#fff !important;font-weight:700;font-size:13px;padding:12px 20px;border-radius:10px;}
.nav-cta:hover{filter:brightness(1.08);}
@media(max-width:760px){.nav-links .nav-link{display:none;}}

/* ─── Hero ─── */
.hero{position:relative;overflow:hidden;}
.hero .b1{width:420px;height:420px;background:var(--accent);opacity:.26;top:-150px;left:50%;transform:translateX(-50%);}
.hero .b2{width:280px;height:280px;background:var(--accent-2);opacity:.2;bottom:-120px;right:-60px;filter:blur(100px);}
.hero-in{position:relative;max-width:860px;margin:0 auto;text-align:center;padding:66px 26px 58px;}
.chip{display:inline-flex;align-items:center;gap:8px;font:600 12px/1 var(--mono);color:var(--accent);background:color-mix(in srgb, var(--accent) 12%, transparent);border:1px solid color-mix(in srgb, var(--accent) 35%, transparent);padding:8px 15px;border-radius:30px;}
.chip i{width:7px;height:7px;border-radius:50%;background:var(--accent);display:inline-block;box-shadow:0 0 10px var(--accent);}
.hero h1{font:800 clamp(35px,5.8vw,60px)/1.02 var(--display);letter-spacing:-2.5px;margin:24px 0 18px;color:#fff;text-wrap:balance;}
.hero p{font-size:17px;line-height:1.58;color:var(--muted);margin:0 auto 30px;max-width:520px;}
.hero-btns{display:flex;gap:13px;justify-content:center;flex-wrap:wrap;}
.hero-stats{display:flex;gap:40px;justify-content:center;margin-top:46px;flex-wrap:wrap;}
.hero-stats .v{font:800 32px/1 var(--display);letter-spacing:-1.2px;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent;}
.hero-stats .l{font:500 12px/1.3 var(--mono);color:var(--faint);text-transform:uppercase;letter-spacing:.06em;margin-top:8px;}
.hero-photo{max-width:1000px;margin:0 auto;padding:8px 26px 0;}
.hero-photo div{position:relative;aspect-ratio:16/9;border-radius:18px;overflow:hidden;border:1px solid color-mix(in srgb, var(--accent) 40%, transparent);box-shadow:0 0 60px color-mix(in srgb, var(--accent) 22%, transparent);}
.hero-photo img{width:100%;height:100%;object-fit:cover;display:block;}

/* ─── Üniversite şeridi ─── */
.unis{border-top:1px solid var(--hair);border-bottom:1px solid var(--hair);}
.unis-in{max-width:var(--maxw);margin:0 auto;padding:18px 26px;display:flex;align-items:center;gap:30px;flex-wrap:wrap;justify-content:center;}
.unis .u{font:600 14px/1 var(--display);color:var(--faint);}
.unis .lbl{color:var(--faint);}

/* ─── Hizmetler: numaralı satır listesi (bu şablonun imzası) ─── */
.rows{max-width:1060px;margin:0 auto;}
.row-item{display:grid;grid-template-columns:70px minmax(200px,1fr) auto;gap:24px;align-items:center;padding:30px 6px;border-bottom:1px solid var(--hair);transition:background .15s ease;}
.row-item:hover{background:rgba(255,255,255,.03);}
.row-no{font:800 52px/1 var(--display);letter-spacing:-2px;background:linear-gradient(160deg, var(--accent), var(--accent-2));-webkit-background-clip:text;background-clip:text;color:transparent;opacity:.85;}
.row-item h3{font:700 19px/1.2 var(--display);margin:0 0 7px;color:#fff;}
.row-item p{font-size:14px;line-height:1.55;color:var(--muted);margin:0;max-width:560px;}
.row-tags{display:flex;flex-direction:column;gap:7px;}
.row-tags span{display:flex;align-items:center;gap:8px;font:500 12.5px/1.3 var(--mono);color:color-mix(in srgb, var(--muted) 76%, #fff);}
.row-tags i{width:5px;height:5px;border-radius:50%;background:var(--accent);flex-shrink:0;box-shadow:0 0 8px var(--accent);}
@media(max-width:720px){.row-item{grid-template-columns:56px 1fr;}.row-tags{grid-column:1 / -1;}}

/* ─── Izgara + kartlar ─── */
.grid{display:flex;flex-wrap:wrap;justify-content:center;gap:var(--gap,16px);--cols:3;--min:250px;}
.grid>*{flex:0 1 calc((100% - (var(--cols) - 1) * var(--gap,16px)) / var(--cols));min-width:min(var(--min),100%);}
.card{background:var(--surface);border:1px solid var(--hair);border-radius:18px;padding:26px 24px;display:flex;flex-direction:column;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease;}
.card:hover{transform:translateY(-3px);border-color:color-mix(in srgb, var(--accent) 45%, transparent);box-shadow:0 0 40px color-mix(in srgb, var(--accent) 16%, transparent);}
.card-ic{width:46px;height:46px;border-radius:13px;background:color-mix(in srgb, var(--accent) 16%, transparent);color:var(--accent);display:flex;align-items:center;justify-content:center;margin-bottom:14px;font-size:23px;}
.card h3{font:700 16px/1.25 var(--display);margin:0 0 7px;color:#fff;}
.card p{font-size:13.5px;line-height:1.55;color:var(--muted);margin:0;}

/* ─── Süreç ─── */
.step{text-align:center;padding:0 8px;}
.step .n{width:52px;height:52px;margin:0 auto 16px;border-radius:14px;background:var(--grad);color:#fff;display:flex;align-items:center;justify-content:center;font:800 17px/1 var(--display);box-shadow:0 0 30px color-mix(in srgb, var(--accent) 34%, transparent);}
.step h3{font:700 16px/1.25 var(--display);margin:0 0 8px;color:#fff;}
.step p{font-size:13.5px;line-height:1.55;color:var(--muted);margin:0;}

/* ─── İstatistik ─── */
.stat-row{display:flex;flex-wrap:wrap;gap:20px;justify-content:center;}
.stat-row>div{flex:1 1 190px;background:var(--surface);border:1px solid var(--hair);border-radius:18px;padding:26px;text-align:center;}
.stat-row .v{font:800 clamp(28px,3.6vw,38px)/1 var(--display);letter-spacing:-1.2px;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent;}
.stat-row .l{font:500 12px/1.3 var(--mono);color:var(--faint);text-transform:uppercase;letter-spacing:.06em;margin-top:9px;}

/* ─── Yorum spotlight + ekip ─── */
.spot{position:relative;max-width:860px;margin:0 auto;text-align:center;}
.spot .b{width:260px;height:260px;background:var(--accent-2);opacity:.18;top:-60px;left:50%;transform:translateX(-50%);filter:blur(100px);}
.spot blockquote{position:relative;font:600 clamp(19px,2.5vw,26px)/1.5 var(--display);letter-spacing:-.5px;color:#fff;margin:26px 0 24px;text-wrap:balance;}
.spot .who{position:relative;display:flex;align-items:center;gap:12px;justify-content:center;}
.avatar{width:44px;height:44px;border-radius:50%;background:var(--grad);color:#fff;display:flex;align-items:center;justify-content:center;font:800 16px/1 var(--display);flex-shrink:0;overflow:hidden;}
.avatar img{width:100%;height:100%;object-fit:cover;}
.who b{display:block;font:700 14px/1.1 var(--display);color:#fff;}
.who span{font:500 12px/1.2 var(--mono);color:var(--faint);}
.quote{background:var(--surface);border:1px solid var(--hair);border-radius:18px;padding:24px;}
.quote p{font-size:14px;line-height:1.6;color:var(--muted);margin:0 0 18px;}
.member{background:var(--surface);border:1px solid var(--hair);border-radius:18px;padding:22px;display:flex;align-items:center;gap:14px;}
.member .avatar{width:50px;height:50px;border-radius:14px;font-size:18px;}
.trust{background:color-mix(in srgb, var(--accent) 14%, var(--surface));border:1px solid color-mix(in srgb, var(--accent) 38%, transparent);border-radius:18px;padding:22px;display:flex;align-items:center;gap:14px;}
.trust-ic{width:50px;height:50px;border-radius:14px;background:var(--grad);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:25px;}
.trust b{font:700 15px/1.2 var(--display);color:#fff;}
.trust p{font-size:12.5px;line-height:1.5;color:var(--muted);margin:4px 0 0;}

/* ─── Paketler ─── */
.pkg{position:relative;overflow:hidden;background:var(--surface);border:1px solid var(--hair);border-radius:18px;padding:28px 26px;display:flex;flex-direction:column;transition:transform .2s ease,box-shadow .2s ease;}
.pkg:hover{transform:translateY(-4px);box-shadow:0 0 46px color-mix(in srgb, var(--accent) 18%, transparent);}
.pkg-tag{font:600 10.5px/1 var(--mono);letter-spacing:.1em;text-transform:uppercase;color:var(--accent);align-self:flex-start;}
.pkg h3{font:800 22px/1.2 var(--display);margin:12px 0 8px;color:#fff;}
.pkg p{font-size:13.5px;line-height:1.55;color:var(--muted);margin:0 0 18px;}
.ticks{list-style:none;padding:18px 0 0;margin:0 0 20px;border-top:1px solid var(--hair);display:flex;flex-direction:column;gap:10px;}
.ticks li{display:flex;align-items:flex-start;gap:9px;font-size:13px;line-height:1.45;color:color-mix(in srgb, var(--muted) 70%, #fff);}
.ticks svg{color:var(--accent);flex-shrink:0;margin-top:1px;font-size:16px;}
.pkg-btn{margin-top:auto;text-decoration:none;text-align:center;background:color-mix(in srgb, var(--accent) 16%, transparent);color:var(--accent);font:700 14px/1 var(--body);padding:15px;border-radius:10px;transition:filter .15s ease;}
.pkg-btn:hover{filter:brightness(1.2);}
.pkg-hi{border-color:color-mix(in srgb, var(--accent) 55%, transparent);box-shadow:0 0 50px color-mix(in srgb, var(--accent) 22%, transparent);}
.pkg-hi .pkg-btn{background:var(--grad);color:#fff;}

/* ─── S.S.S. (JS'siz) ─── */
.faq{max-width:900px;margin:0 auto;}
.faq details{background:var(--surface);border:1px solid var(--hair);border-radius:14px;overflow:hidden;margin-bottom:12px;}
.faq summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:20px 22px;font:600 16px/1.35 var(--body);color:#fff;transition:background .15s ease;}
.faq summary::-webkit-details-marker{display:none;}
.faq summary:hover{background:rgba(255,255,255,.04);}
.faq summary .ico{width:27px;height:27px;flex-shrink:0;border-radius:8px;background:color-mix(in srgb, var(--accent) 18%, transparent);color:var(--accent);display:flex;align-items:center;justify-content:center;font:700 18px/1 var(--body);transition:transform .15s ease;}
.faq details[open] summary .ico{transform:rotate(45deg);}
.faq p{font-size:14.5px;line-height:1.62;color:var(--muted);margin:0;padding:0 22px 20px;}

/* ─── CTA ─── */
.cta{position:relative;overflow:hidden;background:var(--bg-2);border:1px solid var(--hair);border-radius:22px;padding:52px 46px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:44px;align-items:center;}
.cta .b{width:320px;height:320px;background:var(--grad);opacity:.28;bottom:-120px;left:-60px;filter:blur(90px);}
.cta-l{position:relative;}
.cta h2{font:800 clamp(26px,3.5vw,36px)/1.1 var(--display);letter-spacing:-1.4px;margin:0 0 14px;color:#fff;}
.cta-l>p{font-size:16px;line-height:1.6;color:var(--muted);margin:0 0 22px;}
.cta-ticks{display:flex;flex-direction:column;gap:12px;}
.cta-ticks span{display:inline-flex;align-items:center;gap:11px;font:600 14px/1.3 var(--body);color:var(--text);}
.cta-ticks svg{color:var(--accent);flex-shrink:0;font-size:18px;}
.cta-r{position:relative;background:var(--surface);border:1px solid var(--hair-2);border-radius:16px;padding:28px;}
.cta-r h3{font:700 19px/1.25 var(--display);color:#fff;margin:0 0 8px;}
.cta-r>p{font-size:13.5px;line-height:1.55;color:var(--muted);margin:0 0 18px;}
.cta-r .btn{width:100%;justify-content:center;font-size:15px;}
.contact{list-style:none;padding:18px 0 0;margin:18px 0 0;border-top:1px solid var(--hair);display:flex;flex-direction:column;gap:11px;}
.contact li{display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--muted);}
.contact svg{color:var(--accent);flex-shrink:0;font-size:17px;}
.contact a{color:#fff;text-decoration:none;font-weight:600;}
.contact a:hover{color:var(--accent);}

/* ─── Footer ─── */
.foot{border-top:1px solid var(--hair);}
.foot-in{max-width:var(--maxw);margin:0 auto;padding:32px 26px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;}
.foot-txt{font:500 13px/1.5 var(--mono);color:var(--faint);}
.foot-dim{font:500 12px/1.5 var(--mono);color:color-mix(in srgb, var(--faint) 72%, var(--bg));}
.foot-soc a{color:var(--faint);display:inline-flex;font-size:19px;transition:color .15s ease,transform .15s ease;}
.foot-soc a:hover{color:var(--accent);transform:translateY(-2px);}

/* ─── WhatsApp ─── */
.wa-float{position:fixed;right:24px;bottom:24px;z-index:60;text-decoration:none;display:inline-flex;align-items:center;gap:10px;background:var(--grad);color:#fff;font:700 14px/1 var(--body);padding:14px 18px;border-radius:12px;box-shadow:0 14px 34px color-mix(in srgb, var(--accent) 40%, transparent);transition:transform .18s ease;}
.wa-float:hover{transform:translateY(-3px);}
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
            <a href="{{ $applyUrl }}" class="nav-cta" data-track="cta_clicked" data-ph-cta-name="nav_apply" data-ph-location="partner_manyeta_nav">Başvur</a>
        </div>
    </div>
</div>

{{-- ═══ HERO ═══ --}}
<div class="hero">
    <div class="glow-blob b1"></div>
    <div class="glow-blob b2"></div>
    <div class="hero-in">
        <span class="chip"><i></i>İlk görüşme ücretsiz</span>
        <h1>{{ $heroTitle }}</h1>
        <p>{{ $heroSubtitle }}</p>
        <div class="hero-btns">
            <a href="{{ $applyUrl }}" class="btn btn-grad" data-track="cta_clicked" data-ph-cta-name="hero_apply" data-ph-location="partner_manyeta_hero">Ücretsiz danışmanlık {!! $icon('arrow') !!}</a>
            @if(!empty($navLinks))<a href="{{ $navLinks[0]['href'] }}" class="btn btn-ghost">{{ $navLinks[0]['label'] }}</a>@endif
        </div>
        {{-- Sadece partnerin girdiği istatistikler (uydurma rakam yok) --}}
        @if(!empty($heroTrust))
            <div class="hero-stats">
                @foreach($heroTrust as $ht)
                    <div><div class="v">{{ $ht['value'] }}</div><div class="l">{{ $ht['label'] }}</div></div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@if($heroImg)
    <div class="hero-photo"><div><img src="{{ $heroImg }}" alt="{{ $siteName }}"></div></div>
@endif

{{-- ═══ SIRALANABİLİR BÖLÜMLER ═══ --}}
@foreach($sections as $sectionKey)
    @includeIf('public.partner-templates.manyeta.sections.' . $sectionKey)
@endforeach

{{-- ═══ BAŞVURU / İLETİŞİM ═══ --}}
<div id="basvuru" class="wrap" style="padding:8px 26px 72px;">
    <div class="cta">
        <div class="glow-blob b"></div>
        <div class="cta-l">
            <h2>Geleceğin bugün başlıyor</h2>
            <p>Başvur, ekibimiz en kısa sürede seninle iletişime geçsin.</p>
            <div class="cta-ticks">
                <span>{!! $icon('check') !!}Uçtan uca, tek elden yönetim</span>
                <span>{!! $icon('check') !!}Her adım panelden şeffaf takip</span>
            </div>
        </div>
        <div class="cta-r">
            <h3>Ücretsiz ön görüşme</h3>
            <p>Formu doldur; hedeflerini dinleyip sana özel bir yol haritası çıkaralım.</p>
            <a href="{{ $applyUrl }}" class="btn btn-grad" data-track="cta_clicked" data-ph-cta-name="footer_apply" data-ph-location="partner_manyeta_cta">Başvuru formunu aç {!! $icon('arrow') !!}</a>
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
    <a href="{{ $waUrl }}" class="wa-float" target="_blank" rel="noopener" data-track="cta_clicked" data-ph-cta-name="whatsapp_float" data-ph-location="partner_manyeta_float">
        {!! $icon('wa') !!} WhatsApp
    </a>
@endif

</body>
</html>
