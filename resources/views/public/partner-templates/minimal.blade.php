<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    /** Partner Template · MINIMAL — editoryel, bol boşluk, ince çizgiler, serif başlık.
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
<link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&display=swap">
<style>
:root{
    --accent:{{ $accent }};
    --ink:#141414; --body:#3a3a3a; --muted:#8a8a86; --line:#e6e4df; --line2:#f0efea;
    --paper:#fbfaf7; --card:#ffffff;
    --serif:"Fraunces",Georgia,"Times New Roman",serif;
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
.m-logo{font-family:var(--serif);font-size:24px;color:var(--ink);letter-spacing:-.3px;font-weight:600;}
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
.hero h1{font-family:var(--serif);font-weight:500;font-size:clamp(42px,6vw,72px);line-height:1.04;letter-spacing:-1.5px;color:var(--ink);margin:22px 0 26px;}
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
.sec-head h2{font-family:var(--serif);font-weight:500;font-size:clamp(30px,4vw,46px);line-height:1.1;letter-spacing:-.8px;color:var(--ink);margin:16px 0 0;}
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
.steps{display:grid;grid-template-columns:repeat(4,1fr);gap:34px;}
@media(max-width:820px){.steps{grid-template-columns:1fr 1fr;gap:32px;}}
@media(max-width:480px){.steps{grid-template-columns:1fr;}}
.step .sn{font-family:var(--serif);font-size:15px;color:var(--accent);letter-spacing:.1em;padding-bottom:14px;border-bottom:1px solid var(--line);display:block;margin-bottom:16px;}
.step h3{font-size:16px;color:var(--ink);margin:0 0 8px;font-weight:600;}
.step p{font-size:13.5px;color:var(--muted);margin:0;}

/* ABOUT + STATS */
.about-grid{display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center;}
@media(max-width:820px){.about-grid{grid-template-columns:1fr;gap:36px;}}
.about-text{font-size:17px;line-height:1.8;color:var(--body);white-space:pre-line;}
.stat-rows{display:flex;flex-direction:column;}
.stat-row{display:flex;align-items:baseline;justify-content:space-between;gap:20px;padding:22px 0;border-bottom:1px solid var(--line);}
.stat-row:first-child{border-top:1px solid var(--line);}
.stat-row .sv{font-family:var(--serif);font-size:44px;color:var(--ink);line-height:1;}
.stat-row .sl{font-size:14px;color:var(--muted);letter-spacing:.02em;text-align:right;}

/* TEAM */
.team-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:28px;}
@media(max-width:820px){.team-grid{grid-template-columns:1fr 1fr;}}
@media(max-width:480px){.team-grid{grid-template-columns:1fr;}}
.tm{text-align:left;}
.tm-ph{width:74px;height:74px;border-radius:50%;object-fit:cover;margin-bottom:16px;border:1px solid var(--line);
    display:flex;align-items:center;justify-content:center;font-family:var(--serif);font-size:26px;color:var(--accent);background:var(--card);}
.tm h3{font-size:16px;color:var(--ink);margin:0 0 3px;font-weight:600;}
.tm p{font-size:13px;color:var(--muted);margin:0;}

/* TESTIMONIALS */
.q-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:44px;}
@media(max-width:820px){.q-grid{grid-template-columns:1fr;gap:36px;}}
.q .qm{font-family:var(--serif);font-size:44px;line-height:.6;color:var(--accent);}
.q blockquote{margin:16px 0 18px;font-size:16px;line-height:1.7;color:var(--ink);font-family:var(--serif);font-weight:400;}
.q .qw{font-size:13px;color:var(--muted);}
.q .qw b{color:var(--ink);font-weight:600;}

/* TRUST */
.badge-line{display:flex;align-items:center;gap:16px;padding:26px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line);}
.badge-line svg{width:26px;height:26px;color:var(--accent);}
.badge-line .bt{font-size:15px;color:var(--ink);font-weight:600;}
.badge-line .bs{font-size:13px;color:var(--muted);}

