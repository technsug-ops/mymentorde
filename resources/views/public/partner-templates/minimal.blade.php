<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    /** Partner Template · MINIMAL — editoryel, bol boşluk, ince çizgiler, serif başlık.
     *  Veri: App\Support\PartnerSiteData::forDealer() (paylaşılan sözleşme). İkon: PartnerSiteData::icon().
     *  MODÜLER: bölümler minimal/sections/*.blade.php içinde, sıra/aç-kapa partnerin seçimi ($sections). */
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
    --ink:#141414; --body:#3a3a3a; --muted:#8a8a86; --line:#e6e4df; --line2:#f0efea;
    --paper:#fbfaf7; --card:#ffffff;
    --serif:"DM Serif Display",Georgia,"Times New Roman",serif;
    --sans:"Space Grotesk","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;scroll-behavior:smooth;}
body{font-family:var(--sans);color:var(--body);background:var(--paper);line-height:1.7;font-size:15px;-webkit-font-smoothing:antialiased;}
a{color:var(--ink);text-decoration:none;}
svg{width:1em;height:1em;}
.wrap{max-width:1080px;margin:0 auto;padding:0 28px;}
.serif{font-family:var(--serif);font-weight:400;}
.eyebrow{font-size:12px;letter-spacing:.28em;text-transform:uppercase;color:var(--muted);font-weight:600;}
.eyebrow.acc{color:var(--accent);}
.rule{height:1px;background:var(--line);border:0;margin:0;}

/* NAV */
.m-nav{border-bottom:1px solid var(--line);position:sticky;top:0;background:rgba(251,250,247,.9);backdrop-filter:blur(8px);z-index:40;}
.m-nav .wrap{display:flex;align-items:center;justify-content:space-between;padding:22px 28px;}
.m-logo{font-family:var(--serif);font-size:24px;color:var(--ink);letter-spacing:-.3px;font-weight:400;}
.m-logo img{max-height:38px;display:block;}
.m-nav-links{display:flex;gap:32px;font-size:13px;letter-spacing:.04em;}
.m-nav-links a{color:var(--body);}
.m-nav-links a:hover{color:var(--accent);}
.m-nav-cta{font-size:13px;letter-spacing:.04em;border-bottom:1.5px solid var(--accent);padding-bottom:3px;color:var(--ink) !important;}
@media(max-width:760px){.m-nav-links{display:none;}}

