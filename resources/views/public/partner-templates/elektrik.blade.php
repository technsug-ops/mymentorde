{{--
    PARTNER TEMPLATE · ELEKTRİK
    Cesur indigo, sıkı tipografi (Space Grotesk), koyu lacivert paneller, keskin köşeler.
    Kaynak tasarım: "Elektrik" (DC).

    Kurallar: JS YOK (S.S.S. <details>), font SADECE lokal, uydurma veri YOK.
    Bölümler modüler: elektrik/sections/*.blade.php — sıra + aç/kapa $sections'tan.
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
    // Hero paneli: partnerin ilk istatistiği büyük, kalanlar altında. Veri yoksa panel basılmaz.
    $heroLead = $stats[0] ?? null;
    $heroRest = array_slice($stats, 1, 3);

    /** Satır başına kart sayısı — eşit sıralar (6 → 3+3); eksik son sıra ortalanır. */
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
    --accent-deep:color-mix(in srgb, var(--accent) 82%, #05061a);
    --accent-pale:color-mix(in srgb, var(--accent) 10%, #fff);
    --accent-lite:color-mix(in srgb, var(--accent) 55%, #fff);
    --tint:color-mix(in srgb, var(--accent) 4%, #fff);
    --line:color-mix(in srgb, var(--accent) 11%, #fff);
    --line-2:color-mix(in srgb, var(--accent) 22%, #fff);
    --navy:color-mix(in srgb, var(--accent) 22%, #0d0e1e);
    --navy-2:color-mix(in srgb, var(--accent) 26%, #171930);
    --ink:color-mix(in srgb, var(--accent) 12%, #14152b);
    --ink-2:color-mix(in srgb, var(--accent) 14%, #343a52);
    --muted:color-mix(in srgb, var(--accent) 12%, #5b5f78);
    --faint:color-mix(in srgb, var(--accent) 16%, #969bb2);
    --on-navy:color-mix(in srgb, var(--accent) 24%, #b9bfe0);
    --display:"Space Grotesk","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --body:"IBM Plex Sans","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --mono:"IBM Plex Mono",ui-monospace,SFMono-Regular,monospace;
    --maxw:1340px;
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;scroll-behavior:smooth;}
body{background:#fff;color:var(--ink);font-family:var(--body);font-size:15px;line-height:1.6;-webkit-font-smoothing:antialiased;}
svg{width:1em;height:1em;}
img{max-width:100%;}
a{color:var(--accent-deep);}
.wrap{max-width:var(--maxw);margin:0 auto;padding:0 26px;}
.sec{padding:10px 0 64px;}
.lbl{font:600 12px/1 var(--mono);letter-spacing:.06em;color:var(--accent-deep);text-transform:uppercase;}
h1,h2,h3{margin:0;font-family:var(--display);}
.h2{font:700 clamp(25px,3.3vw,34px)/1.12 var(--display);letter-spacing:-1.2px;margin:12px 0 0;}
.sec-head{max-width:620px;margin-bottom:34px;}
.sec-head p{font-size:15px;color:var(--muted);margin:12px 0 0;}
.sec-head-row{display:flex;align-items:baseline;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:32px;}
.sec-note{font:600 12px/1.4 var(--mono);color:var(--faint);max-width:320px;}

/* ─── Butonlar ─── */
.btn{display:inline-flex;align-items:center;gap:9px;text-decoration:none;font:700 15px/1 var(--body);padding:16px 28px;border-radius:12px;transition:transform .18s ease,box-shadow .18s ease,border-color .15s ease,color .15s ease;}
.btn-accent{background:var(--accent);color:#fff;box-shadow:0 12px 28px color-mix(in srgb, var(--accent) 30%, transparent);}
.btn-accent:hover{transform:translateY(-2px);box-shadow:0 16px 36px color-mix(in srgb, var(--accent) 40%, transparent);}
.btn-line{border:1.5px solid var(--line-2);color:var(--ink);padding:15px 24px;}
.btn-line:hover{border-color:var(--accent);color:var(--accent-deep);}

/* ─── Nav ─── */
.nav{position:sticky;top:0;z-index:50;background:rgba(255,255,255,.9);backdrop-filter:blur(12px);border-bottom:1px solid var(--line);}
.nav-in{max-width:var(--maxw);margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:16px 26px;}
.brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--ink);}
.brand-name{font:700 22px/1 var(--display);letter-spacing:-.5px;}
.brand-name i{color:var(--accent);font-style:normal;}
.brand img{max-height:40px;width:auto;display:block;}
.nav-links{display:flex;align-items:center;gap:26px;flex-wrap:wrap;}
.nav-links a{text-decoration:none;font:500 14px/1 var(--body);color:var(--muted);}
.nav-links a:hover{color:var(--accent-deep);}
.nav-cta{background:var(--accent);color:#fff !important;font-weight:700;font-size:13px;padding:12px 20px;border-radius:10px;}
.nav-cta:hover{filter:brightness(.94);}
@media(max-width:760px){.nav-links .nav-link{display:none;}}

/* ─── Hero ─── */
.hero{max-width:var(--maxw);margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(330px,1fr));gap:40px;align-items:center;padding:64px 26px;}
.pill{display:inline-block;font:600 12px/1 var(--mono);letter-spacing:.06em;color:var(--accent-deep);background:var(--accent-pale);padding:8px 14px;border-radius:8px;text-transform:uppercase;}
.hero h1{font:700 clamp(36px,6vw,62px)/1.02 var(--display);letter-spacing:-2.5px;margin:22px 0 18px;text-wrap:balance;}
.hero p{font-size:18px;line-height:1.55;color:var(--muted);margin:0 0 30px;max-width:480px;}
.hero-btns{display:flex;gap:12px;align-items:center;flex-wrap:wrap;}
/* Koyu istatistik paneli — SADECE partnerin girdiği sayılar */
.panel{background:var(--navy);border-radius:20px;padding:36px;color:#fff;position:relative;overflow:hidden;}
.panel-blob{position:absolute;width:220px;height:220px;border-radius:50%;background:var(--accent);filter:blur(60px);opacity:.5;top:-70px;right:-40px;pointer-events:none;}
.panel-in{position:relative;}
.panel .big{font:700 clamp(46px,6vw,72px)/1 var(--display);letter-spacing:-3px;color:var(--accent-lite);}
.panel .big-l{font:600 15px/1.3 var(--body);color:var(--on-navy);margin-top:8px;}
.panel hr{border:0;height:1px;background:rgba(255,255,255,.14);margin:26px 0;}
.panel-row{display:flex;gap:28px;flex-wrap:wrap;}
.panel-row .v{font:700 28px/1 var(--display);color:#fff;}
.panel-row .l{font:500 12px/1.2 var(--mono);color:color-mix(in srgb, var(--on-navy) 80%, var(--navy));margin-top:5px;}
.hero-fig{border-radius:20px;overflow:hidden;aspect-ratio:4/3;}
.hero-fig img{width:100%;height:100%;object-fit:cover;display:block;}

/* ─── Üniversite satırı ─── */
.unis{border-top:1px solid var(--line);border-bottom:1px solid var(--line);padding:22px 0;display:flex;align-items:center;gap:32px;flex-wrap:wrap;}
.unis-lbl{font:600 11px/1.3 var(--mono);color:var(--faint);text-transform:uppercase;letter-spacing:.06em;}
.unis .u{font:600 17px/1 var(--display);color:color-mix(in srgb, var(--accent) 28%, var(--faint));}

/* ─── Izgara + kartlar ─── */
.grid{display:flex;flex-wrap:wrap;justify-content:center;gap:var(--gap,16px);--cols:3;--min:250px;}
.grid>*{flex:0 1 calc((100% - (var(--cols) - 1) * var(--gap,16px)) / var(--cols));min-width:min(var(--min),100%);}
.card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:28px 24px;display:flex;flex-direction:column;transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease;}
.card:hover{transform:translateY(-4px);box-shadow:0 16px 36px color-mix(in srgb, var(--navy) 9%, transparent);border-color:var(--line-2);}
.card-ic{width:52px;height:52px;border-radius:13px;background:var(--navy);color:var(--accent-lite);display:flex;align-items:center;justify-content:center;margin-bottom:16px;font-size:26px;}
.card h3{font:700 18px/1.2 var(--display);margin:0 0 8px;}
.card p{font-size:14px;line-height:1.55;color:var(--muted);margin:0 0 16px;}
.ticks{list-style:none;padding:16px 0 0;margin:auto 0 0;border-top:1px solid var(--line);display:flex;flex-direction:column;gap:9px;}
.ticks li{display:flex;align-items:flex-start;gap:9px;font-size:13px;line-height:1.4;color:var(--ink-2);}
.ticks svg{color:var(--accent);flex-shrink:0;margin-top:1px;font-size:16px;}

/* ─── Süreç (hairline + koyu üst çizgi) ─── */
.proc{display:flex;flex-wrap:wrap;gap:1px;background:var(--line);border-top:2px solid var(--navy);--cols:4;--min:200px;}
.proc>*{flex:0 1 calc((100% - (var(--cols) - 1) * 1px) / var(--cols));min-width:min(var(--min),100%);background:#fff;padding:26px 22px 20px;}
.proc .n{font:700 40px/1 var(--display);color:var(--accent);letter-spacing:-1.5px;}
.proc h3{font:700 16px/1.2 var(--display);margin:14px 0 6px;}
.proc p{font-size:13px;line-height:1.5;color:var(--muted);margin:0;}

/* ─── İstatistik bandı (tam genişlik accent) ─── */
.band{background:var(--accent);}
.band-in{max-width:var(--maxw);margin:0 auto;padding:48px 26px;display:flex;flex-wrap:wrap;gap:20px;}
.band-in>div{flex:1 1 200px;color:#fff;}
.band .v{font:700 clamp(32px,4.2vw,44px)/1 var(--display);letter-spacing:-1.5px;}
.band .l{font:500 12px/1.3 var(--body);color:color-mix(in srgb, #fff 82%, var(--accent));text-transform:uppercase;letter-spacing:.05em;margin-top:8px;}

/* ─── Yorumlar / ekip ─── */
.tone{background:var(--tint);border-top:1px solid var(--line);border-bottom:1px solid var(--line);}
.quote{background:var(--tint);border:1px solid var(--line);border-radius:14px;padding:26px;transition:transform .2s ease,border-color .2s ease;}
.quote:hover{transform:translateY(-3px);border-color:var(--line-2);}
.quote p{font-size:14.5px;line-height:1.6;color:var(--ink-2);margin:0 0 20px;}
.who{display:flex;align-items:center;gap:12px;}
.avatar{width:42px;height:42px;border-radius:11px;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font:700 16px/1 var(--display);flex-shrink:0;overflow:hidden;}
.avatar img{width:100%;height:100%;object-fit:cover;}
.who b{display:block;font:700 14px/1.1 var(--display);}
.who span{font:500 12px/1.2 var(--mono);color:var(--faint);}
.member{background:#fff;border:1px solid var(--line);border-radius:12px;padding:22px;display:flex;align-items:center;gap:14px;}
.member .avatar{width:50px;height:50px;border-radius:12px;font-size:18px;}
.trust{background:var(--navy);border-radius:12px;padding:22px;display:flex;align-items:center;gap:14px;}
.trust-ic{width:50px;height:50px;border-radius:12px;background:color-mix(in srgb, var(--accent) 22%, transparent);color:var(--accent-lite);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:25px;}
.trust b{font:700 15px/1.2 var(--display);color:#fff;}
.trust p{font-size:12.5px;line-height:1.5;color:var(--on-navy);margin:4px 0 0;}

/* ─── Paketler ─── */
.pkg{background:#fff;border:1px solid var(--line);border-radius:14px;padding:28px 26px;display:flex;flex-direction:column;transition:transform .2s ease,box-shadow .2s ease;}
.pkg:hover{transform:translateY(-4px);box-shadow:0 16px 36px color-mix(in srgb, var(--navy) 10%, transparent);}
.pkg-tag{font:600 10.5px/1 var(--mono);letter-spacing:.06em;text-transform:uppercase;color:var(--accent-deep);background:var(--accent-pale);padding:6px 10px;border-radius:6px;align-self:flex-start;}
.pkg h3{font:700 22px/1.2 var(--display);margin:16px 0 8px;}
.pkg p{font-size:13.5px;line-height:1.55;color:var(--muted);margin:0 0 18px;}
.pkg .ticks{padding:18px 0 0;margin:0 0 20px;}
.pkg-btn{margin-top:auto;text-decoration:none;text-align:center;background:var(--accent-pale);color:var(--accent-deep);font:700 14px/1 var(--body);padding:15px;border-radius:10px;transition:filter .15s ease;}
.pkg-btn:hover{filter:brightness(.95);}
.pkg-hi{background:var(--navy);border-color:var(--navy);}
.pkg-hi h3{color:#fff;}
.pkg-hi p{color:var(--on-navy);}
.pkg-hi .pkg-tag{background:color-mix(in srgb, var(--accent) 30%, transparent);color:var(--accent-lite);}
.pkg-hi .ticks{border-color:rgba(255,255,255,.14);}
.pkg-hi .ticks li{color:#eceefb;}
.pkg-hi .ticks svg{color:var(--accent-lite);}
.pkg-hi .pkg-btn{background:var(--accent);color:#fff;}

/* ─── S.S.S. (JS'siz) ─── */
.faq{max-width:920px;}
.faq details{background:#fff;border:1px solid var(--line);border-radius:12px;overflow:hidden;margin-bottom:12px;}
.faq summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:20px 22px;font:600 16px/1.35 var(--body);transition:background .15s ease;}
.faq summary::-webkit-details-marker{display:none;}
.faq summary:hover{background:var(--tint);}
.faq summary .ico{width:26px;height:26px;flex-shrink:0;border-radius:7px;background:var(--accent-pale);color:var(--accent-deep);display:flex;align-items:center;justify-content:center;font:600 18px/1 var(--body);transition:transform .15s ease;}
.faq details[open] summary .ico{transform:rotate(45deg);}
.faq p{font-size:14.5px;line-height:1.62;color:var(--muted);margin:0;padding:0 22px 20px;}

/* ─── CTA ─── */
.cta{background:var(--navy);border-radius:20px;padding:52px 48px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:44px;align-items:center;position:relative;overflow:hidden;}
.cta-blob{position:absolute;width:300px;height:300px;border-radius:50%;background:var(--accent);filter:blur(60px);opacity:.55;bottom:-100px;left:-40px;pointer-events:none;}
.cta-l{position:relative;}
.cta h2{font:700 clamp(26px,3.5vw,36px)/1.1 var(--display);letter-spacing:-1.2px;margin:0 0 14px;color:#fff;}
.cta-l>p{font-size:16px;line-height:1.6;color:var(--on-navy);margin:0 0 22px;}
.cta-chips{display:flex;gap:10px;flex-wrap:wrap;}
.cta-chips span{font:600 12px/1 var(--mono);color:var(--accent-lite);background:color-mix(in srgb, var(--accent) 22%, transparent);padding:9px 12px;border-radius:8px;}
.cta-r{position:relative;background:var(--navy-2);border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:28px;}
.cta-r h3{font:700 19px/1.25 var(--display);color:#fff;margin:0 0 8px;}
.cta-r>p{font-size:13.5px;line-height:1.55;color:var(--on-navy);margin:0 0 18px;}
.cta-r .btn{width:100%;justify-content:center;}
.contact{list-style:none;padding:18px 0 0;margin:18px 0 0;border-top:1px solid rgba(255,255,255,.1);display:flex;flex-direction:column;gap:11px;}
.contact li{display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--on-navy);}
.contact svg{color:var(--accent-lite);flex-shrink:0;font-size:17px;}
.contact a{color:#fff;text-decoration:none;font-weight:600;}
.contact a:hover{color:var(--accent-lite);}

/* ─── Footer ─── */
.foot{border-top:1px solid var(--line);}
.foot-in{max-width:var(--maxw);margin:0 auto;padding:32px 26px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;}
.foot-txt{font:500 13px/1.5 var(--mono);color:var(--faint);}
.foot-dim{font:500 12px/1.5 var(--mono);color:color-mix(in srgb, var(--faint) 78%, #fff);}
.foot-soc a{color:var(--faint);display:inline-flex;font-size:19px;transition:color .15s ease,transform .15s ease;}
.foot-soc a:hover{color:var(--accent);transform:translateY(-2px);}

/* ─── WhatsApp ─── */
.wa-float{position:fixed;right:24px;bottom:24px;z-index:60;text-decoration:none;display:inline-flex;align-items:center;gap:10px;background:#1faa55;color:#fff;font:700 14px/1 var(--body);padding:14px 18px;border-radius:12px;box-shadow:0 14px 30px rgba(31,170,85,.36);transition:transform .18s ease;}
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
                <span class="brand-name">{{ $siteName }}<i>.</i></span>
            @endif
        </a>
        <div class="nav-links">
            @foreach($navLinks as $nl)
                <a href="{{ $nl['href'] }}" class="nav-link">{{ $nl['label'] }}</a>
            @endforeach
            <a href="{{ $applyUrl }}" class="nav-cta" data-track="cta_clicked" data-ph-cta-name="nav_apply" data-ph-location="partner_elektrik_nav">Başvur</a>
        </div>
    </div>
</div>

{{-- ═══ HERO ═══ --}}
<div class="hero">
    <div>
        <span class="pill">Almanya · Eğitim Danışmanlığı</span>
        <h1>{{ $heroTitle }}</h1>
        <p>{{ $heroSubtitle }}</p>
        <div class="hero-btns">
            <a href="{{ $applyUrl }}" class="btn btn-accent" data-track="cta_clicked" data-ph-cta-name="hero_apply" data-ph-location="partner_elektrik_hero">Ücretsiz başla {!! $icon('arrow') !!}</a>
            @if(!empty($navLinks))<a href="{{ $navLinks[0]['href'] }}" class="btn btn-line">{{ $navLinks[0]['label'] }}</a>@endif
        </div>
    </div>

    {{-- Sağ blok: partner istatistiği varsa koyu panel, yoksa hero görseli, ikisi de yoksa boş --}}
    @if($heroLead)
        <div class="panel">
            <div class="panel-blob"></div>
            <div class="panel-in">
                <div class="big">{{ $heroLead['value'] }}</div>
                <div class="big-l">{{ $heroLead['label'] }}</div>
                @if(!empty($heroRest))
                    <hr>
                    <div class="panel-row">
                        @foreach($heroRest as $hr)
                            <div><div class="v">{{ $hr['value'] }}</div><div class="l">{{ $hr['label'] }}</div></div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @elseif($heroImg)
        <figure class="hero-fig" style="margin:0;"><img src="{{ $heroImg }}" alt="{{ $siteName }}"></figure>
    @endif
</div>

{{-- ═══ SIRALANABİLİR BÖLÜMLER ═══ --}}
@foreach($sections as $sectionKey)
    @includeIf('public.partner-templates.elektrik.sections.' . $sectionKey)
@endforeach

{{-- ═══ BAŞVURU / İLETİŞİM ═══ --}}
<div id="basvuru" class="wrap" style="padding:8px 26px 72px;">
    <div class="cta">
        <div class="cta-blob"></div>
        <div class="cta-l">
            <h2>Bugün başlayın. İlk görüşme ücretsiz.</h2>
            <p>Başvurun, ekibimiz en kısa sürede sizinle iletişime geçsin.</p>
            <div class="cta-chips">
                <span>TEK ELDEN YÖNETİM</span>
                <span>PANELDEN ŞEFFAF TAKİP</span>
            </div>
        </div>
        <div class="cta-r">
            <h3>Ücretsiz ön görüşme</h3>
            <p>Formu doldurun; hedeflerinizi dinleyip size özel bir yol haritası çıkaralım.</p>
            <a href="{{ $applyUrl }}" class="btn btn-accent" data-track="cta_clicked" data-ph-cta-name="footer_apply" data-ph-location="partner_elektrik_cta">Başvuru formunu aç {!! $icon('arrow') !!}</a>
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
                <span class="brand-name" style="font-size:18px;">{{ $siteName }}<i>.</i></span>
            @endif
        </div>
        @if($address)<span class="foot-txt">{{ $address }}</span>@endif
        <span class="foot-dim">
            © {{ now()->year }} {{ $siteName }}
            @if($showBadge)
                · @include('partials.vendor-credit')
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
    <a href="{{ $waUrl }}" class="wa-float" target="_blank" rel="noopener" data-track="cta_clicked" data-ph-cta-name="whatsapp_float" data-ph-location="partner_elektrik_float">
        {!! $icon('wa') !!} WhatsApp
    </a>
@endif

</body>
</html>