/* CTA */
.cta{padding:110px 0;text-align:center;border-top:1px solid var(--line);}
.cta h2{font-family:var(--serif);font-weight:500;font-size:clamp(34px,5vw,58px);line-height:1.06;letter-spacing:-1px;color:var(--ink);margin:0 0 22px;max-width:720px;margin-left:auto;margin-right:auto;}
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

{{-- NAV --}}
<nav class="m-nav">
    <div class="wrap">
        <a href="#" class="m-logo">@if($brandLogoUrl)<img src="{{ $brandLogoUrl }}" alt="{{ $siteName }}">@else{{ $siteName }}@endif</a>
        <div class="m-nav-links">
            <a href="#hizmetler">Hizmetler</a>
            <a href="#hakkimizda">Hakkımızda</a>
            @if(!empty($team))<a href="#ekip">Ekip</a>@endif
            <a href="#iletisim">İletişim</a>
        </div>
        <a href="{{ $applyUrl }}" class="m-nav-cta" data-track="cta_clicked" data-ph-cta-name="nav_apply" data-ph-location="partner_minimal_nav">Başvur</a>
    </div>
</nav>

{{-- HERO --}}
<section class="hero">
    <div class="wrap hero-grid">
        <div>
            <span class="eyebrow acc">{{ $siteName }} — Almanya Eğitim Danışmanlığı</span>
            <h1 class="serif">{{ $heroTitle }}</h1>
            <p class="hero-lead">{{ $heroSubtitle }}</p>
            <div class="hero-actions">
                <a href="{{ $applyUrl }}" class="btn btn-fill" data-track="cta_clicked" data-ph-cta-name="hero_apply" data-ph-location="partner_minimal_hero">Ücretsiz Danışmanlık Al {!! $icon('arrow') !!}</a>
                <a href="#hizmetler" class="btn btn-line">Hizmetlerimiz</a>
            </div>
        </div>
        <div class="hero-side">
            <div class="hs-item"><div class="hs-v">{{ $heroTrust['students'] }}</div><div class="hs-l">Yönlendirilen öğrenci</div></div>
            <div class="hs-item"><div class="hs-v">{{ $heroTrust['success'] }}</div><div class="hs-l">Vize başarı oranı</div></div>
            <div class="hs-item"><div class="hs-v">{{ $heroTrust['rating'] }}</div><div class="hs-l">Öğrenci memnuniyeti</div></div>
        </div>
    </div>
</section>

{{-- SERVICES --}}
<section id="hizmetler" class="sec sec-top">
    <div class="wrap">
        <div class="sec-head">
            <span class="eyebrow acc">Hizmetler</span>
            <h2 class="serif">Almanya eğitim sürecinin her adımında</h2>
            <p>Başvurudan yerleşime kadar tüm süreci uçtan uca yönetiyoruz.</p>
        </div>
        <div class="svc-grid">
            @foreach($services as $i => $s)
                <div class="svc">
                    <div class="svc-n">{{ sprintf('%02d', $i + 1) }}</div>
                    <div>
                        <h3>{{ $s['title'] }}</h3>
                        @if(!empty($s['desc']))<p>{{ $s['desc'] }}</p>@endif
                        @if(!empty($s['items']))
                            <ul class="svc-items">@foreach($s['items'] as $it)<li>{{ $it }}</li>@endforeach</ul>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- PROCESS --}}