/* buttons */
.btn{display:inline-flex;align-items:center;gap:10px;font-size:14px;letter-spacing:.02em;font-weight:600;transition:all .2s;}
.btn-fill{background:var(--ink);color:#fff !important;padding:15px 30px;border-radius:2px;}
.btn-fill:hover{background:var(--accent);}
.btn-line{color:var(--ink) !important;border-bottom:1.5px solid var(--ink);padding-bottom:4px;border-radius:0;}
.btn-line:hover{border-color:var(--accent);color:var(--accent) !important;}
.btn svg{width:15px;height:15px;}

/* HERO */
.hero{padding:96px 0 84px;}
.hero-grid{display:grid;grid-template-columns:1.25fr .9fr;gap:70px;align-items:end;}
@media(max-width:900px){.hero-grid{grid-template-columns:1fr;gap:44px;}}
.hero h1{font-family:var(--serif);font-weight:400;font-size:clamp(42px,6vw,72px);line-height:1.04;letter-spacing:-1.5px;color:var(--ink);margin:22px 0 26px;}
.hero h1 em{font-style:italic;color:var(--accent);}
.hero-lead{font-size:18px;color:var(--body);max-width:520px;margin:0 0 34px;}
.hero-actions{display:flex;gap:28px;align-items:center;flex-wrap:wrap;}
.hero-side{border-left:1px solid var(--line);padding-left:36px;}
@media(max-width:900px){.hero-side{border-left:0;padding-left:0;border-top:1px solid var(--line);padding-top:32px;}}
.hero-side .hs-item{padding:18px 0;border-bottom:1px solid var(--line2);}
.hero-side .hs-item:last-child{border-bottom:0;}
.hero-side .hs-v{font-family:var(--serif);font-size:38px;color:var(--ink);line-height:1;}
.hero-side .hs-l{font-size:13px;color:var(--muted);margin-top:6px;letter-spacing:.02em;}

/* SECTION */
.sec{padding:84px 0;}
.sec-top{border-top:1px solid var(--line);}
.sec-head{max-width:640px;margin-bottom:52px;}
.sec-head h2{font-family:var(--serif);font-weight:400;font-size:clamp(30px,4vw,46px);line-height:1.1;letter-spacing:-.8px;color:var(--ink);margin:16px 0 0;}
.sec-head p{font-size:17px;color:var(--muted);margin:16px 0 0;}

/* SERVICES — hairline list grid */
.svc-grid{display:grid;grid-template-columns:1fr 1fr;gap:0;border-top:1px solid var(--line);}
@media(max-width:720px){.svc-grid{grid-template-columns:1fr;}}
.svc{padding:34px 0;border-bottom:1px solid var(--line);display:grid;grid-template-columns:auto 1fr;gap:24px;}
.svc:nth-child(odd){padding-right:40px;border-right:1px solid var(--line);}
.svc:nth-child(even){padding-left:40px;}
@media(max-width:720px){.svc:nth-child(odd){padding-right:0;border-right:0;}.svc:nth-child(even){padding-left:0;}}
.svc-n{font-family:var(--serif);font-size:20px;color:var(--accent);line-height:1.2;min-width:34px;}
.svc h3{font-size:19px;color:var(--ink);margin:0 0 8px;font-weight:600;letter-spacing:-.2px;}
.svc p{font-size:14px;color:var(--body);margin:0 0 12px;}
.svc-items{list-style:none;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:6px 16px;}
.svc-items li{font-size:12.5px;color:var(--muted);display:flex;align-items:center;gap:6px;}
.svc-items li::before{content:'';width:4px;height:4px;border-radius:50%;background:var(--accent);}

/* PROCESS — numbered row */
.steps{display:grid;grid-template-columns:repeat(var(--n,4),1fr);gap:34px;}
@media(max-width:820px){.steps{grid-template-columns:1fr 1fr;gap:32px;}}
@media(max-width:480px){.steps{grid-template-columns:1fr;}}
.step .sn{font-family:var(--serif);font-size:15px;color:var(--accent);letter-spacing:.1em;padding-bottom:14px;border-bottom:1px solid var(--line);display:block;margin-bottom:16px;}
.step h3{font-size:16px;color:var(--ink);margin:0 0 8px;font-weight:600;}
.step p{font-size:13.5px;color:var(--muted);margin:0;}

/* ABOUT */
.about-text{font-size:17px;line-height:1.8;color:var(--body);white-space:pre-line;}

/* STATS — hairline satırlar, iki sütun */
.stat-cols{display:grid;grid-template-columns:repeat(var(--n,2),1fr);gap:0 64px;}
@media(max-width:820px){.stat-cols{grid-template-columns:1fr;gap:0;}}
.stat-row{display:flex;align-items:baseline;justify-content:space-between;gap:20px;padding:22px 0;border-bottom:1px solid var(--line);}
.stat-row .sv{font-family:var(--serif);font-size:44px;color:var(--ink);line-height:1;}
.stat-row .sl{font-size:14px;color:var(--muted);letter-spacing:.02em;text-align:right;}

/* WHY — hairline satır listesi */
.why-rows{display:grid;grid-template-columns:1fr 1fr;gap:0 56px;border-top:1px solid var(--line);}
@media(max-width:820px){.why-rows{grid-template-columns:1fr;gap:0;}}
.why-row{display:grid;grid-template-columns:auto 1fr;gap:20px;padding:28px 0;border-bottom:1px solid var(--line);}
.why-ic{color:var(--accent);font-size:22px;line-height:1;}
.why-ic svg{width:22px;height:22px;}
.why-row h3{font-size:16px;color:var(--ink);margin:0 0 6px;font-weight:600;}
.why-row p{font-size:13.5px;color:var(--muted);margin:0;line-height:1.6;}

/* TEAM */
/* Eksik satır ORTALANIR — ekip sayısı partnerin girdiğine bağlı.
   NOT: .svc-grid bilerek grid kaldı; oradaki tasarım nth-child(odd/even)
   kenarlıklarına dayanıyor, flex'e çevrilse çizgiler bozulurdu. */
.team-grid{display:flex;flex-wrap:wrap;justify-content:center;gap:28px;}
.team-grid>*{flex:0 1 calc((100% - 84px)/4);min-width:190px;}
@media(max-width:820px){.team-grid>*{flex-basis:calc((100% - 28px)/2);}}
@media(max-width:480px){.team-grid>*{flex-basis:100%;min-width:0;}}

/* ÖĞRENCİ YORUMLARI
   ⚠ Bu bölümün stili HİÇ YOKTU: bölüm .q-grid/.q/.qm/.qw sınıflarını
   kullanıyor ama minimal.blade.php'de hiçbiri tanımlı değildi. Minimal
   şablonunu seçen bayi biçimsiz, üst üste yığılmış bir blok görüyordu. */
.q-grid{display:flex;flex-wrap:wrap;justify-content:center;gap:32px;}
.q-grid>*{flex:0 1 calc((100% - 64px)/3);min-width:250px;}
@media(max-width:820px){.q-grid>*{flex-basis:100%;min-width:0;}}
.q{border-top:1px solid var(--line);padding-top:22px;}
.qm{font-family:var(--serif);font-size:38px;line-height:1;color:var(--accent);margin-bottom:6px;}
.q blockquote{margin:0 0 16px;font-size:14.5px;color:var(--body);line-height:1.75;}
.qw{font-size:12.5px;color:var(--muted);letter-spacing:.02em;}
.qw b{color:var(--ink);font-weight:600;}
.tm{text-align:left;}
.tm-ph{width:74px;height:74px;border-radius:50%;object-fit:cover;margin-bottom:16px;border:1px solid var(--line);
    display:flex;align-items:center;justify-content:center;font-family:var(--serif);font-size:26px;color:var(--accent);background:var(--card);}
.tm h3{font-size:16px;color:var(--ink);margin:0 0 3px;font-weight:600;}
.tm p{font-size:13px;color:var(--muted);margin:0;}

/* ÜNİVERSİTELER — ince ayraçlı satır */
.uni-list{display:flex;flex-wrap:wrap;align-items:center;gap:0;}
.uni{font-family:var(--serif);font-size:19px;color:var(--ink);padding:8px 22px 8px 0;margin-right:22px;
    border-right:1px solid var(--line);line-height:1.3;}
.uni:last-child{border-right:0;margin-right:0;}

/* PAKETLER — çerçevesiz, hairline ayrımlı sütunlar */
.pkg-grid{display:grid;grid-template-columns:repeat(var(--n,3),1fr);gap:0;border-top:1px solid var(--line);}
@media(max-width:900px){.pkg-grid{grid-template-columns:1fr;}}
.pkg{display:flex;flex-direction:column;padding:38px 34px 38px 0;border-bottom:1px solid var(--line);border-right:1px solid var(--line);}
.pkg:last-child{border-right:0;padding-right:0;}
@media(max-width:900px){.pkg{border-right:0;padding-right:0;}}
.pkg-hi{background:var(--card);padding-left:28px;padding-right:28px;border-top:2px solid var(--accent);margin-top:-1px;}
.pkg-head{display:flex;align-items:baseline;gap:14px;flex-wrap:wrap;margin-bottom:12px;}
.pkg-head h3{font-size:26px;color:var(--ink);margin:0;line-height:1.15;}
.pkg-tag{font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:var(--accent);font-weight:600;}
.pkg-desc{font-size:14px;color:var(--body);margin:0 0 20px;}
.pkg-items{list-style:none;padding:18px 0 0;margin:0 0 26px;border-top:1px solid var(--line2);display:flex;flex-direction:column;gap:10px;}
.pkg-items li{font-size:13.5px;color:var(--muted);display:flex;align-items:flex-start;gap:10px;line-height:1.5;}
.pkg-items li::before{content:'';width:4px;height:4px;border-radius:50%;background:var(--accent);margin-top:9px;flex-shrink:0;}
.pkg-btn{margin-top:auto;align-self:flex-start;}

/* S.S.S. — JS yok, <details> akordeonu */
.faq{border-top:1px solid var(--line);max-width:820px;}
.faq-item{border-bottom:1px solid var(--line);}
.faq-item summary{display:flex;align-items:center;justify-content:space-between;gap:20px;cursor:pointer;list-style:none;
    padding:24px 0;font-size:17px;color:var(--ink);font-weight:600;letter-spacing:-.2px;}
.faq-item summary::-webkit-details-marker{display:none;}
.faq-item summary:hover{color:var(--accent);}
/* +/− işareti: iki ince çizgi, açılınca dikey olan kaybolur */
.faq-sign{position:relative;width:14px;height:14px;flex-shrink:0;}
.faq-sign::before,.faq-sign::after{content:'';position:absolute;background:var(--accent);transition:opacity .2s;}
.faq-sign::before{left:0;top:6px;width:14px;height:1.5px;}
.faq-sign::after{left:6px;top:0;width:1.5px;height:14px;}
.faq-item[open] .faq-sign::after{opacity:0;}
.faq-a{padding:0 0 26px;font-size:15px;color:var(--muted);line-height:1.8;max-width:640px;white-space:pre-line;}

/* TRUST */
.badge-line{display:flex;align-items:center;gap:16px;padding:26px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line);}
.badge-line svg{width:26px;height:26px;color:var(--accent);}
.badge-line .bt{font-size:15px;color:var(--ink);font-weight:600;}
.badge-line .bs{font-size:13px;color:var(--muted);}

