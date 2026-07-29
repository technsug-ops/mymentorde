{{--
    PARTNER TEMPLATE · LAVANTA
    Yumuşak/yuvarlak formlar, pastel zemin, samimi dil. Kaynak tasarım: "Lavanta" (DC).

    Kurallar: JS YOK (S.S.S. <details> ile), font SADECE lokal, uydurma veri YOK.
    Tüm renkler var(--accent)'ten türetilir → partner kendi rengini seçince site onunla boyanır.
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
    // Hero'da öne çıkan rozetler: partnerin kendi istatistikleri (yoksa hiç gösterilmez).
    $heroCards = array_slice($heroTrust ?? [], 0, 2);
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
    --accent-deep:color-mix(in srgb, var(--accent) 86%, #000);
    --accent-soft:color-mix(in srgb, var(--accent) 14%, #fff);
    --accent-tint:color-mix(in srgb, var(--accent) 5%, #fff);
    --line:color-mix(in srgb, var(--accent) 9%, #fff);
    --line-2:color-mix(in srgb, var(--accent) 18%, #fff);
    --ink:color-mix(in srgb, var(--accent) 16%, #1a1626);
    --muted:color-mix(in srgb, var(--accent) 12%, #6b6577);
    --ink-soft:color-mix(in srgb, var(--accent) 22%, #cfc9dd);
    --shadow-sm:0 8px 26px color-mix(in srgb, var(--ink) 6%, transparent);
    --shadow-md:0 18px 40px color-mix(in srgb, var(--ink) 11%, transparent);
    --display:"Poppins","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --body:"Public Sans","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --mono:"IBM Plex Mono",ui-monospace,SFMono-Regular,monospace;
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;scroll-behavior:smooth;}
body{background:var(--accent-tint);color:var(--ink);font-family:var(--body);font-size:15px;line-height:1.6;-webkit-font-smoothing:antialiased;}
svg{width:1em;height:1em;}
img{max-width:100%;}
a{color:var(--accent-deep);}
.wrap{max-width:1120px;margin:0 auto;padding:0 26px;}
.lbl{font:600 12px/1 var(--mono);letter-spacing:.1em;color:var(--accent);text-transform:uppercase;}
h1,h2,h3{font-family:var(--display);letter-spacing:-.6px;margin:0;}
.h2{font:700 clamp(25px,3.4vw,36px)/1.14 var(--display);letter-spacing:-1px;margin:14px 0 10px;}
.lead{font-size:16px;color:var(--muted);margin:0;}
.sec{padding:62px 0;}
.sec-head{text-align:center;max-width:620px;margin:0 auto 40px;}
@keyframes lvFloat{0%,100%{transform:translateY(0);}50%{transform:translateY(-9px);}}

/* ─── Butonlar ─── */
.btn{display:inline-flex;align-items:center;gap:9px;text-decoration:none;border-radius:30px;font:700 15px/1 var(--body);padding:16px 28px;transition:transform .18s ease,box-shadow .18s ease,background .15s ease;}
.btn-primary{background:var(--accent);color:#fff;box-shadow:0 14px 30px color-mix(in srgb, var(--accent) 34%, transparent);}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 18px 38px color-mix(in srgb, var(--accent) 44%, transparent);}
.btn-ghost{background:#fff;color:var(--accent-deep);border:1.5px solid var(--line-2);padding:15px 24px;}
.btn-ghost:hover{border-color:var(--accent);color:var(--accent);}

/* ─── Nav ─── */
.nav{position:sticky;top:0;z-index:50;background:color-mix(in srgb, var(--accent-tint) 88%, transparent);backdrop-filter:blur(12px);border-bottom:1px solid var(--line);}
.nav-in{max-width:1120px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:16px 26px;}
.brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--ink);}
.brand-mark{width:34px;height:34px;border-radius:12px;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font:800 16px/1 var(--display);flex-shrink:0;}
.brand-name{font:700 20px/1 var(--display);}
.brand img{max-height:40px;width:auto;display:block;}
.nav-links{display:flex;align-items:center;gap:24px;flex-wrap:wrap;}
.nav-links a{text-decoration:none;font:600 14px/1 var(--body);color:var(--muted);}
.nav-links a:hover{color:var(--accent);}
.nav-cta{background:var(--accent);color:#fff !important;font:700 13px/1 var(--body);padding:12px 20px;border-radius:30px;}
.nav-cta:hover{background:var(--accent-deep);}
@media(max-width:760px){.nav-links .nav-link{display:none;}}

/* ─── Hero ─── */
.hero{max-width:1120px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:44px;align-items:center;padding:66px 26px 60px;}
.pill{display:inline-flex;align-items:center;gap:9px;font:600 12px/1 var(--body);color:var(--accent-deep);background:var(--accent-soft);padding:9px 15px;border-radius:30px;}
.pill i{width:8px;height:8px;border-radius:50%;background:var(--accent);display:inline-block;}
.hero h1{font:700 clamp(32px,5.2vw,52px)/1.08 var(--display);letter-spacing:-1.4px;margin:22px 0 18px;text-wrap:balance;}
.hero p{font-size:17.5px;color:var(--muted);margin:0 0 30px;max-width:470px;}
.hero-btns{display:flex;gap:13px;align-items:center;flex-wrap:wrap;}
.hero-chips{display:flex;gap:10px;flex-wrap:wrap;margin-top:26px;}
.chip{background:#fff;border:1px solid var(--line);border-radius:16px;padding:11px 15px;box-shadow:var(--shadow-sm);}
.chip b{display:block;font:800 18px/1 var(--display);}
.chip span{font:500 11.5px/1.3 var(--mono);color:var(--muted);}
.hero-art{position:relative;}
.hero-art-frame{position:relative;aspect-ratio:4/5;border-radius:32px;overflow:hidden;box-shadow:var(--shadow-md);}
.hero-art-frame img{width:100%;height:100%;object-fit:cover;display:block;}
.float{position:absolute;background:#fff;border-radius:18px;padding:13px 16px;box-shadow:var(--shadow-md);display:flex;align-items:center;gap:11px;animation:lvFloat 5s ease-in-out infinite;}
.float:nth-of-type(2){animation-delay:.8s;}
.float-ic{width:36px;height:36px;border-radius:50%;background:var(--accent-soft);color:var(--accent);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:19px;}
.float b{display:block;font:700 13px/1.1 var(--display);}
.float span{font:500 11px/1 var(--mono);color:var(--muted);}
.float-tr{top:-18px;right:-14px;}
.float-bl{bottom:-16px;left:-16px;}

/* ─── Üniversite şeridi ─── */
.unis{background:#fff;border:1px solid var(--line);border-radius:22px;padding:22px 28px;display:flex;align-items:center;gap:30px;flex-wrap:wrap;box-shadow:var(--shadow-sm);}
.unis-lbl{font:600 11px/1.3 var(--mono);color:var(--muted);text-transform:uppercase;letter-spacing:.06em;max-width:130px;}
.unis span.u{font:700 17px/1 var(--display);color:color-mix(in srgb, var(--accent) 42%, #fff);}

/* ─── Kart ızgaraları ─── */
.grid{display:grid;gap:18px;}
.g-svc{grid-template-columns:repeat(auto-fit,minmax(250px,1fr));}
.g-step{grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;}
.g-why{grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;}
.g-team{grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;}
.g-pkg{grid-template-columns:repeat(auto-fit,minmax(280px,1fr));align-items:start;}
.card{background:#fff;border:1px solid var(--line);border-radius:22px;padding:28px 26px;display:flex;flex-direction:column;box-shadow:var(--shadow-sm);transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease;}
.card:hover{transform:translateY(-4px);box-shadow:var(--shadow-md);border-color:var(--line-2);}
.card-ic{width:56px;height:56px;border-radius:17px;background:var(--accent-soft);color:var(--accent);display:flex;align-items:center;justify-content:center;margin-bottom:16px;font-size:28px;}
.card h3{font:700 18px/1.2 var(--display);margin:0 0 8px;}
.card p{font-size:14px;line-height:1.55;color:var(--muted);margin:0 0 16px;}
.ticks{list-style:none;padding:16px 0 0;margin:auto 0 0;border-top:1px solid var(--line);display:flex;flex-direction:column;gap:9px;}
.ticks li{display:flex;align-items:flex-start;gap:9px;font-size:13px;line-height:1.4;color:color-mix(in srgb, var(--ink) 82%, #fff);}
.ticks svg{color:var(--accent);flex-shrink:0;margin-top:2px;font-size:16px;}

/* ─── Süreç ─── */
.proc{background:var(--accent-soft);}
.step{background:#fff;border-radius:20px;padding:26px 22px;box-shadow:var(--shadow-sm);}
.step-n{width:48px;height:48px;border-radius:14px;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font:800 18px/1 var(--display);margin-bottom:16px;}
.step h3{font:700 16px/1.25 var(--display);margin:0 0 8px;}
.step p{font-size:13.5px;line-height:1.55;color:var(--muted);margin:0;}

/* ─── İstatistik bandı ─── */
.stats{background:var(--accent);border-radius:26px;padding:40px 32px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;box-shadow:0 24px 50px color-mix(in srgb, var(--accent) 28%, transparent);}
.stats div{text-align:center;color:#fff;}
.stats .v{font:800 clamp(30px,3.8vw,42px)/1 var(--display);letter-spacing:-1px;}
.stats .l{font:600 12px/1.3 var(--body);opacity:.92;text-transform:uppercase;letter-spacing:.05em;margin-top:9px;}

/* ─── Yorumlar ─── */
.quote{background:#fff;border:1px solid var(--line);border-radius:22px;padding:28px;box-shadow:var(--shadow-sm);}
.quote p{font-size:14.5px;line-height:1.62;margin:0 0 20px;color:color-mix(in srgb, var(--ink) 88%, #fff);}
.who{display:flex;align-items:center;gap:12px;}
.avatar{width:44px;height:44px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font:700 16px/1 var(--display);flex-shrink:0;overflow:hidden;}
.avatar img{width:100%;height:100%;object-fit:cover;}
.who b{display:block;font:700 14px/1.1 var(--display);}
.who span{font:500 12px/1.2 var(--mono);color:var(--muted);}

/* ─── Neden biz / ekip ─── */
.why-wrap{background:#fff;}
.why-card{background:var(--accent-tint);border:1px solid var(--line);border-radius:20px;padding:24px 22px;transition:transform .2s ease;}
.why-card:hover{transform:translateY(-3px);}
.why-ic{width:46px;height:46px;border-radius:14px;background:#fff;color:var(--accent);display:flex;align-items:center;justify-content:center;margin-bottom:14px;box-shadow:var(--shadow-sm);font-size:23px;}
.why-card h3{font:700 15.5px/1.3 var(--display);margin:0 0 6px;}
.why-card p{font-size:13px;line-height:1.55;color:var(--muted);margin:0;}
.member{background:var(--accent-tint);border:1px solid var(--line);border-radius:20px;padding:24px;display:flex;align-items:center;gap:14px;}
.member .avatar{width:52px;height:52px;font-size:19px;}
.trust{background:var(--accent-soft);border-radius:20px;padding:24px;display:flex;align-items:center;gap:14px;}
.trust-ic{width:52px;height:52px;border-radius:16px;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:26px;}
.trust b{font:700 15px/1.2 var(--display);}
.trust p{font-size:12.5px;line-height:1.5;color:var(--muted);margin:4px 0 0;}

/* ─── Paketler ─── */
.pkg{background:#fff;border:1.5px solid var(--line);border-radius:24px;padding:30px 26px;display:flex;flex-direction:column;box-shadow:var(--shadow-sm);transition:transform .2s ease;}
.pkg:hover{transform:translateY(-4px);}
.pkg-top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;}
.pkg h3{font:700 21px/1.2 var(--display);margin:0;}
.pkg-tag{font:600 10.5px/1 var(--mono);letter-spacing:.05em;text-transform:uppercase;color:var(--accent-deep);background:var(--accent-soft);padding:6px 10px;border-radius:20px;}
.pkg p{font-size:13.5px;line-height:1.58;color:var(--muted);margin:0 0 18px;}
.pkg .ticks{margin:0 0 20px;padding-top:18px;}
.pkg-btn{margin-top:auto;text-decoration:none;text-align:center;background:var(--accent-soft);color:var(--accent-deep);font:700 14px/1 var(--body);padding:15px;border-radius:30px;transition:filter .15s ease;}
.pkg-btn:hover{filter:brightness(.95);}
.pkg-hi{background:var(--ink);border-color:var(--ink);box-shadow:0 22px 50px color-mix(in srgb, var(--ink) 22%, transparent);}
.pkg-hi h3{color:#fff;}
.pkg-hi p{color:var(--ink-soft);}
.pkg-hi .pkg-tag{color:var(--ink);background:color-mix(in srgb, var(--accent) 55%, #fff);}
.pkg-hi .ticks{border-color:rgba(255,255,255,.14);}
.pkg-hi .ticks li{color:#eeebf7;}
.pkg-hi .ticks svg{color:color-mix(in srgb, var(--accent) 55%, #fff);}
.pkg-hi .pkg-btn{background:var(--accent);color:#fff;}

/* ─── S.S.S. (JS'siz akordeon) ─── */
.faq{max-width:900px;margin:0 auto;}
.faq details{background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden;box-shadow:var(--shadow-sm);margin-bottom:12px;}
.faq summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:20px 22px;font:600 16px/1.35 var(--body);transition:background .15s ease;}
.faq summary::-webkit-details-marker{display:none;}
.faq summary:hover{background:var(--accent-tint);}
.faq summary .ico{width:28px;height:28px;flex-shrink:0;border-radius:50%;background:var(--accent-soft);color:var(--accent);display:flex;align-items:center;justify-content:center;font:600 19px/1 var(--body);}
.faq details[open] summary .ico{transform:rotate(45deg);}
.faq p{font-size:14.5px;line-height:1.62;color:var(--muted);margin:0;padding:0 22px 20px;}

/* ─── CTA + iletişim ─── */
.cta{background:var(--ink);border-radius:28px;padding:52px 48px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:44px;align-items:center;position:relative;overflow:hidden;}
.cta-blob{position:absolute;width:300px;height:300px;border-radius:50%;background:var(--accent);filter:blur(60px);opacity:.5;top:-100px;right:-40px;pointer-events:none;}
.cta-l{position:relative;}
.cta h2{font:700 clamp(26px,3.4vw,36px)/1.12 var(--display);letter-spacing:-1px;margin:0 0 14px;color:#fff;}
.cta-l>p{font-size:16px;line-height:1.6;color:var(--ink-soft);margin:0 0 22px;}
.cta-ticks{display:flex;flex-direction:column;gap:12px;}
.cta-ticks span{display:inline-flex;align-items:center;gap:11px;font:600 14px/1.3 var(--body);color:#eeebf7;}
.cta-ticks svg{color:color-mix(in srgb, var(--accent) 55%, #fff);flex-shrink:0;font-size:18px;}
.cta-r{position:relative;background:#fff;border-radius:18px;padding:28px;}
.cta-r h3{font:700 19px/1.25 var(--display);margin:0 0 8px;}
.cta-r>p{font-size:13.5px;line-height:1.55;color:var(--muted);margin:0 0 18px;}
.cta-r .btn{width:100%;justify-content:center;}
.contact{list-style:none;padding:18px 0 0;margin:18px 0 0;border-top:1px solid var(--line);display:flex;flex-direction:column;gap:11px;}
.contact li{display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--muted);}
.contact svg{color:var(--accent);flex-shrink:0;font-size:17px;}
.contact a{color:var(--ink);text-decoration:none;font-weight:600;}
.contact a:hover{color:var(--accent);}

/* ─── Footer ─── */
.foot{background:var(--ink);}
.foot-in{max-width:1120px;margin:0 auto;padding:34px 26px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;}
.foot .brand-name{color:#fff;font-size:16px;}
.foot-txt{font:500 13px/1.5 var(--mono);color:var(--ink-soft);}
.foot-dim{font:500 12px/1.5 var(--mono);color:color-mix(in srgb, var(--ink-soft) 62%, var(--ink));}
.foot-dim a{color:var(--ink-soft);}
.foot-soc{display:flex;align-items:center;gap:14px;}
.foot-soc a{color:var(--ink-soft);display:inline-flex;font-size:19px;transition:color .15s ease,transform .15s ease;}
.foot-soc a:hover{color:#fff;transform:translateY(-2px);}

/* ─── WhatsApp float ─── */
.wa-float{position:fixed;right:24px;bottom:24px;z-index:60;text-decoration:none;display:inline-flex;align-items:center;gap:10px;background:#25a566;color:#fff;font:700 14px/1 var(--body);padding:14px 18px;border-radius:30px;box-shadow:0 14px 30px rgba(37,165,102,.4);transition:transform .18s ease;}
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
                <span class="brand-mark">{{ mb_strtoupper(mb_substr($siteName, 0, 1)) }}</span>
                <span class="brand-name">{{ $siteName }}</span>
            @endif
        </a>
        <div class="nav-links">
            <a href="#hizmetler" class="nav-link">Hizmetler</a>
            <a href="#surec" class="nav-link">Süreç</a>
            @if(!empty($packages))<a href="#paketler" class="nav-link">Paketler</a>@endif
            <a href="#sss" class="nav-link">S.S.S.</a>
            <a href="{{ $applyUrl }}" class="nav-cta" data-track="cta_clicked" data-ph-cta-name="nav_apply" data-ph-location="partner_lavanta_nav">Ücretsiz Başvur</a>
        </div>
    </div>
</div>

{{-- ═══ HERO ═══ --}}
<div class="hero">
    <div>
        <span class="pill"><i></i>Ücretsiz ön değerlendirme</span>
        <h1>{{ $heroTitle }}</h1>
        <p>{{ $heroSubtitle }}</p>
        <div class="hero-btns">
            <a href="{{ $applyUrl }}" class="btn btn-primary" data-track="cta_clicked" data-ph-cta-name="hero_apply" data-ph-location="partner_lavanta_hero">Ücretsiz danışmanlık al {!! $icon('arrow') !!}</a>
            <a href="#hizmetler" class="btn btn-ghost">Hizmetler</a>
        </div>
        {{-- Hero görseli yoksa partnerin istatistikleri burada çip olarak görünür (uydurma rakam yok) --}}
        @if(!$heroImg && !empty($heroCards))
            <div class="hero-chips">
                @foreach($heroCards as $hc)
                    <div class="chip"><b>{{ $hc['value'] }}</b><span>{{ $hc['label'] }}</span></div>
                @endforeach
            </div>
        @endif
    </div>

    @if($heroImg)
        <div class="hero-art">
            <div class="hero-art-frame"><img src="{{ $heroImg }}" alt="{{ $siteName }}"></div>
            @foreach($heroCards as $i => $hc)
                <div class="float {{ $i === 0 ? 'float-tr' : 'float-bl' }}">
                    <span class="float-ic">{!! $icon($i === 0 ? 'check' : 'cap') !!}</span>
                    <div><b>{{ $hc['value'] }}</b><span>{{ $hc['label'] }}</span></div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- ═══ ÜNİVERSİTE ŞERİDİ (partner girmediyse yok) ═══ --}}
@if(!empty($universities))
<div class="wrap" style="padding-bottom:56px;">
    <div class="unis">
        <span class="unis-lbl">Öğrencilerimizin yerleştiği üniversiteler</span>
        @foreach($universities as $u)
            <span class="u">{{ $u }}</span>
        @endforeach
    </div>
</div>
@endif

{{-- ═══ HİZMETLER ═══ --}}
<div id="hizmetler" class="wrap" style="padding-bottom:64px;">
    <div class="sec-head">
        <span class="lbl">Hizmetler</span>
        <h2 class="h2">Sürecin her adımında yanınızdayız</h2>
        <p class="lead">Başvurudan yerleşime kadar tüm süreci uzman ekibimizle yönetiyoruz.</p>
    </div>
    <div class="grid g-svc">
        @foreach($services as $s)
            <div class="card">
                <span class="card-ic">{!! $icon($s['icon'] ?? 'default') !!}</span>
                <h3>{{ $s['title'] }}</h3>
                <p>{{ $s['desc'] }}</p>
                @if(!empty($s['items']))
                    <ul class="ticks">
                        @foreach($s['items'] as $item)
                            <li>{!! $icon('check') !!}{{ $item }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
</div>

{{-- ═══ SÜREÇ ═══ --}}
<div id="surec" class="proc">
    <div class="wrap sec">
        <div class="sec-head" style="max-width:560px;margin-bottom:42px;">
            <span class="lbl">Nasıl çalışır</span>
            <h2 class="h2" style="margin-bottom:0;">Dört adımda Almanya'ya</h2>
        </div>
        <div class="grid g-step">
            @foreach($steps as $st)
                <div class="step">
                    <div class="step-n">{{ $st['no'] }}</div>
                    <h3>{{ $st['title'] }}</h3>
                    <p>{{ $st['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ═══ İSTATİSTİK BANDI (partner girmediyse yok) ═══ --}}
@if(!empty($stats))
<div class="wrap sec">
    <div class="stats">
        @foreach($stats as $st)
            <div>
                <div class="v">{{ $st['value'] }}</div>
                <div class="l">{{ $st['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- ═══ HAKKIMIZDA ═══ --}}
<div class="wrap" style="padding:8px 26px 64px;">
    <div class="card" style="padding:38px 34px;">
        <span class="lbl">Hakkımızda</span>
        <h2 class="h2">{{ $siteName }}</h2>
        <p style="white-space:pre-line;font-size:15.5px;color:var(--muted);margin:0;max-width:760px;">{{ $aboutText }}</p>
    </div>
</div>

{{-- ═══ YORUMLAR — yalnız partnerin girdiği gerçek yorumlar ═══ --}}
@if(!empty($testimonials))
<div class="wrap" style="padding:0 26px 64px;">
    <div class="sec-head" style="max-width:560px;">
        <span class="lbl">Öğrenci yorumları</span>
        <h2 class="h2" style="margin-bottom:0;">Başarı hikâyeleriyle büyüyoruz</h2>
    </div>
    <div class="grid g-svc">
        @foreach($testimonials as $t)
            <figure class="quote" style="margin:0;">
                <p>“{{ $t['text'] }}”</p>
                @if(($t['name'] ?? '') !== '' || ($t['school'] ?? '') !== '')
                    <figcaption class="who">
                        <span class="avatar">{{ mb_strtoupper(mb_substr($t['name'] !== '' ? $t['name'] : $siteName, 0, 1)) }}</span>
                        <div>
                            @if(($t['name'] ?? '') !== '')<b>{{ $t['name'] }}</b>@endif
                            @if(($t['school'] ?? '') !== '')<span>{{ $t['school'] }}</span>@endif
                        </div>
                    </figcaption>
                @endif
            </figure>
        @endforeach
    </div>
</div>
@endif

{{-- ═══ NEDEN BİZ + EKİP + ROZET ═══ --}}
<div class="why-wrap">
    <div class="wrap sec">
        <div class="sec-head" style="max-width:560px;">
            <span class="lbl">Neden biz</span>
            <h2 class="h2" style="margin-bottom:0;">Farkımız, sistemli çalışmamız</h2>
        </div>
        <div class="grid g-why" style="margin-bottom:{{ (!empty($team) || $showBadge) ? '40px' : '0' }};">
            @foreach($whyUs as $w)
                <div class="why-card">
                    <span class="why-ic">{!! $icon($w['icon']) !!}</span>
                    <h3>{{ $w['title'] }}</h3>
                    <p>{{ $w['desc'] }}</p>
                </div>
            @endforeach
        </div>

        @if(!empty($team) || $showBadge)
            <div class="grid g-team">
                @foreach($team as $m)
                    <div class="member">
                        <span class="avatar">
                            @if(($m['photo'] ?? '') !== '')
                                <img src="{{ $m['photo'] }}" alt="{{ $m['name'] }}">
                            @else
                                {{ mb_strtoupper(mb_substr($m['name'], 0, 1)) }}
                            @endif
                        </span>
                        <div class="who">
                            <div>
                                <b>{{ $m['name'] }}</b>
                                @if(($m['title'] ?? '') !== '')<span>{{ $m['title'] }}</span>@endif
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Rozet kapalıysa MentorDE adı sayfada hiç geçmez (tam white-label) --}}
                @if($showBadge)
                    <div class="trust">
                        <span class="trust-ic">{!! $icon('shield') !!}</span>
                        <div>
                            <b>{{ config('brand.name', 'MentorDE') }} Yetkili Partneri</b>
                            <p>Başvuru, vize ve yerleşim süreçleriniz resmi partner ağı ve dijital altyapı üzerinden yürütülür.</p>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- ═══ PAKETLER (partner girmediyse yok) ═══ --}}
@if(!empty($packages))
<div id="paketler" class="wrap sec">
    <div class="sec-head">
        <span class="lbl">Paketler</span>
        <h2 class="h2">Size uygun destek seviyesi</h2>
        @if($packageNote !== '')<p class="lead">{{ $packageNote }}</p>@endif
    </div>
    <div class="grid g-pkg">
        @foreach($packages as $p)
            <div class="pkg{{ !empty($p['featured']) ? ' pkg-hi' : '' }}">
                <div class="pkg-top">
                    <h3>{{ $p['name'] }}</h3>
                    @if(($p['tag'] ?? '') !== '')<span class="pkg-tag">{{ $p['tag'] }}</span>@endif
                </div>
                @if(($p['desc'] ?? '') !== '')<p>{{ $p['desc'] }}</p>@endif
                @if(!empty($p['items']))
                    <ul class="ticks">
                        @foreach($p['items'] as $item)
                            <li>{!! $icon('check') !!}{{ $item }}</li>
                        @endforeach
                    </ul>
                @endif
                <a href="{{ $applyUrl }}" class="pkg-btn" data-track="cta_clicked" data-ph-cta-name="package_apply" data-ph-location="partner_lavanta_packages">Bu paketi görüşelim</a>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- ═══ S.S.S. (JS'siz: <details>) ═══ --}}
@if(!empty($faq))
<div id="sss" class="wrap" style="padding:8px 26px 64px;">
    <div class="sec-head" style="max-width:560px;">
        <span class="lbl">S.S.S.</span>
        <h2 class="h2" style="margin-bottom:0;">Aklınızdaki sorular</h2>
    </div>
    <div class="faq">
        @foreach($faq as $i => $f)
            <details @if($i === 0) open @endif>
                <summary>{{ $f['q'] }}<span class="ico">+</span></summary>
                <p>{{ $f['a'] }}</p>
            </details>
        @endforeach
    </div>
</div>
@endif

{{-- ═══ BAŞVURU / İLETİŞİM ═══ --}}
<div id="basvuru" class="wrap" style="padding:8px 26px 72px;">
    <div class="cta">
        <div class="cta-blob"></div>
        <div class="cta-l">
            <h2>Yolculuğunuza bugün başlayın</h2>
            <p>Başvurun, ekibimiz en kısa sürede sizinle iletişime geçsin.</p>
            <div class="cta-ticks">
                <span>{!! $icon('check') !!}Uçtan uca, tek elden yönetim</span>
                <span>{!! $icon('check') !!}Her adım panelden şeffaf takip</span>
            </div>
        </div>
        <div class="cta-r">
            <h3>Ücretsiz ön görüşme</h3>
            <p>Formu doldurun; hedeflerinizi dinleyip size özel bir yol haritası çıkaralım.</p>
            <a href="{{ $applyUrl }}" class="btn btn-primary" data-track="cta_clicked" data-ph-cta-name="footer_apply" data-ph-location="partner_lavanta_cta">Başvuru formunu aç {!! $icon('arrow') !!}</a>
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
                <span class="brand-mark" style="width:30px;height:30px;border-radius:10px;font-size:14px;">{{ mb_strtoupper(mb_substr($siteName, 0, 1)) }}</span>
                <span class="brand-name">{{ $siteName }}</span>
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
    <a href="{{ $waUrl }}" class="wa-float" target="_blank" rel="noopener" data-track="cta_clicked" data-ph-cta-name="whatsapp_float" data-ph-location="partner_lavanta_float">
        {!! $icon('wa') !!} WhatsApp
    </a>
@endif

</body>
</html>