<section class="sec sec-top">
    <div class="wrap">
        <div class="sec-head">
            <span class="eyebrow acc">Nasıl Çalışır</span>
            <h2 class="serif">Adım adım, yanınızda</h2>
        </div>
        <div class="steps">
            <div class="step"><span class="sn">01 — Değerlendirme</span><h3>Ücretsiz Görüşme</h3><p>Hedeflerinizi dinler, size en uygun program seçeneklerini çıkarırız.</p></div>
            <div class="step"><span class="sn">02 — Başvuru</span><h3>Belge & Kayıt</h3><p>Üniversite ve dil okulu başvurularınızı uçtan uca yönetiriz.</p></div>
            <div class="step"><span class="sn">03 — Vize</span><h3>Vize & Finans</h3><p>Randevu, bloke hesap ve sigorta işlemlerinde rehberlik ederiz.</p></div>
            <div class="step"><span class="sn">04 — Yerleşim</span><h3>Almanya'da Hayat</h3><p>Konaklama ve Anmeldung ile yeni hayatınıza sorunsuz başlarsınız.</p></div>
        </div>
    </div>
</section>

{{-- ABOUT + STATS --}}
<section id="hakkimizda" class="sec sec-top">
    <div class="wrap about-grid">
        <div>
            <span class="eyebrow acc">Hakkımızda</span>
            <h2 class="serif" style="font-size:clamp(28px,3.6vw,40px);line-height:1.1;letter-spacing:-.6px;color:var(--ink);margin:16px 0 20px;">{{ $siteName }}</h2>
            <div class="about-text">{{ $aboutText }}</div>
        </div>
        @if(!empty($stats))
            <div class="stat-rows">
                @foreach($stats as $st)
                    <div class="stat-row"><span class="sv serif">{{ $st['value'] }}</span><span class="sl">{{ $st['label'] }}</span></div>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- TEAM --}}
@if(!empty($team))
<section id="ekip" class="sec sec-top">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow acc">Ekip</span><h2 class="serif">Danışman kadromuz</h2></div>
        <div class="team-grid">
            @foreach($team as $m)
                <div class="tm">
                    @if(!empty($m['photo']))<img class="tm-ph" src="{{ $m['photo'] }}" alt="{{ $m['name'] }}">@else<div class="tm-ph">{{ Str::upper(Str::substr($m['name'], 0, 1)) }}</div>@endif
                    <h3>{{ $m['name'] }}</h3>
                    @if(!empty($m['title']))<p>{{ $m['title'] }}</p>@endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- TESTIMONIALS --}}
<section class="sec sec-top">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow acc">Öğrenci Yorumları</span><h2 class="serif">Başarı hikayeleriyle büyüyoruz</h2></div>
        <div class="q-grid">
            <div class="q"><div class="qm">"</div><blockquote>Başvurudan vizeye kadar her adımda yanımdalardı. Süreç o kadar düzenliydi ki hiç stres yaşamadım.</blockquote><div class="qw"><b>Elif K.</b> — TU München</div></div>
            <div class="q"><div class="qm">"</div><blockquote>Her aşamayı görebiliyordum, çok sistemli çalışıyorlar. Vize dosyam ilk seferde onaylandı.</blockquote><div class="qw"><b>Burak S.</b> — RWTH Aachen</div></div>
            <div class="q"><div class="qm">"</div><blockquote>Konaklama ve Anmeldung dahil her şeyde destek oldular. Almanya'da yalnız hissetmedim.</blockquote><div class="qw"><b>Zeynep A.</b> — Uni Köln</div></div>
        </div>
    </div>
</section>

{{-- TRUST --}}
@if($showBadge)
<section class="sec sec-top" style="padding:56px 0;">
    <div class="wrap">
        <div class="badge-line">
            {!! $icon('shield') !!}
            <div><div class="bt">{{ config('brand.name', 'MentorDE') }} Yetkili Partneri</div><div class="bs">Resmi partner ağı üzerinden güvenli, şeffaf süreç.</div></div>
        </div>
    </div>
</section>
@endif

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
        @if($showBadge)<span>Powered by <a href="https://panel.mentorde.com" target="_blank" rel="noopener">{{ config('brand.name', 'MentorDE') }}</a></span>@endif
    </div>
</footer>

</body>
</html>