/* CTA */
.cta{padding:110px 0;text-align:center;border-top:1px solid var(--line);}
.cta h2{font-family:var(--serif);font-weight:400;font-size:clamp(34px,5vw,58px);line-height:1.06;letter-spacing:-1px;color:var(--ink);margin:0 0 22px;max-width:720px;margin-left:auto;margin-right:auto;}
.cta p{font-size:18px;color:var(--muted);max-width:520px;margin:0 auto 38px;}
.cta-contacts{display:flex;gap:28px;justify-content:center;flex-wrap:wrap;margin-top:44px;font-size:14px;color:var(--muted);}
.cta-contacts a{color:var(--ink) !important;border-bottom:1px solid var(--line);}
.cta-contacts span{display:inline-flex;align-items:center;gap:8px;}
.cta-contacts svg{width:15px;height:15px;color:var(--accent);}

/* FOOTER */
footer{border-top:1px solid var(--line);padding:34px 0;font-size:13px;color:var(--muted);}
footer .wrap{display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;}
footer a{color:var(--ink);}
</style>
</head>
<body>

{{-- NAV — linkler SÖZLEŞMEDEN ($navLinks): kapalı/boş bölümün linki basılmaz --}}
<nav class="m-nav">
    <div class="wrap">
        <a href="#" class="m-logo">@if($brandLogoUrl)<img src="{{ $brandLogoUrl }}" alt="{{ $siteName }}">@else{{ $siteName }}@endif</a>
        <div class="m-nav-links">
            @foreach($navLinks as $nl)
                <a href="{{ $nl['href'] }}">{{ $nl['label'] }}</a>
            @endforeach
            <a href="#iletisim">İletişim</a>
        </div>
        <a href="{{ $applyUrl }}" class="m-nav-cta" data-track="cta_clicked" data-ph-cta-name="nav_apply" data-ph-location="partner_minimal_nav">Başvur</a>
    </div>
