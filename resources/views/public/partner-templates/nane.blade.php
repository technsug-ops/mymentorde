{{--
    PARTNER TEMPLATE · NANE
    Minimal ve ferah: ortalanmış hero, gölge yok, ince çizgi (hairline) ızgaralar, bol boşluk.
    Kaynak tasarım: "Nane" (DC).

    Kurallar: JS YOK (S.S.S. <details>), font SADECE lokal, uydurma veri YOK.
    Bölümler modüler: nane/sections/*.blade.php — sıra + aç/kapa $sections'tan gelir.
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

    /** Satır başına kart sayısı — sıralar eşit dolsun (6 → 3+3), eksik son sıra ortalanır. */
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
    --accent-deep:color-mix(in srgb, var(--accent) 74%, #06120c);
    --soft:color-mix(in srgb, var(--accent) 13%, #fff);
    --tint:color-mix(in srgb, var(--accent) 6%, #fff);
    --line:color-mix(in srgb, var(--accent) 22%, #fff);
    --line-2:color-mix(in srgb, var(--accent) 34%, #fff);
    --ink:color-mix(in srgb, var(--accent) 20%, #14231b);
    --ink-mid:color-mix(in srgb, var(--accent) 26%, #3d4c44);
    --muted:color-mix(in srgb, var(--accent) 22%, #64756c);
    --faint:color-mix(in srgb, var(--accent) 34%, #93a49b);
    --on-ink:color-mix(in srgb, var(--accent) 10%, #f4faf7);
    --on-ink-soft:color-mix(in srgb, var(--accent) 30%, #b7c8bf);
    --display:"Poppins","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --body:"Public Sans","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --mono:"IBM Plex Mono",ui-monospace,SFMono-Regular,monospace;
    --maxw:1240px;
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;scroll-behavior:smooth;}
body{background:var(--tint);color:var(--ink);font-family:var(--body);font-size:15px;line-height:1.6;-webkit-font-smoothing:antialiased;}
svg{width:1em;height:1em;}
img{max-width:100%;}
a{color:var(--accent-deep);}
.wrap{max-width:var(--maxw);margin:0 auto;padding:0 26px;}
.sec{padding:62px 0;}
.sec-white{background:#fff;}
.lbl{font:600 12px/1 var(--mono);letter-spacing:.14em;color:var(--accent-deep);text-transform:uppercase;}
h1,h2,h3{font-family:var(--display);margin:0;font-weight:600;}
.h2{font:600 clamp(25px,3.3vw,34px)/1.14 var(--display);letter-spacing:-1.2px;margin:14px 0 0;text-wrap:balance;}
.sec-head{text-align:center;max-width:560px;margin:0 auto 42px;}
.sec-head p{font-size:15px;color:var(--muted);margin:12px 0 0;}

/* ─── Butonlar ─── */
.btn{display:inline-flex;align-items:center;gap:9px;text-decoration:none;font:700 15px/1 var(--body);padding:16px 30px;border-radius:12px;transition:background .15s ease,transform .15s ease,border-color .15s ease;}
.btn-dark{background:var(--ink);color:var(--on-ink);}
.btn-dark:hover{background:var(--ink-mid);transform:translateY(-2px);}
.btn-line{color:var(--ink);border:1.5px solid var(--line-2);padding:15px 26px;}
.btn-line:hover{border-color:var(--accent);}
.btn-accent{background:var(--accent);color:var(--ink);}
.btn-accent:hover{filter:brightness(.95);transform:translateY(-1px);}

/* ─── Nav ─── */
.nav{position:sticky;top:0;z-index:50;background:color-mix(in srgb, var(--tint) 90%, transparent);backdrop-filter:blur(12px);border-bottom:1px solid var(--line);}
.nav-in{max-width:var(--maxw);margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:18px 26px;}
.brand{display:flex;align-items:center;gap:9px;text-decoration:none;color:var(--ink);}
.brand-dot{width:10px;height:10px;border-radius:50%;background:var(--accent);flex-shrink:0;}
.brand-name{font:700 18px/1 var(--display);}
.brand img{max-height:38px;width:auto;display:block;}
.nav-links{display:flex;align-items:center;gap:26px;flex-wrap:wrap;}
.nav-links a{text-decoration:none;font:600 14px/1 var(--body);color:var(--muted);}
.nav-links a:hover{color:var(--accent-deep);}
.nav-cta{color:var(--ink) !important;font-weight:700;border-bottom:2px solid var(--accent);padding-bottom:2px;}
@media(max-width:760px){.nav-links .nav-link{display:none;}}

/* ─── Hero (ortalanmış) ─── */
.hero{max-width:820px;margin:0 auto;text-align:center;padding:78px 26px 64px;}
.hero h1{font:600 clamp(34px,5.4vw,54px)/1.1 var(--display);letter-spacing:-1.6px;margin:22px 0 20px;text-wrap:balance;}
.hero p{font-size:17px;line-height:1.62;color:var(--muted);margin:0 auto 32px;max-width:520px;}
.hero-btns{display:flex;gap:13px;justify-content:center;flex-wrap:wrap;}
.hero-stats{display:flex;gap:36px;justify-content:center;margin-top:44px;padding-top:32px;border-top:1px solid var(--line);flex-wrap:wrap;}
.hero-stats .v{font:600 30px/1 var(--display);letter-spacing:-.5px;}
.hero-stats .l{font:600 11px/1.3 var(--mono);color:var(--faint);text-transform:uppercase;letter-spacing:.08em;margin-top:7px;}
.hero-img{position:relative;aspect-ratio:21/9;border-radius:20px;overflow:hidden;margin-bottom:62px;}
.hero-img img{width:100%;height:100%;object-fit:cover;display:block;}

/* ─── Hairline ızgara (Nane'nin imzası): 1px boşluk = çizgi ─── */
.hair{display:flex;flex-wrap:wrap;gap:1px;background:var(--line);border:1px solid var(--line);border-radius:16px;overflow:hidden;--cols:3;--min:250px;}
.hair>*{flex:0 1 calc((100% - (var(--cols) - 1) * 1px) / var(--cols));min-width:min(var(--min),100%);background:#fff;}
.cell{padding:30px 26px;display:flex;flex-direction:column;transition:background .2s ease;}
.cell:hover{background:var(--tint);}
.cell-ic{color:var(--accent-deep);font-size:22px;margin-bottom:14px;display:flex;}
.cell h3{font:600 17px/1.25 var(--display);margin:0 0 8px;}
.cell p{font-size:13.5px;line-height:1.6;color:var(--muted);margin:0 0 14px;}
.ticks{list-style:none;padding:0;margin:auto 0 0;display:flex;flex-direction:column;gap:8px;}
.ticks li{display:flex;align-items:flex-start;gap:9px;font-size:13px;line-height:1.4;color:var(--ink-mid);}
.ticks svg{color:var(--accent-deep);flex-shrink:0;margin-top:2px;font-size:15px;}

/* ─── Kart ızgarası (gölgesiz, çerçeveli) ─── */
.grid{display:flex;flex-wrap:wrap;justify-content:center;gap:var(--gap,18px);--cols:3;--min:250px;}
.grid>*{flex:0 1 calc((100% - (var(--cols) - 1) * var(--gap,18px)) / var(--cols));min-width:min(var(--min),100%);}
.card{border:1px solid var(--line);border-radius:16px;padding:28px;background:#fff;transition:transform .2s ease,border-color .2s ease;}
.card:hover{transform:translateY(-3px);border-color:var(--accent);}

/* ─── Süreç (üst çizgi + dikey ayraçlar) ─── */
.proc{display:flex;flex-wrap:wrap;border-top:1px solid var(--line-2);--cols:4;--min:200px;}
.proc>*{flex:0 1 calc(100% / var(--cols));min-width:min(var(--min),100%);padding:28px 22px 0;border-right:1px solid var(--line);}
.proc>*:last-child{border-right:0;}
.proc .n{font:600 13px/1 var(--mono);color:var(--accent-deep);}
.proc h3{font:600 16px/1.25 var(--display);margin:14px 0 8px;}
.proc p{font-size:13.5px;line-height:1.6;color:var(--muted);margin:0;}

/* ─── Üniversite bandı ─── */
.unis{display:flex;align-items:center;gap:32px;flex-wrap:wrap;justify-content:center;background:var(--soft);border-radius:14px;padding:20px 28px;}
.unis-lbl{font:600 11px/1.3 var(--mono);color:var(--faint);text-transform:uppercase;letter-spacing:.08em;}
.unis .u{font:600 16px/1 var(--display);color:color-mix(in srgb, var(--accent) 62%, var(--muted));}

/* ─── Yorumlar / ekip ─── */
.quote p{font-size:14.5px;line-height:1.62;color:var(--ink-mid);margin:0 0 20px;}
.who{display:flex;align-items:center;gap:12px;}
.avatar{width:44px;height:44px;border-radius:50%;background:var(--soft);color:var(--accent-deep);display:flex;align-items:center;justify-content:center;font:700 16px/1 var(--display);flex-shrink:0;overflow:hidden;}
.avatar img{width:100%;height:100%;object-fit:cover;}
.who b{display:block;font:600 14px/1.1 var(--display);}
.who span{font:500 12px/1.2 var(--mono);color:var(--faint);}
.member{border:1px solid var(--line);border-radius:16px;padding:22px;display:flex;align-items:center;gap:14px;background:#fff;}
.member .avatar{width:50px;height:50px;font-size:18px;}
.trust{background:var(--ink);border-radius:16px;padding:22px;display:flex;align-items:center;gap:14px;}
.trust-ic{width:50px;height:50px;border-radius:13px;background:color-mix(in srgb, var(--accent) 26%, transparent);color:color-mix(in srgb, var(--accent) 70%, #fff);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:25px;}
.trust b{font:600 15px/1.2 var(--display);color:var(--on-ink);}
.trust p{font-size:12.5px;line-height:1.5;color:var(--on-ink-soft);margin:4px 0 0;}

/* ─── Paketler (hairline + öne çıkan koyu) ─── */
.pkg{padding:32px 28px;display:flex;flex-direction:column;transition:background .2s ease;}
.pkg:hover{background:var(--tint);}
.pkg-tag{font:600 11px/1 var(--mono);letter-spacing:.1em;text-transform:uppercase;color:var(--accent-deep);}
.pkg h3{font:600 21px/1.2 var(--display);margin:14px 0 10px;}
.pkg p{font-size:13.5px;line-height:1.6;color:var(--muted);margin:0 0 18px;}
.pkg .ticks{padding:18px 0 0;margin:0 0 22px;border-top:1px solid var(--soft);}
.pkg-btn{margin-top:auto;text-decoration:none;text-align:center;background:var(--soft);color:var(--ink);font:700 14px/1 var(--body);padding:15px;border-radius:11px;transition:filter .15s ease;}
.pkg-btn:hover{filter:brightness(.95);}
.pkg-hi{background:var(--ink);}
.pkg-hi:hover{background:var(--ink-mid);}
.pkg-hi h3{color:var(--on-ink);}
.pkg-hi p{color:var(--on-ink-soft);}
.pkg-hi .pkg-tag{color:color-mix(in srgb, var(--accent) 70%, #fff);}
.pkg-hi .ticks{border-color:rgba(255,255,255,.14);}
.pkg-hi .ticks li{color:var(--on-ink);}
.pkg-hi .ticks svg{color:color-mix(in srgb, var(--accent) 70%, #fff);}
.pkg-hi .pkg-btn{background:var(--accent);color:var(--ink);}

/* ─── S.S.S. (alt çizgili satırlar, JS'siz) ─── */
.faq{max-width:900px;margin:0 auto;}
.faq details{border-bottom:1px solid var(--line);}
.faq summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:21px 6px;font:600 16px/1.35 var(--body);transition:background .15s ease;}
.faq summary::-webkit-details-marker{display:none;}
.faq summary:hover{background:var(--soft);}
.faq summary .ico{width:28px;height:28px;flex-shrink:0;border-radius:50%;background:var(--soft);color:var(--accent-deep);display:flex;align-items:center;justify-content:center;font:600 19px/1 var(--body);transition:transform .15s ease;}
.faq details[open] summary .ico{transform:rotate(45deg);}
.faq p{font-size:14.5px;line-height:1.65;color:var(--muted);margin:0;padding:0 6px 22px;}

/* ─── CTA ─── */
.cta{background:var(--ink);border-radius:20px;padding:52px 48px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:44px;align-items:center;}
.cta h2{font:600 clamp(25px,3.3vw,34px)/1.16 var(--display);letter-spacing:-1.2px;margin:0 0 14px;color:var(--on-ink);text-wrap:balance;}
.cta-l>p{font-size:15.5px;line-height:1.65;color:var(--on-ink-soft);margin:0 0 22px;}
.cta-ticks{display:flex;flex-direction:column;gap:12px;}
.cta-ticks span{display:inline-flex;align-items:center;gap:11px;font:600 14px/1.3 var(--body);color:var(--on-ink);}
.cta-ticks svg{color:color-mix(in srgb, var(--accent) 70%, #fff);flex-shrink:0;font-size:18px;}
.cta-r{background:var(--tint);border-radius:14px;padding:28px;}
.cta-r h3{font:600 19px/1.25 var(--display);margin:0 0 8px;}
.cta-r>p{font-size:13.5px;line-height:1.55;color:var(--muted);margin:0 0 18px;}
.cta-r .btn{width:100%;justify-content:center;}
.contact{list-style:none;padding:18px 0 0;margin:18px 0 0;border-top:1px solid var(--line);display:flex;flex-direction:column;gap:11px;}
.contact li{display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--muted);}
.contact svg{color:var(--accent-deep);flex-shrink:0;font-size:17px;}
.contact a{color:var(--ink);text-decoration:none;font-weight:600;}
.contact a:hover{color:var(--accent-deep);}

/* ─── Footer ─── */
.foot{border-top:1px solid var(--line);}
.foot-in{max-width:var(--maxw);margin:0 auto;padding:32px 26px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;}
.foot-txt{font:500 13px/1.5 var(--mono);color:var(--faint);}
.foot-dim{font:500 12px/1.5 var(--mono);color:color-mix(in srgb, var(--faint) 78%, #fff);}
.foot-soc a{color:var(--faint);display:inline-flex;font-size:19px;transition:color .15s ease,transform .15s ease;}
.foot-soc a:hover{color:var(--accent-deep);transform:translateY(-2px);}

/* ─── WhatsApp ─── */
.wa-float{position:fixed;right:24px;bottom:24px;z-index:60;text-decoration:none;display:inline-flex;align-items:center;gap:10px;background:var(--ink);color:var(--on-ink);font:700 14px/1 var(--body);padding:14px 18px;border-radius:30px;box-shadow:0 14px 30px color-mix(in srgb, var(--ink) 30%, transparent);transition:transform .18s ease;}
.wa-float:hover{transform:translateY(-3px);}
.wa-float svg{color:color-mix(in srgb, var(--accent) 70%, #fff);font-size:20px;}
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
                <span class="brand-dot"></span><span class="brand-name">{{ $siteName }}</span>
            @endif
        </a>
        <div class="nav-links">
            @foreach($navLinks as $nl)
                <a href="{{ $nl['href'] }}" class="nav-link">{{ $nl['label'] }}</a>
            @endforeach
            <a href="{{ $applyUrl }}" class="nav-cta" data-track="cta_clicked" data-ph-cta-name="nav_apply" data-ph-location="partner_nane_nav">Başvur</a>
        </div>
    </div>
</div>

{{-- ═══ HERO ═══ --}}
<div class="hero">
    <span class="lbl">Almanya · Eğitim Danışmanlığı</span>
    <h1>{{ $heroTitle }}</h1>
    <p>{{ $heroSubtitle }}</p>
    <div class="hero-btns">
        <a href="{{ $applyUrl }}" class="btn btn-dark" data-track="cta_clicked" data-ph-cta-name="hero_apply" data-ph-location="partner_nane_hero">Ücretsiz danışmanlık</a>
        @if(!empty($navLinks))<a href="{{ $navLinks[0]['href'] }}" class="btn btn-line">{{ $navLinks[0]['label'] }}</a>@endif
    </div>
    {{-- Sadece partnerin kendi istatistikleri; boşsa satır hiç basılmaz --}}
    @if(!empty($heroTrust))
        <div class="hero-stats">
            @foreach($heroTrust as $ht)
                <div><div class="v">{{ $ht['value'] }}</div><div class="l">{{ $ht['label'] }}</div></div>
            @endforeach
        </div>
    @endif
</div>

@if($heroImg)
    <div class="wrap"><div class="hero-img"><img src="{{ $heroImg }}" alt="{{ $siteName }}"></div></div>
@endif

{{-- ═══ SIRALANABİLİR BÖLÜMLER ═══ --}}
@foreach($sections as $sectionKey)
    @includeIf('public.partner-templates.nane.sections.' . $sectionKey)
@endforeach

{{-- ═══ BAŞVURU / İLETİŞİM ═══ --}}
<div id="basvuru" class="wrap" style="padding:8px 26px 72px;">
    <div class="cta">
        <div class="cta-l">
            <h2>Bugün ücretsiz başlayın</h2>
            <p>Başvurun, ekibimiz en kısa sürede sizinle iletişime geçsin. Hiçbir yükümlülük altına girmezsiniz.</p>
            <div class="cta-ticks">
                <span>{!! $icon('check') !!}Uçtan uca, tek elden yönetim</span>
                <span>{!! $icon('check') !!}Her adım panelden şeffaf takip</span>
            </div>
        </div>
        <div class="cta-r">
            <h3>Ücretsiz ön görüşme</h3>
            <p>Formu doldurun; hedeflerinizi dinleyip size özel bir yol haritası çıkaralım.</p>
            <a href="{{ $applyUrl }}" class="btn btn-accent" data-track="cta_clicked" data-ph-cta-name="footer_apply" data-ph-location="partner_nane_cta">Başvuru formunu aç {!! $icon('arrow') !!}</a>
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
                <span class="brand-dot"></span><span class="brand-name" style="font-size:16px;">{{ $siteName }}</span>
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
    <a href="{{ $waUrl }}" class="wa-float" target="_blank" rel="noopener" data-track="cta_clicked" data-ph-cta-name="whatsapp_float" data-ph-location="partner_nane_float">
        {!! $icon('wa') !!} WhatsApp
    </a>
@endif

</body>
</html>
