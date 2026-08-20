{{--
    PARTNER TEMPLATE · SEDEF
    Yumuşak ve güven veren: geniş yuvarlatmalar (24–32px), çerçevesiz yumuşak gölgeler,
    pill etiketler, sakin adaçayı paleti. Kaynak tasarım: "Sedef" (DC).

    Kurallar: JS YOK (S.S.S. <details>), font SADECE lokal, uydurma veri YOK.
    Bölümler modüler: sedef/sections/*.blade.php — sıra + aç/kapa $sections'tan.
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
    --accent-deep:color-mix(in srgb, var(--accent) 84%, #06110e);
    --accent-soft:color-mix(in srgb, var(--accent) 12%, #fff);
    --tint:color-mix(in srgb, var(--accent) 4%, #f7f9f8);
    --line:color-mix(in srgb, var(--accent) 10%, #fff);
    --ink:color-mix(in srgb, var(--accent) 14%, #1b2622);
    --ink-2:color-mix(in srgb, var(--accent) 16%, #3a4a45);
    --muted:color-mix(in srgb, var(--accent) 16%, #5d6f69);
    --faint:color-mix(in srgb, var(--accent) 24%, #93a29d);
    --shadow-s:0 4px 18px color-mix(in srgb, var(--ink) 6%, transparent);
    --shadow-m:0 18px 40px color-mix(in srgb, var(--ink) 11%, transparent);
    --display:"Manrope","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --body:"DM Sans","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --maxw:1300px;
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;scroll-behavior:smooth;}
body{background:var(--tint);color:var(--ink);font-family:var(--body);font-size:15px;line-height:1.65;-webkit-font-smoothing:antialiased;}
svg{width:1em;height:1em;}
img{max-width:100%;}
a{color:var(--accent-deep);}
.wrap{max-width:var(--maxw);margin:0 auto;padding:0 26px;}
.sec{padding:0 0 64px;}
h1,h2,h3{margin:0;font-family:var(--display);}
.pill-lbl{display:inline-block;font:600 12.5px/1 var(--display);color:var(--accent-deep);background:var(--accent-soft);padding:8px 15px;border-radius:30px;}
.h2{font:700 clamp(26px,3.5vw,36px)/1.2 var(--display);letter-spacing:-1.1px;margin:18px 0 12px;text-wrap:balance;}
.sec-head{max-width:620px;margin-bottom:38px;}
.sec-head.center{text-align:center;margin-left:auto;margin-right:auto;}
.sec-head p{font-size:16px;line-height:1.68;color:var(--muted);margin:0;}

/* ─── Butonlar ─── */
.btn{display:inline-flex;align-items:center;gap:9px;text-decoration:none;font:600 15.5px/1 var(--display);padding:17px 30px;border-radius:16px;transition:transform .18s ease,box-shadow .18s ease;}
.btn-accent{background:var(--accent);color:#fff;box-shadow:0 12px 28px color-mix(in srgb, var(--accent) 26%, transparent);}
.btn-accent:hover{transform:translateY(-2px);box-shadow:0 16px 34px color-mix(in srgb, var(--accent) 34%, transparent);}
.btn-white{background:#fff;color:var(--ink);box-shadow:var(--shadow-s);padding:16px 26px;}
.btn-white:hover{transform:translateY(-2px);}

/* ─── Nav ─── */
.nav{position:sticky;top:0;z-index:50;background:color-mix(in srgb, var(--tint) 88%, transparent);backdrop-filter:blur(12px);}
.nav-in{max-width:var(--maxw);margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:18px 26px;}
.brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--ink);}
.brand-mark{width:34px;height:34px;border-radius:12px;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font:800 15px/1 var(--display);flex-shrink:0;}
.brand-name{font:700 19px/1 var(--display);letter-spacing:-.3px;}
.brand img{max-height:38px;width:auto;display:block;}
.nav-links{display:flex;align-items:center;gap:26px;flex-wrap:wrap;}
.nav-links a{text-decoration:none;font:600 14px/1 var(--display);color:var(--muted);}
.nav-links a:hover{color:var(--accent-deep);}
.nav-cta{background:var(--accent);color:#fff !important;padding:12px 20px;border-radius:30px;font-size:13.5px;}
.nav-cta:hover{filter:brightness(.96);}
@media(max-width:760px){.nav-links .nav-link{display:none;}}

/* ─── Hero ─── */
.hero{max-width:var(--maxw);margin:0 auto;padding:52px 26px 60px;display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:44px;align-items:center;}
.chip-dot{display:inline-flex;align-items:center;gap:9px;font:600 12.5px/1 var(--display);color:var(--accent-deep);background:var(--accent-soft);padding:9px 16px;border-radius:30px;}
.chip-dot i{width:7px;height:7px;border-radius:50%;background:var(--accent);display:inline-block;}
.hero h1{font:700 clamp(33px,4.9vw,50px)/1.14 var(--display);letter-spacing:-1.4px;margin:22px 0 18px;text-wrap:balance;}
.hero p{font-size:17px;line-height:1.7;color:var(--muted);margin:0 0 30px;max-width:520px;}
.hero-btns{display:flex;gap:13px;align-items:center;flex-wrap:wrap;}
.hero-mini{display:flex;align-items:center;gap:22px;margin-top:30px;flex-wrap:wrap;}
.hero-mini .v{font:800 22px/1 var(--display);color:var(--accent-deep);}
.hero-mini .l{font:500 12.5px/1.3 var(--body);color:var(--muted);margin-top:4px;}
.hero-fig{border-radius:28px;overflow:hidden;box-shadow:var(--shadow-m);aspect-ratio:4/5;max-width:480px;width:100%;justify-self:end;}
.hero-fig img{width:100%;height:100%;object-fit:cover;display:block;}

/* ─── Izgara + yumuşak kartlar ─── */
.grid{display:flex;flex-wrap:wrap;justify-content:center;gap:var(--gap,18px);--cols:3;--min:280px;}
.grid>*{flex:0 1 calc((100% - (var(--cols) - 1) * var(--gap,18px)) / var(--cols));min-width:min(var(--min),100%);}
.card{background:#fff;border-radius:24px;padding:30px 28px;display:flex;flex-direction:column;box-shadow:var(--shadow-s);transition:transform .2s ease,box-shadow .2s ease;}
.card:hover{transform:translateY(-4px);box-shadow:var(--shadow-m);}
.card-ic{width:54px;height:54px;border-radius:18px;background:var(--accent-soft);color:var(--accent-deep);display:flex;align-items:center;justify-content:center;margin-bottom:18px;font-size:27px;}
.card h3{font:700 18.5px/1.25 var(--display);margin:0 0 9px;}
.card p{font-size:14.5px;line-height:1.62;color:var(--muted);margin:0 0 18px;}
.ticks{list-style:none;padding:18px 0 0;margin:auto 0 0;border-top:1px solid var(--line);display:flex;flex-direction:column;gap:10px;}
.ticks li{display:flex;align-items:flex-start;gap:10px;font-size:13.5px;line-height:1.5;color:var(--ink-2);}
.ticks svg{color:var(--accent);flex-shrink:0;margin-top:2px;font-size:16px;}

/* ─── Panel (büyük beyaz blok) ─── */
.panel{background:#fff;border-radius:32px;padding:48px 44px;box-shadow:var(--shadow-s);}
.step .n{width:46px;height:46px;border-radius:16px;background:var(--accent-soft);color:var(--accent-deep);display:flex;align-items:center;justify-content:center;font:800 16px/1 var(--display);margin-bottom:16px;}
.step h3{font:700 17px/1.25 var(--display);margin:0 0 8px;}
.step p{font-size:14px;line-height:1.62;color:var(--muted);margin:0;}

/* ─── İstatistik bloğu ─── */
.stat-block{background:var(--accent-deep);border-radius:32px;padding:44px 40px;display:flex;flex-wrap:wrap;gap:26px;}
.stat-block>div{flex:1 1 180px;}
.stat-block .v{font:800 clamp(30px,3.6vw,40px)/1 var(--display);letter-spacing:-1px;color:#fff;}
.stat-block .l{font:500 13px/1.4 var(--body);color:rgba(255,255,255,.74);margin-top:9px;}

/* ─── Üniversite ─── */
.unis{display:flex;align-items:center;gap:30px;flex-wrap:wrap;background:#fff;border-radius:24px;padding:22px 28px;box-shadow:var(--shadow-s);}
.unis-lbl{font:600 12px/1.3 var(--display);color:var(--faint);}
.unis .u{font:700 17px/1 var(--display);color:color-mix(in srgb, var(--accent) 46%, var(--faint));}

/* ─── Yorum / ekip ─── */
.quote p{font-size:15px;line-height:1.7;color:var(--ink-2);margin:0 0 20px;}
.who{display:flex;align-items:center;gap:12px;}
.avatar{width:46px;height:46px;border-radius:16px;background:var(--accent-soft);color:var(--accent-deep);display:flex;align-items:center;justify-content:center;font:800 16px/1 var(--display);flex-shrink:0;overflow:hidden;}
.avatar img{width:100%;height:100%;object-fit:cover;}
.who b{display:block;font:700 14.5px/1.1 var(--display);}
.who span{font:500 12.5px/1.2 var(--body);color:var(--faint);}
.member{background:#fff;border-radius:24px;padding:24px;display:flex;align-items:center;gap:14px;box-shadow:var(--shadow-s);}
.member .avatar{width:52px;height:52px;font-size:18px;}
.trust{background:var(--accent-deep);border-radius:24px;padding:24px;display:flex;align-items:center;gap:14px;}
.trust-ic{width:52px;height:52px;border-radius:17px;background:rgba(255,255,255,.16);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:25px;}
.trust b{font:700 15px/1.25 var(--display);color:#fff;}
.trust p{font-size:12.5px;line-height:1.5;color:rgba(255,255,255,.72);margin:4px 0 0;}

/* ─── Paketler ─── */
.pkg{background:#fff;border-radius:26px;padding:32px 28px;display:flex;flex-direction:column;box-shadow:var(--shadow-s);transition:transform .2s ease,box-shadow .2s ease;}
.pkg:hover{transform:translateY(-4px);box-shadow:var(--shadow-m);}
.pkg-tag{font:600 11.5px/1 var(--display);color:var(--accent-deep);background:var(--accent-soft);padding:7px 13px;border-radius:20px;align-self:flex-start;}
.pkg h3{font:700 22px/1.2 var(--display);margin:16px 0 8px;}
.pkg p{font-size:14px;line-height:1.6;color:var(--muted);margin:0 0 18px;}
.pkg .ticks{margin:0 0 22px;}
.pkg-btn{margin-top:auto;text-decoration:none;text-align:center;background:var(--accent-soft);color:var(--accent-deep);font:600 14.5px/1 var(--display);padding:15px;border-radius:16px;transition:filter .15s ease;}
.pkg-btn:hover{filter:brightness(.96);}
.pkg-hi{background:var(--accent-deep);box-shadow:0 22px 46px color-mix(in srgb, var(--accent-deep) 26%, transparent);}
.pkg-hi h3{color:#fff;}
.pkg-hi p{color:rgba(255,255,255,.76);}
.pkg-hi .pkg-tag{background:rgba(255,255,255,.18);color:#fff;}
.pkg-hi .ticks{border-color:rgba(255,255,255,.16);}
.pkg-hi .ticks li{color:rgba(255,255,255,.9);}
.pkg-hi .ticks svg{color:#fff;}
.pkg-hi .pkg-btn{background:#fff;color:var(--accent-deep);}

/* ─── S.S.S. (JS'siz) ─── */
.faq{max-width:900px;margin:0 auto;}
.faq details{background:#fff;border-radius:20px;box-shadow:var(--shadow-s);margin-bottom:12px;overflow:hidden;}
.faq summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:22px 26px;font:600 16px/1.4 var(--display);transition:background .15s ease;}
.faq summary::-webkit-details-marker{display:none;}
.faq summary:hover{background:var(--accent-soft);}
.faq summary .ico{width:28px;height:28px;flex-shrink:0;border-radius:10px;background:var(--accent-soft);color:var(--accent-deep);display:flex;align-items:center;justify-content:center;font:700 18px/1 var(--display);transition:transform .15s ease;}
.faq details[open] summary .ico{transform:rotate(45deg);}
.faq p{font-size:14.5px;line-height:1.7;color:var(--muted);margin:0;padding:0 26px 22px;}

/* ─── CTA ─── */
.cta{background:var(--accent-deep);border-radius:32px;padding:52px 46px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:44px;align-items:center;}
.cta h2{font:700 clamp(26px,3.4vw,34px)/1.2 var(--display);letter-spacing:-1px;margin:0 0 14px;color:#fff;}
.cta-l>p{font-size:15.5px;line-height:1.7;color:rgba(255,255,255,.76);margin:0 0 22px;}
.cta-ticks{display:flex;flex-direction:column;gap:12px;}
.cta-ticks span{display:inline-flex;align-items:center;gap:11px;font:600 14px/1.4 var(--display);color:#fff;}
.cta-ticks svg{color:#fff;opacity:.8;flex-shrink:0;font-size:18px;}
.cta-r{background:#fff;border-radius:24px;padding:30px;}
.cta-r h3{font:700 19px/1.25 var(--display);margin:0 0 8px;}
.cta-r>p{font-size:14px;line-height:1.6;color:var(--muted);margin:0 0 18px;}
.cta-r .btn{width:100%;justify-content:center;}
.contact{list-style:none;padding:18px 0 0;margin:18px 0 0;border-top:1px solid var(--line);display:flex;flex-direction:column;gap:11px;}
.contact li{display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--muted);}
.contact svg{color:var(--accent);flex-shrink:0;font-size:17px;}
.contact a{color:var(--ink);text-decoration:none;font-weight:600;}
.contact a:hover{color:var(--accent-deep);}

/* ─── Footer ─── */
.foot-in{max-width:var(--maxw);margin:0 auto;padding:34px 26px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;}
.foot-txt{font:500 13px/1.5 var(--body);color:var(--faint);}
.foot-dim{font:500 12.5px/1.5 var(--body);color:color-mix(in srgb, var(--faint) 78%, #fff);}
.foot-soc a{color:var(--faint);display:inline-flex;font-size:19px;transition:color .15s ease,transform .15s ease;}
.foot-soc a:hover{color:var(--accent-deep);transform:translateY(-2px);}

/* ─── WhatsApp ─── */
.wa-float{position:fixed;right:24px;bottom:24px;z-index:60;text-decoration:none;display:inline-flex;align-items:center;gap:10px;background:var(--accent-deep);color:#fff;font:600 14px/1 var(--display);padding:15px 19px;border-radius:30px;box-shadow:0 14px 30px color-mix(in srgb, var(--accent-deep) 30%, transparent);transition:transform .18s ease;}
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
            <a href="{{ $applyUrl }}" class="nav-cta" data-track="cta_clicked" data-ph-cta-name="nav_apply" data-ph-location="partner_sedef_nav">Ücretsiz Başvur</a>
        </div>
    </div>
</div>

{{-- ═══ HERO ═══ --}}
<div class="hero">
    <div>
        <span class="chip-dot"><i></i>İlk görüşme ücretsiz</span>
        <h1>{{ $heroTitle }}</h1>
        <p>{{ $heroSubtitle }}</p>
        <div class="hero-btns">
            <a href="{{ $applyUrl }}" class="btn btn-accent" data-track="cta_clicked" data-ph-cta-name="hero_apply" data-ph-location="partner_sedef_hero">Ücretsiz danışmanlık {!! $icon('arrow') !!}</a>
            @if(!empty($navLinks))<a href="{{ $navLinks[0]['href'] }}" class="btn btn-white">{{ $navLinks[0]['label'] }}</a>@endif
        </div>
        {{-- Sadece partnerin girdiği istatistikler --}}
        @if(!empty($heroTrust))
            <div class="hero-mini">
                @foreach($heroTrust as $ht)
                    <div><div class="v">{{ $ht['value'] }}</div><div class="l">{{ $ht['label'] }}</div></div>
                @endforeach
            </div>
        @endif
    </div>
    @if($heroImg)
        <figure class="hero-fig" style="margin:0;"><img src="{{ $heroImg }}" alt="{{ $siteName }}"></figure>
    @endif
</div>

{{-- ═══ SIRALANABİLİR BÖLÜMLER ═══ --}}
@foreach($sections as $sectionKey)
    @includeIf('public.partner-templates.sedef.sections.' . $sectionKey)
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
            <a href="{{ $applyUrl }}" class="btn btn-accent" data-track="cta_clicked" data-ph-cta-name="footer_apply" data-ph-location="partner_sedef_cta">Başvuru formunu aç {!! $icon('arrow') !!}</a>
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
    <a href="{{ $waUrl }}" class="wa-float" target="_blank" rel="noopener" data-track="cta_clicked" data-ph-cta-name="whatsapp_float" data-ph-location="partner_sedef_float">
        {!! $icon('wa') !!} WhatsApp
    </a>
@endif

</body>
</html>