</nav>

{{-- HERO --}}
<section class="hero">
    <div class="wrap hero-grid" @if(empty($heroTrust)) style="grid-template-columns:1fr;" @endif>
        <div>
            <span class="eyebrow acc">{{ $siteName }} — Almanya Eğitim Danışmanlığı</span>
            <h1 class="serif">{{ $heroTitle }}</h1>
            <p class="hero-lead">{{ $heroSubtitle }}</p>
            <div class="hero-actions">
                <a href="{{ $applyUrl }}" class="btn btn-fill" data-track="cta_clicked" data-ph-cta-name="hero_apply" data-ph-location="partner_minimal_hero">Ücretsiz Danışmanlık Al {!! $icon('arrow') !!}</a>
                <a href="#hizmetler" class="btn btn-line">Hizmetlerimiz</a>
            </div>
        </div>
        @if(!empty($heroTrust))
            {{-- Sadece partnerin kendi girdiği istatistikler — uydurma rakam yok. --}}
            <div class="hero-side">
                @foreach($heroTrust as $ht)
                    <div class="hs-item"><div class="hs-v">{{ $ht['value'] }}</div><div class="hs-l">{{ $ht['label'] }}</div></div>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- ═══ SIRALANABİLİR BÖLÜMLER ═══
    Partnerin seçtiği sıra + aç/kapa: $sections (App\Support\PartnerSiteSections).
    Her bölüm ayrı partial: minimal/sections/{key}.blade.php.
    @includeIf: eksik partial sayfayı düşürmez. --}}
@foreach($sections as $sectionKey)
    @includeIf('public.partner-templates.minimal.sections.' . $sectionKey)
@endforeach

{{-- CTA --}}
<section id="iletisim" class="cta">
    <div class="wrap">
        <span class="eyebrow acc">Ücretsiz Ön Değerlendirme</span>
        <h2 class="serif" style="margin-top:18px;">Almanya eğitim yolculuğunuza bugün başlayın</h2>
        <p>Başvurun, ekibimiz en kısa sürede sizinle iletişime geçsin. İlk görüşme tamamen ücretsizdir.</p>
        <a href="{{ $applyUrl }}" class="btn btn-fill" data-track="cta_clicked" data-ph-cta-name="footer_apply" data-ph-location="partner_minimal_cta">Ücretsiz Danışmanlık Başvurusu {!! $icon('arrow') !!}</a>
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
        <span>© {{ now()->year }} {{ $siteName }}</span>
        @if($showBadge)<span>@include('partials.vendor-credit')</span>@endif
    </div>
</footer>

</body>
</html>
