{{--
    PARTNER TEMPLATE · BULUT
    Ferah cam (glassmorphism) yüzeyler: yarı saydam beyaz kartlar + blur, büyük yumuşak
    blob'lar, ortalanmış hero, yatay hizmet kartları. Kaynak tasarım: "Bulut" (DC).

    Kurallar: JS YOK (S.S.S. <details>), font SADECE lokal, uydurma veri YOK.
    Bölümler modüler: bulut/sections/*.blade.php — sıra + aç/kapa $sections'tan.
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
    --accent-deep:color-mix(in srgb, var(--accent) 80%, #05101f);
    --accent-2:color-mix(in srgb, var(--accent) 45%, #6fd3e8);
    --accent-soft:color-mix(in srgb, var(--accent) 10%, #fff);
    --tint:color-mix(in srgb, var(--accent) 6%, #f4f7fc);
    --glass:rgba(255,255,255,.78);
    --glass-line:rgba(255,255,255,.95);
    --ink:color-mix(in srgb, var(--accent) 14%, #1a2740);
    --ink-2:color-mix(in srgb, var(--accent) 16%, #3b4a66);
    --muted:color-mix(in srgb, var(--accent) 16%, #5f6d88);
    --faint:color-mix(in srgb, var(--accent) 22%, #97a2b8);
    --shadow-s:0 6px 22px color-mix(in srgb, var(--ink) 7%, transparent);
    --shadow-m:0 18px 44px color-mix(in srgb, var(--ink) 12%, transparent);
    --font:"Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --maxw:1300px;
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;scroll-behavior:smooth;overflow-x:hidden;}
body{background:var(--tint);color:var(--ink);font-family:var(--font);font-size:15px;line-height:1.65;-webkit-font-smoothing:antialiased;}
svg{width:1em;height:1em;}
img{max-width:100%;}
a{color:var(--accent-deep);}
.wrap{max-width:var(--maxw);margin:0 auto;padding:0 26px;}
.sec{padding:0 0 64px;}
h1,h2,h3{margin:0;}
.pill-lbl{display:inline-block;font:600 12.5px/1 var(--font);color:var(--accent-deep);background:var(--accent-soft);padding:8px 15px;border-radius:30px;}
.h2{font:800 clamp(26px,3.5vw,36px)/1.2 var(--font);letter-spacing:-1.2px;margin:18px 0 12px;text-wrap:balance;}
.sec-head{max-width:620px;margin:0 auto 40px;text-align:center;}
.sec-head.left{margin-left:0;text-align:left;}
.sec-head p{font-size:16px;line-height:1.68;color:var(--muted);margin:0;}
.blob{position:absolute;border-radius:50%;filter:blur(150px);pointer-events:none;}

/* ─── Butonlar ─── */
.btn{display:inline-flex;align-items:center;gap:9px;text-decoration:none;font:700 15px/1 var(--font);padding:16px 30px;border-radius:14px;transition:transform .18s ease,box-shadow .18s ease;}
.btn-accent{background:var(--accent);color:#fff;box-shadow:0 12px 28px color-mix(in srgb, var(--accent) 28%, transparent);}
.btn-accent:hover{transform:translateY(-2px);box-shadow:0 16px 36px color-mix(in srgb, var(--accent) 36%, transparent);}
.btn-glass{background:var(--glass);border:1px solid var(--glass-line);color:var(--ink);box-shadow:var(--shadow-s);backdrop-filter:blur(14px);}
.btn-glass:hover{transform:translateY(-2px);}

/* ─── Nav ─── */
.nav{position:sticky;top:0;z-index:50;background:rgba(255,255,255,.62);backdrop-filter:blur(16px);border-bottom:1px solid var(--glass-line);}
.nav-in{max-width:var(--maxw);margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:16px 26px;}
.brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--ink);}
.brand-mark{width:34px;height:34px;border-radius:12px;background:linear-gradient(140deg, var(--accent), var(--accent-2));color:#fff;display:flex;align-items:center;justify-content:center;font:800 15px/1 var(--font);flex-shrink:0;}
.brand-name{font:800 19px/1 var(--font);letter-spacing:-.4px;}
.brand img{max-height:38px;width:auto;display:block;}
.nav-links{display:flex;align-items:center;gap:26px;flex-wrap:wrap;}
.nav-links a{text-decoration:none;font:600 14px/1 var(--font);color:var(--muted);}
.nav-links a:hover{color:var(--accent-deep);}
.nav-cta{background:var(--accent);color:#fff !important;padding:12px 20px;border-radius:30px;font-size:13.5px;}
.nav-cta:hover{filter:brightness(.96);}
@media(max-width:760px){.nav-links .nav-link{display:none;}}

/* ─── Hero (ortalanmış, blob'lu) ─── */
.hero{position:relative;overflow:hidden;}
.hero .b1{width:640px;height:640px;background:var(--accent);opacity:.16;top:-320px;left:50%;transform:translateX(-50%);}
.hero .b2{width:420px;height:420px;background:var(--accent-2);opacity:.18;top:120px;right:-160px;filter:blur(140px);}
.hero-in{position:relative;max-width:880px;margin:0 auto;text-align:center;padding:56px 26px 44px;}
.chip{display:inline-flex;align-items:center;gap:9px;font:600 12.5px/1 var(--font);color:var(--accent-deep);background:rgba(255,255,255,.8);border:1px solid var(--glass-line);padding:9px 16px;border-radius:30px;box-shadow:var(--shadow-s);}
.chip i{width:7px;height:7px;border-radius:50%;background:var(--accent);display:inline-block;}
.hero h1{font:800 clamp(34px,5.2vw,54px)/1.1 var(--font);letter-spacing:-1.8px;margin:24px 0 20px;text-wrap:balance;}
.hero p{font-size:17px;line-height:1.7;color:var(--muted);margin:0 auto 30px;max-width:540px;}
.hero-btns{display:flex;gap:13px;justify-content:center;flex-wrap:wrap;}
.hero-glass{position:relative;max-width:1000px;margin:44px auto 0;padding:0 26px;}
.hero-glass-in{background:var(--glass);border:1px solid var(--glass-line);border-radius:26px;padding:26px;backdrop-filter:blur(16px);box-shadow:var(--shadow-m);display:flex;flex-wrap:wrap;gap:22px;}
.hero-glass-in>div{flex:1 1 170px;text-align:center;}
.hero-glass .v{font:800 28px/1 var(--font);letter-spacing:-1px;color:var(--accent-deep);}
.hero-glass .l{font:600 12px/1.35 var(--font);color:var(--muted);margin-top:7px;}
.hero-fig{max-width:1000px;margin:36px auto 0;padding:0 26px;}
.hero-fig div{aspect-ratio:16/9;border-radius:26px;overflow:hidden;border:1px solid var(--glass-line);box-shadow:var(--shadow-m);}
.hero-fig img{width:100%;height:100%;object-fit:cover;display:block;}

/* ─── Cam kart ızgarası ─── */
.grid{display:flex;flex-wrap:wrap;justify-content:center;gap:var(--gap,16px);--cols:3;--min:300px;}
.grid>*{flex:0 1 calc((100% - (var(--cols) - 1) * var(--gap,16px)) / var(--cols));min-width:min(var(--min),100%);}
.glass{background:var(--glass);backdrop-filter:blur(14px);border:1px solid var(--glass-line);border-radius:22px;padding:26px;box-shadow:var(--shadow-s);transition:transform .2s ease,box-shadow .2s ease;}
.glass:hover{transform:translateY(-4px);box-shadow:var(--shadow-m);}
/* Yatay hizmet kartı: ikon solda, metin sağda */
.svc{display:flex;gap:18px;}
.svc-ic{width:52px;height:52px;flex-shrink:0;border-radius:17px;background:var(--accent-soft);color:var(--accent-deep);display:flex;align-items:center;justify-content:center;font-size:26px;}
.svc h3{font:700 18px/1.3 var(--font);margin:0 0 8px;}
.svc p{font-size:14px;line-height:1.62;color:var(--muted);margin:0 0 14px;}
.tags{display:flex;gap:8px;flex-wrap:wrap;}
.tags span{font:600 12px/1 var(--font);color:var(--accent-deep);background:var(--accent-soft);padding:7px 11px;border-radius:20px;}

/* ─── Süreç ─── */
.step .n{width:48px;height:48px;border-radius:16px;background:linear-gradient(140deg, var(--accent), var(--accent-2));color:#fff;display:flex;align-items:center;justify-content:center;font:800 17px/1 var(--font);margin-bottom:16px;box-shadow:0 10px 24px color-mix(in srgb, var(--accent) 26%, transparent);}
.step h3{font:700 17px/1.25 var(--font);margin:0 0 8px;}
.step p{font-size:14px;line-height:1.62;color:var(--muted);margin:0;}

/* ─── İstatistik / üniversite ─── */
.stat-glass{display:flex;flex-wrap:wrap;gap:20px;}
.stat-glass>div{flex:1 1 190px;text-align:center;}
.stat-glass .v{font:800 clamp(28px,3.4vw,38px)/1 var(--font);letter-spacing:-1.2px;color:var(--accent-deep);}
.stat-glass .l{font:600 12.5px/1.4 var(--font);color:var(--muted);margin-top:8px;}
.unis{display:flex;align-items:center;gap:30px;flex-wrap:wrap;justify-content:center;}
.unis-lbl{font:600 12px/1.3 var(--font);color:var(--faint);}
.unis .u{font:700 17px/1 var(--font);color:color-mix(in srgb, var(--accent) 42%, var(--faint));}

/* ─── Yorum / ekip ─── */
.quote p{font-size:15px;line-height:1.7;color:var(--ink-2);margin:0 0 20px;}
.who{display:flex;align-items:center;gap:12px;}
.avatar{width:46px;height:46px;border-radius:15px;background:linear-gradient(140deg, var(--accent), var(--accent-2));color:#fff;display:flex;align-items:center;justify-content:center;font:800 16px/1 var(--font);flex-shrink:0;overflow:hidden;}
.avatar img{width:100%;height:100%;object-fit:cover;}
.who b{display:block;font:700 14.5px/1.1 var(--font);}
.who span{font:500 12.5px/1.2 var(--font);color:var(--faint);}
.member{display:flex;align-items:center;gap:14px;}
.trust{background:linear-gradient(140deg, var(--accent), var(--accent-2));border-radius:22px;padding:24px;display:flex;align-items:center;gap:14px;box-shadow:var(--shadow-m);}
.trust-ic{width:52px;height:52px;border-radius:16px;background:rgba(255,255,255,.22);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:25px;}
.trust b{font:700 15px/1.25 var(--font);color:#fff;}
.trust p{font-size:12.5px;line-height:1.5;color:rgba(255,255,255,.85);margin:4px 0 0;}

/* ─── Paketler ─── */
.pkg{display:flex;flex-direction:column;padding:30px 28px;}
.pkg-tag{font:600 11.5px/1 var(--font);color:var(--accent-deep);background:var(--accent-soft);padding:7px 13px;border-radius:20px;align-self:flex-start;}
.pkg h3{font:800 22px/1.2 var(--font);margin:16px 0 8px;}
.pkg p{font-size:14px;line-height:1.6;color:var(--muted);margin:0 0 18px;}
.ticks{list-style:none;padding:18px 0 0;margin:0 0 22px;border-top:1px solid color-mix(in srgb, var(--accent) 12%, #fff);display:flex;flex-direction:column;gap:10px;}
.ticks li{display:flex;align-items:flex-start;gap:10px;font-size:13.5px;line-height:1.5;color:var(--ink-2);}
.ticks svg{color:var(--accent);flex-shrink:0;margin-top:2px;font-size:16px;}
.pkg-btn{margin-top:auto;text-decoration:none;text-align:center;background:var(--accent-soft);color:var(--accent-deep);font:700 14.5px/1 var(--font);padding:15px;border-radius:14px;transition:filter .15s ease;}
.pkg-btn:hover{filter:brightness(.96);}
.pkg-hi{background:linear-gradient(150deg, var(--accent), var(--accent-2));border-color:transparent;}
.pkg-hi h3, .pkg-hi .ticks li{color:#fff;}
.pkg-hi p{color:rgba(255,255,255,.85);}
.pkg-hi .pkg-tag{background:rgba(255,255,255,.22);color:#fff;}
.pkg-hi .ticks{border-color:rgba(255,255,255,.24);}
.pkg-hi .ticks svg{color:#fff;}
.pkg-hi .pkg-btn{background:#fff;color:var(--accent-deep);}

/* ─── S.S.S. (JS'siz) ─── */
.faq{max-width:900px;margin:0 auto;}
.faq details{background:var(--glass);backdrop-filter:blur(14px);border:1px solid var(--glass-line);border-radius:18px;box-shadow:var(--shadow-s);margin-bottom:12px;overflow:hidden;}
.faq summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:21px 24px;font:700 16px/1.4 var(--font);transition:background .15s ease;}
.faq summary::-webkit-details-marker{display:none;}
.faq summary:hover{background:rgba(255,255,255,.6);}
.faq summary .ico{width:28px;height:28px;flex-shrink:0;border-radius:10px;background:var(--accent-soft);color:var(--accent-deep);display:flex;align-items:center;justify-content:center;font:700 18px/1 var(--font);transition:transform .15s ease;}
.faq details[open] summary .ico{transform:rotate(45deg);}
.faq p{font-size:14.5px;line-height:1.7;color:var(--muted);margin:0;padding:0 24px 22px;}

/* ─── CTA ─── */
.cta{position:relative;overflow:hidden;background:linear-gradient(150deg, var(--accent), var(--accent-2));border-radius:30px;padding:52px 46px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:44px;align-items:center;box-shadow:var(--shadow-m);}
.cta h2{font:800 clamp(26px,3.4vw,34px)/1.2 var(--font);letter-spacing:-1px;margin:0 0 14px;color:#fff;}
.cta-l>p{font-size:15.5px;line-height:1.7;color:rgba(255,255,255,.86);margin:0 0 22px;}
.cta-ticks{display:flex;flex-direction:column;gap:12px;}
.cta-ticks span{display:inline-flex;align-items:center;gap:11px;font:600 14px/1.4 var(--font);color:#fff;}
.cta-ticks svg{color:#fff;opacity:.85;flex-shrink:0;font-size:18px;}
.cta-r{background:rgba(255,255,255,.92);backdrop-filter:blur(14px);border-radius:22px;padding:30px;}
.cta-r h3{font:800 19px/1.25 var(--font);margin:0 0 8px;}
.cta-r>p{font-size:14px;line-height:1.6;color:var(--muted);margin:0 0 18px;}
.cta-r .btn{width:100%;justify-content:center;}
.contact{list-style:none;padding:18px 0 0;margin:18px 0 0;border-top:1px solid color-mix(in srgb, var(--accent) 12%, #fff);display:flex;flex-direction:column;gap:11px;}
.contact li{display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--muted);}
.contact svg{color:var(--accent);flex-shrink:0;font-size:17px;}
.contact a{color:var(--ink);text-decoration:none;font-weight:600;}
.contact a:hover{color:var(--accent-deep);}

/* ─── Footer ─── */
.foot-in{max-width:var(--maxw);margin:0 auto;padding:34px 26px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;}
.foot-txt{font:500 13px/1.5 var(--font);color:var(--faint);}
.foot-dim{font:500 12.5px/1.5 var(--font);color:color-mix(in srgb, var(--faint) 78%, #fff);}
.foot-soc a{color:var(--faint);display:inline-flex;font-size:19px;transition:color .15s ease,transform .15s ease;}
.foot-soc a:hover{color:var(--accent-deep);transform:translateY(-2px);}

/* ─── WhatsApp ─── */
.wa-float{position:fixed;right:24px;bottom:24px;z-index:60;text-decoration:none;display:inline-flex;align-items:center;gap:10px;background:linear-gradient(140deg, var(--accent), var(--accent-2));color:#fff;font:700 14px/1 var(--font);padding:15px 19px;border-radius:30px;box-shadow:0 14px 30px color-mix(in srgb, var(--accent) 34%, transparent);transition:transform .18s ease;}
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
            @foreach($navLinks as $nl)
                <a href="{{ $nl['href'] }}" class="nav-link">{{ $nl['label'] }}</a>
            @endforeach
            <a href="{{ $applyUrl }}" class="nav-cta" data-track="cta_clicked" data-ph-cta-name="nav_apply" data-ph-location="partner_bulut_nav">Ücretsiz Başvur</a>
        </div>
    </div>
</div>

{{-- ═══ HERO ═══ --}}
<div class="hero">
    <div class="blob b1"></div>
    <div class="blob b2"></div>
    <div class="hero-in">
        <span class="chip"><i></i>Ücretsiz ön değerlendirme</span>
        <h1>{{ $heroTitle }}</h1>
        <p>{{ $heroSubtitle }}</p>
        <div class="hero-btns">
            <a href="{{ $applyUrl }}" class="btn btn-accent" data-track="cta_clicked" data-ph-cta-name="hero_apply" data-ph-location="partner_bulut_hero">Ücretsiz danışmanlık {!! $icon('arrow') !!}</a>
            @if(!empty($navLinks))<a href="{{ $navLinks[0]['href'] }}" class="btn btn-glass">{{ $navLinks[0]['label'] }}</a>@endif
        </div>
    </div>

    {{-- Cam istatistik şeridi — SADECE partnerin girdiği sayılar --}}
    @if(!empty($heroTrust))
        <div class="hero-glass">
            <div class="hero-glass-in">
                @foreach($heroTrust as $ht)
                    <div><div class="v">{{ $ht['value'] }}</div><div class="l">{{ $ht['label'] }}</div></div>
                @endforeach
            </div>
        </div>
    @endif

    @if($heroImg)
        <div class="hero-fig"><div><img src="{{ $heroImg }}" alt="{{ $siteName }}"></div></div>
    @endif
</div>

<div style="height:48px;"></div>

{{-- ═══ SIRALANABİLİR BÖLÜMLER ═══ --}}
@foreach($sections as $sectionKey)
    @includeIf('public.partner-templates.bulut.sections.' . $sectionKey)
@endforeach

{{-- ═══ BAŞVURU / İLETİŞİM ═══ --}}
<div id="basvuru" class="wrap" style="padding:0 26px 72px;">
    <div class="cta">
        <div class="cta-l">
            <h2>Yolculuğunuza bugün başlayın</h2>
            <p>Başvurun, ekibimiz en kısa sürede sizinle iletişime geçsin. Hiçbir yükümlülük altına girmezsiniz.</p>
            <div class="cta-ticks">
                <span>{!! $icon('check') !!}Uçtan uca, tek elden yönetim</span>
                <span>{!! $icon('check') !!}Her adım panelden şeffaf takip</span>
            </div>
        </div>
        <div class="cta-r">
            <h3>Ücretsiz ön görüşme</h3>
            <p>Formu doldurun; hedeflerinizi dinleyip size özel bir yol haritası çıkaralım.</p>
            <a href="{{ $applyUrl }}" class="btn btn-accent" data-track="cta_clicked" data-ph-cta-name="footer_apply" data-ph-location="partner_bulut_cta">Başvuru formunu aç {!! $icon('arrow') !!}</a>
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
                <span class="brand-mark" style="width:30px;height:30px;border-radius:10px;font-size:13px;">{{ mb_strtoupper(mb_substr($siteName, 0, 1)) }}</span>
                <span class="brand-name" style="font-size:16px;">{{ $siteName }}</span>
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
    <a href="{{ $waUrl }}" class="wa-float" target="_blank" rel="noopener" data-track="cta_clicked" data-ph-cta-name="whatsapp_float" data-ph-location="partner_bulut_float">
        {!! $icon('wa') !!} WhatsApp
    </a>
@endif

</body>
</html>
