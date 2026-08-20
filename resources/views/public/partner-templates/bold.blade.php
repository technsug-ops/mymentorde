<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    /** Partner Template · BOLD — koyu iddialı hero, yüksek kontrast, büyük display tipografi.
     *  Veri: App\Support\PartnerSiteData::forDealer() (paylaşılan sözleşme). İkon: PartnerSiteData::icon(). */
    $accent   = \App\Support\PartnerSiteData::accent($accentColor ?? null);
    $siteName = $brandName ?? config('brand.name', 'MentorDE');
    $icon     = fn (string $k) => \App\Support\PartnerSiteData::icon($k);
    $waDigits = $whatsapp ? preg_replace('/\D+/', '', $whatsapp) : '';
    $waUrl    = $waDigits !== '' ? 'https://wa.me/' . $waDigits : null;
@endphp
<title>{{ $siteName }} — Almanya Eğitim Danışmanlığı</title>
@include('partials.favicon')
<meta name="description" content="{{ Str::limit(strip_tags($heroSubtitle ?? ''), 155) }}">
<meta name="robots" content="index, follow">
<meta property="og:title" content="{{ $siteName }} — Almanya Eğitim Danışmanlığı">
<meta property="og:description" content="{{ Str::limit(strip_tags($heroSubtitle ?? ''), 200) }}">
<meta property="og:type" content="website">
{{-- Fontlar SADECE lokal (DSGVO): Google Fonts CDN'e istek atma — bkz. public/fonts/local-fonts.css --}}
<link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">
<style>
:root{
    --accent:{{ $accent }};
    --accent-d:color-mix(in srgb,{{ $accent }} 78%,#000);
    --dark:#0e0e15; --dark2:#171722; --dark3:#20202e;
    --ink:#12121a; --body:#4a4a56; --muted:#9a9aa6; --line:#e9e8ee; --bg:#f6f6f9;
    --disp:"Plus Jakarta Sans","Space Grotesk",-apple-system,sans-serif;
    --sans:"Space Grotesk","Plus Jakarta Sans",-apple-system,sans-serif;
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;scroll-behavior:smooth;}
body{font-family:var(--sans);color:var(--body);background:var(--bg);line-height:1.65;font-size:15px;-webkit-font-smoothing:antialiased;}
a{color:var(--accent);text-decoration:none;}
svg{width:1em;height:1em;}
.wrap{max-width:1180px;margin:0 auto;padding:0 26px;}
.kick{display:inline-flex;align-items:center;gap:8px;font-family:var(--disp);font-size:12px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:var(--accent);}
.kick::before{content:'';width:22px;height:2px;background:var(--accent);}
.kick.c{justify-content:center;}

/* buttons */
.btn{display:inline-flex;align-items:center;gap:10px;font-family:var(--disp);font-weight:700;font-size:15px;transition:all .2s;border-radius:12px;}
.btn-acc{background:var(--accent);color:#fff !important;padding:17px 34px;box-shadow:0 12px 30px color-mix(in srgb,var(--accent) 40%,transparent);}
.btn-acc:hover{background:var(--accent-d);transform:translateY(-2px);}
.btn-out{border:2px solid rgba(255,255,255,.25);color:#fff !important;padding:15px 30px;}
.btn-out:hover{border-color:#fff;background:rgba(255,255,255,.08);}
.btn-dark{background:var(--ink);color:#fff !important;padding:16px 32px;}
.btn-dark:hover{background:var(--accent);transform:translateY(-2px);}
.btn svg{width:16px;height:16px;}

/* NAV (over dark hero) */
.b-nav{position:absolute;top:0;left:0;right:0;z-index:20;}
.b-nav .wrap{display:flex;align-items:center;justify-content:space-between;padding:24px 26px;}
.b-logo{font-family:var(--disp);font-weight:800;font-size:23px;color:#fff;letter-spacing:-.4px;}
.b-logo img{max-height:40px;display:block;}
.b-nav-links{display:flex;gap:30px;font-size:14px;font-weight:600;}
.b-nav-links a{color:rgba(255,255,255,.72);}
.b-nav-links a:hover{color:#fff;}
.b-nav-cta{font-family:var(--disp);font-weight:700;font-size:13px;background:var(--accent);color:#fff !important;padding:11px 20px;border-radius:10px;}
@media(max-width:820px){.b-nav-links{display:none;}}

/* HERO (dark) */
.hero{position:relative;background:var(--dark);color:#fff;padding:150px 0 100px;overflow:hidden;}
.hero::before{content:'';position:absolute;inset:0;z-index:0;
    background:radial-gradient(50% 55% at 78% 12%,color-mix(in srgb,var(--accent) 55%,transparent),transparent 62%),
              radial-gradient(45% 50% at 10% 100%,color-mix(in srgb,var(--accent) 34%,transparent),transparent 60%);}
.hero::after{content:'';position:absolute;inset:0;z-index:0;opacity:.5;
    background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px);
    background-size:56px 56px;-webkit-mask-image:radial-gradient(70% 70% at 60% 30%,#000,transparent 75%);mask-image:radial-gradient(70% 70% at 60% 30%,#000,transparent 75%);}
.hero .wrap{position:relative;z-index:1;}
.hero-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:56px;align-items:center;}
@media(max-width:900px){.hero-grid{grid-template-columns:1fr;gap:40px;}}
.hero h1{font-family:var(--disp);font-weight:800;font-size:clamp(44px,6.5vw,76px);line-height:1;letter-spacing:-2px;color:#fff;margin:24px 0 24px;}
.hero h1 .hl{color:var(--accent);}
.hero-lead{font-size:19px;color:rgba(255,255,255,.72);max-width:520px;margin:0 0 34px;}
.hero-ctas{display:flex;gap:16px;flex-wrap:wrap;}
.hero-chips{display:flex;gap:14px;margin-top:40px;flex-wrap:wrap;}
.hchip{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:18px 22px;backdrop-filter:blur(6px);}
.hchip .hv{font-family:var(--disp);font-weight:800;font-size:30px;color:#fff;line-height:1;}
.hchip .hl2{font-size:12px;color:rgba(255,255,255,.6);margin-top:7px;letter-spacing:.03em;}
/* hero visual: stacked bold card */
.hero-panel{background:linear-gradient(150deg,var(--dark2),var(--dark3));border:1px solid rgba(255,255,255,.1);border-radius:24px;padding:28px;box-shadow:0 40px 80px rgba(0,0,0,.5);}
.hp-top{display:flex;align-items:center;gap:12px;margin-bottom:22px;}
.hp-ic{width:48px;height:48px;border-radius:13px;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:23px;}
.hp-t{font-family:var(--disp);font-weight:700;color:#fff;font-size:16px;}
.hp-s{font-size:12px;color:rgba(255,255,255,.55);}
.hp-row{display:flex;align-items:center;gap:12px;padding:14px 0;border-top:1px solid rgba(255,255,255,.08);color:#fff;font-size:14px;font-weight:600;}
.hp-row .d{width:26px;height:26px;border-radius:50%;background:color-mix(in srgb,var(--accent) 26%,transparent);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:14px;}
.hp-row .d svg{width:15px;height:15px;}

/* SECTIONS */
.sec{padding:88px 0;}
.sec-head{margin-bottom:52px;}
.sec-head.c{text-align:center;max-width:680px;margin-left:auto;margin-right:auto;}
.sec-head h2{font-family:var(--disp);font-weight:800;font-size:clamp(30px,4.4vw,50px);line-height:1.04;letter-spacing:-1.2px;color:var(--ink);margin:16px 0 0;}
.sec-head p{font-size:17px;color:var(--muted);margin:16px 0 0;}

/* SERVICES — bold cards */
/* Eksik satır ORTALANIR — kart sayısı partnerin girdiğine bağlı.
   Genişlik: (100% - (sütun-1)*gap) / sütun */
.svc-grid{display:flex;flex-wrap:wrap;justify-content:center;gap:22px;}
.svc-grid>*{flex:0 1 calc((100% - 44px)/3);min-width:240px;}
@media(max-width:860px){.svc-grid>*{flex-basis:calc((100% - 22px)/2);}}
@media(max-width:540px){.svc-grid>*{flex-basis:100%;min-width:0;}}
.svc{background:#fff;border-radius:20px;padding:32px 28px;border:1px solid var(--line);position:relative;transition:all .2s;overflow:hidden;}
.svc:hover{transform:translateY(-6px);box-shadow:0 26px 50px rgba(18,18,26,.1);}
.svc-n{position:absolute;top:20px;right:26px;font-family:var(--disp);font-weight:800;font-size:44px;color:var(--line);line-height:1;}
.svc-ic{width:58px;height:58px;border-radius:15px;background:var(--ink);color:#fff;display:flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:20px;}
.svc:hover .svc-ic{background:var(--accent);}
.svc h3{font-family:var(--disp);font-weight:700;font-size:19px;color:var(--ink);margin:0 0 9px;}
.svc p{font-size:14px;color:var(--body);margin:0 0 14px;}
.svc-items{list-style:none;padding:14px 0 0;margin:0;border-top:1px solid var(--line);display:flex;flex-direction:column;gap:9px;}
.svc-items li{display:flex;align-items:center;gap:9px;font-size:13px;color:var(--ink);font-weight:500;}
.svc-items li svg{width:16px;height:16px;color:var(--accent);flex-shrink:0;}

/* PROCESS */
.steps{display:grid;grid-template-columns:repeat(4,1fr);gap:22px;}
@media(max-width:820px){.steps{grid-template-columns:1fr 1fr;}}
@media(max-width:480px){.steps{grid-template-columns:1fr;}}
.step{background:#fff;border:1px solid var(--line);border-radius:18px;padding:28px 24px;position:relative;}
.step .sn{font-family:var(--disp);font-weight:800;font-size:16px;color:#fff;background:var(--accent);width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;}
.step h3{font-family:var(--disp);font-weight:700;font-size:16px;color:var(--ink);margin:0 0 8px;}
.step p{font-size:13.5px;color:var(--body);margin:0;}

/* STAT BAND (dark) */
.band{background:var(--dark);color:#fff;position:relative;overflow:hidden;padding:70px 0;}
.band::before{content:'';position:absolute;inset:0;background:radial-gradient(50% 80% at 80% 20%,color-mix(in srgb,var(--accent) 34%,transparent),transparent 60%);}
.band .wrap{position:relative;z-index:1;}
.band-grid{display:grid;grid-template-columns:repeat(var(--n,4),1fr);gap:24px;}
@media(max-width:640px){.band-grid{grid-template-columns:1fr 1fr;}}
.band .bi{text-align:center;}
.band .bv{font-family:var(--disp);font-weight:800;font-size:clamp(38px,5vw,58px);line-height:1;color:#fff;}
.band .bv .u{color:var(--accent);}
.band .bl{font-size:13px;color:rgba(255,255,255,.6);margin-top:10px;text-transform:uppercase;letter-spacing:.06em;}

/* ABOUT */
.about-grid{display:grid;grid-template-columns:1fr 1fr;gap:56px;align-items:center;}
@media(max-width:820px){.about-grid{grid-template-columns:1fr;gap:32px;}}
.about-text{font-size:17px;line-height:1.8;color:var(--body);white-space:pre-line;}
.about-why{display:flex;flex-direction:column;gap:14px;}
.wc{display:flex;gap:14px;align-items:flex-start;background:#fff;border:1px solid var(--line);border-radius:14px;padding:20px;}
.wc-ic{width:44px;height:44px;border-radius:12px;background:color-mix(in srgb,var(--accent) 12%,#fff);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:21px;flex-shrink:0;}
.wc h3{font-family:var(--disp);font-weight:700;font-size:15px;color:var(--ink);margin:0 0 4px;}
.wc p{font-size:13px;color:var(--body);margin:0;}

/* TEAM */
.team-grid{display:flex;flex-wrap:wrap;justify-content:center;gap:22px;}
.team-grid>*{flex:0 1 calc((100% - 66px)/4);min-width:200px;}
@media(max-width:820px){.team-grid>*{flex-basis:calc((100% - 22px)/2);}}
@media(max-width:480px){.team-grid>*{flex-basis:100%;min-width:0;}}
.tm{background:#fff;border:1px solid var(--line);border-radius:18px;padding:26px;text-align:center;transition:all .2s;}
.tm:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(18,18,26,.08);}
.tm-ph{width:88px;height:88px;border-radius:18px;object-fit:cover;margin:0 auto 15px;background:var(--ink);color:#fff;
    display:flex;align-items:center;justify-content:center;font-family:var(--disp);font-weight:800;font-size:30px;}
.tm h3{font-family:var(--disp);font-weight:700;font-size:16px;color:var(--ink);margin:0 0 3px;}
.tm p{font-size:13px;color:var(--muted);margin:0;}

/* TESTIMONIALS */
.q-grid{display:flex;flex-wrap:wrap;justify-content:center;gap:22px;}
.q-grid>*{flex:0 1 calc((100% - 44px)/3);min-width:260px;}
@media(max-width:820px){.q-grid>*{flex-basis:100%;min-width:0;}}
.qc{background:#fff;border:1px solid var(--line);border-radius:20px;padding:30px;}
.qc .st{color:var(--accent);font-size:16px;letter-spacing:2px;margin-bottom:14px;}
.qc blockquote{margin:0 0 20px;font-size:15px;line-height:1.7;color:var(--ink);}
.qc .who{display:flex;align-items:center;gap:12px;}
.qc .av{width:46px;height:46px;border-radius:13px;background:var(--ink);color:#fff;font-family:var(--disp);font-weight:800;display:flex;align-items:center;justify-content:center;}
.qc .nm{font-family:var(--disp);font-weight:700;color:var(--ink);font-size:14px;}
.qc .rl{font-size:12px;color:var(--muted);}

/* TRUST */
.trust{background:var(--ink);border-radius:22px;padding:36px;display:flex;gap:22px;align-items:center;flex-wrap:wrap;justify-content:center;text-align:center;color:#fff;}
.trust-b{display:inline-flex;align-items:center;gap:14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);border-radius:16px;padding:16px 24px;}
.trust-b svg{width:34px;height:34px;color:var(--accent);}
.trust-b .tt{font-family:var(--disp);font-weight:700;font-size:16px;}
.trust-b .ts{font-size:12px;color:rgba(255,255,255,.6);}
.trust p{margin:0;color:rgba(255,255,255,.72);font-size:15px;max-width:420px;}

/* CTA */
.cta{background:var(--accent);color:#fff;padding:96px 0;text-align:center;position:relative;overflow:hidden;}
.cta::before{content:'';position:absolute;inset:0;background:radial-gradient(50% 70% at 20% 20%,rgba(255,255,255,.18),transparent 60%);}
.cta .wrap{position:relative;z-index:1;}
.cta h2{font-family:var(--disp);font-weight:800;font-size:clamp(32px,5vw,56px);line-height:1.02;letter-spacing:-1.4px;margin:0 0 18px;}
.cta p{font-size:18px;color:rgba(255,255,255,.9);max-width:560px;margin:0 auto 36px;}
.cta .btn-white{background:#fff;color:var(--ink) !important;padding:18px 40px;font-family:var(--disp);font-weight:800;border-radius:12px;box-shadow:0 16px 40px rgba(0,0,0,.25);}
.cta .btn-white:hover{transform:translateY(-2px);}
.cta-contacts{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-top:40px;}
.cta-contacts span{display:inline-flex;align-items:center;gap:9px;background:rgba(255,255,255,.16);padding:11px 18px;border-radius:12px;font-size:14px;color:#fff;font-weight:600;}
.cta-contacts a{color:#fff !important;}
.cta-contacts svg{width:16px;height:16px;}

/* FOOTER */
footer{background:var(--dark);color:rgba(255,255,255,.55);padding:34px 0;font-size:13px;}
footer .wrap{display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;}
footer a{color:#fff;}

/* ── MODÜLER BÖLÜMLER ── */
/* adım sayısı sözleşmeden geldiği için sütun sayısı dinamik */
.steps{grid-template-columns:repeat(var(--n,4),1fr);}
@media(max-width:900px){.steps{grid-template-columns:1fr 1fr;}}
@media(max-width:520px){.steps{grid-template-columns:1fr;}}

/* NEDEN BİZ — artık bağımsız bölüm (eskiden hakkımızda'nın sağ sütunuydu) */
.why-grid{display:grid;grid-template-columns:repeat(var(--n,3),1fr);gap:20px;}
@media(max-width:900px){.why-grid{grid-template-columns:1fr;}}

/* ÜNİVERSİTELER */
.uni-strip{display:flex;flex-direction:column;align-items:center;gap:20px;text-align:center;}
.uni-row{display:flex;flex-wrap:wrap;gap:12px;justify-content:center;}
.uni{font-family:var(--disp);font-weight:700;font-size:14px;color:var(--ink);background:#fff;
    border:2px solid var(--line);border-radius:12px;padding:11px 20px;transition:all .2s;}
.uni:hover{border-color:var(--accent);color:var(--accent);}

/* PAKETLER */
.pkg-grid{display:grid;grid-template-columns:repeat(var(--n,3),1fr);gap:22px;align-items:stretch;}
@media(max-width:900px){.pkg-grid{grid-template-columns:1fr;}}
.pkg{position:relative;display:flex;flex-direction:column;background:#fff;border:2px solid var(--line);
    border-radius:20px;padding:32px 28px;transition:all .2s;}
.pkg:hover{transform:translateY(-4px);border-color:color-mix(in srgb,var(--accent) 45%,transparent);}
.pkg-hi{background:var(--dark);border-color:var(--dark);color:rgba(255,255,255,.78);}
.pkg-hi h3,.pkg-hi .pkg-desc{color:#fff;}
.pkg-hi .pkg-items li{color:rgba(255,255,255,.8);}
.pkg-flag{position:absolute;top:-13px;left:28px;background:var(--accent);color:#fff;font-family:var(--disp);
    font-weight:800;font-size:11px;letter-spacing:.1em;text-transform:uppercase;padding:7px 14px;border-radius:20px;}
.pkg-head{display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;margin-bottom:12px;}
.pkg-head h3{font-family:var(--disp);font-weight:800;font-size:24px;color:var(--ink);margin:0;letter-spacing:-.6px;}
.pkg-tag{font-family:var(--disp);font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--accent);}
.pkg-desc{font-size:14px;color:var(--body);margin:0 0 20px;line-height:1.6;}
.pkg-items{list-style:none;padding:20px 0 0;margin:0 0 26px;border-top:2px solid color-mix(in srgb,var(--line) 60%,transparent);
    display:flex;flex-direction:column;gap:11px;}
.pkg-hi .pkg-items{border-top-color:rgba(255,255,255,.16);}
.pkg-items li{display:flex;align-items:flex-start;gap:10px;font-size:13.5px;color:var(--body);line-height:1.5;}
.pkg-items li svg{width:17px;height:17px;color:var(--accent);flex-shrink:0;margin-top:2px;}
.pkg-btn{margin-top:auto;justify-content:center;background:var(--ink);color:#fff !important;padding:14px 24px;font-size:14px;}
.pkg-btn:hover{background:var(--accent);}
.pkg-hi .pkg-btn{background:var(--accent);}
.pkg-hi .pkg-btn:hover{background:#fff;color:var(--ink) !important;}

/* S.S.S. — JS yok, <details> akordeonu */
.faq{max-width:840px;margin:0 auto;display:flex;flex-direction:column;gap:14px;}
.faq-item{background:#fff;border:2px solid var(--line);border-radius:16px;overflow:hidden;transition:all .2s;}
.faq-item[open]{border-color:var(--accent);}
.faq-item summary{display:flex;align-items:center;justify-content:space-between;gap:18px;cursor:pointer;list-style:none;
    padding:22px 26px;font-family:var(--disp);font-weight:700;font-size:16px;color:var(--ink);letter-spacing:-.3px;}
.faq-item summary::-webkit-details-marker{display:none;}
.faq-item summary:hover{color:var(--accent);}
/* +/− işareti: açılınca dikey çizgi kaybolur */
.faq-sign{position:relative;width:16px;height:16px;flex-shrink:0;}
.faq-sign::before,.faq-sign::after{content:'';position:absolute;background:var(--accent);border-radius:2px;transition:opacity .2s;}
.faq-sign::before{left:0;top:7px;width:16px;height:2px;}
.faq-sign::after{left:7px;top:0;width:2px;height:16px;}
.faq-item[open] .faq-sign::after{opacity:0;}
.faq-a{padding:0 26px 24px;font-size:14.5px;color:var(--body);line-height:1.75;white-space:pre-line;}
</style>
</head>
<body>

{{-- HERO + NAV --}}
<header class="hero">
    <nav class="b-nav"><div class="wrap">
        <a href="#" class="b-logo">@if($brandLogoUrl)<img src="{{ $brandLogoUrl }}" alt="{{ $siteName }}">@else{{ $siteName }}@endif</a>
        {{-- Menü SÖZLEŞMEDEN ($navLinks): kapalı/boş bölümün linki basılmaz --}}
        <div class="b-nav-links">
            @foreach($navLinks as $nl)<a href="{{ $nl['href'] }}">{{ $nl['label'] }}</a>@endforeach<a href="#iletisim">İletişim</a>
        </div>
        <a href="{{ $applyUrl }}" class="b-nav-cta" data-track="cta_clicked" data-ph-cta-name="nav_apply" data-ph-location="partner_bold_nav">Başvur →</a>
    </div></nav>
    <div class="wrap hero-grid">
        <div>
            <span class="kick" style="color:var(--accent)">{{ $siteName }} · Almanya Eğitim</span>
            <h1>{{ $heroTitle }}</h1>
            <p class="hero-lead">{{ $heroSubtitle }}</p>
            <div class="hero-ctas">
                <a href="{{ $applyUrl }}" class="btn btn-acc" data-track="cta_clicked" data-ph-cta-name="hero_apply" data-ph-location="partner_bold_hero">Ücretsiz Danışmanlık Al {!! $icon('arrow') !!}</a>
                <a href="#hizmetler" class="btn btn-out">Hizmetlerimiz</a>
            </div>
            @if(!empty($heroTrust))
                {{-- Sadece partnerin kendi girdiği istatistikler — uydurma rakam yok. --}}
                <div class="hero-chips">
                    @foreach($heroTrust as $ht)
                        <div class="hchip"><div class="hv">{{ $ht['value'] }}</div><div class="hl2">{{ $ht['label'] }}</div></div>
                    @endforeach
                </div>
            @endif
        </div>
        <div>
            @if(!empty($dealer?->site_hero_image_path))
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($dealer->site_hero_image_path) }}" alt="{{ $siteName }}" style="width:100%;border-radius:24px;box-shadow:0 40px 80px rgba(0,0,0,.5);">
            @else
                <div class="hero-panel">
                    <div class="hp-top"><div class="hp-ic">{!! $icon('chart') !!}</div><div><div class="hp-t">Süreç Takip Paneli</div><div class="hp-s">Dijital, anlık, şeffaf</div></div></div>
                    <div class="hp-row"><span class="d">{!! $icon('check') !!}</span> Başvuru hazırlığı tamamlandı</div>
                    <div class="hp-row"><span class="d">{!! $icon('check') !!}</span> Üniversite kabulü alındı</div>
                    <div class="hp-row"><span class="d">{!! $icon('check') !!}</span> Vize dosyası onaylandı</div>
                </div>
            @endif
        </div>
    </div>
</header>

{{-- ═══ SIRALANABİLİR BÖLÜMLER ═══
    Partnerin seçtiği sıra + aç/kapa: $sections (App\Support\PartnerSiteSections).
    Her bölüm ayrı partial: bold/sections/{key}.blade.php.
    @includeIf: eksik partial sayfayı düşürmez. --}}
@foreach($sections as $sectionKey)
    @includeIf('public.partner-templates.bold.sections.' . $sectionKey)
@endforeach

{{-- CTA --}}
<section id="iletisim" class="cta">
    <div class="wrap">
        <h2>Almanya Eğitim Yolculuğunuza Bugün Başlayın</h2>
        <p>Başvurun, ekibimiz en kısa sürede sizinle iletişime geçsin. İlk görüşme tamamen ücretsizdir.</p>
        <a href="{{ $applyUrl }}" class="btn btn-white" data-track="cta_clicked" data-ph-cta-name="footer_apply" data-ph-location="partner_bold_cta">Ücretsiz Danışmanlık Başvurusu {!! $icon('arrow') !!}</a>
        @if($waUrl || $phone || $address)
            <div class="cta-contacts">
                @if($waUrl)<span>{!! $icon('default') !!} <a href="{{ $waUrl }}" target="_blank" rel="noopener">WhatsApp</a></span>@endif
                @if($phone)<span>{!! $icon('passport') !!} <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a></span>@endif
                @if($address)<span>{!! $icon('home') !!} {{ $address }}</span>@endif
                @if($instagram)<span>{!! $icon('default') !!} <a href="https://instagram.com/{{ ltrim($instagram, '@') }}" target="_blank" rel="noopener">{{ '@' . ltrim($instagram, '@') }}</a></span>@endif
            </div>
        @endif
    </div>
</section>

{{-- FOOTER --}}
<footer>
    <div class="wrap">
        <span>© {{ now()->year }} {{ $siteName }} — Tüm hakları saklıdır.</span>
        @if($showBadge)<span>@include('partials.vendor-credit')</span>@endif
    </div>
</footer>

</body>
</html>
