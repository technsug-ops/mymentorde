{{--
    PARTNER TEMPLATE · UFUK
    Kurumsal ve güven odaklı: lacivert bloklar + bronz vurgu, Newsreader serif başlıklar,
    krem zemin, çerçeveli tablo benzeri hizmet ızgarası, orta sayfa CTA bandı.
    Kaynak tasarım: "Ufuk" (DC).

    Kurallar: JS YOK (S.S.S. <details>), font SADECE lokal, uydurma veri YOK.
    Bölümler modüler: ufuk/sections/*.blade.php — sıra + aç/kapa $sections'tan.
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
    --navy:color-mix(in srgb, var(--accent) 34%, #101d31);
    --navy-2:color-mix(in srgb, var(--accent) 38%, #1c2d47);
    /* Bronz ikincil vurgu accent'ten türetilir */
    --bronze:color-mix(in srgb, var(--accent) 22%, #c98b4f);
    --bronze-soft:color-mix(in srgb, var(--bronze) 14%, #fff);
    --paper:color-mix(in srgb, var(--accent) 3%, #f6f3ec);
    --paper-2:color-mix(in srgb, var(--accent) 5%, #faf7f0);
    --line:color-mix(in srgb, var(--accent) 8%, #e4ded2);
    --line-2:color-mix(in srgb, var(--accent) 12%, #efe9dd);
    --ink:color-mix(in srgb, var(--accent) 24%, #13202f);
    --ink-2:color-mix(in srgb, var(--accent) 14%, #3f4a5c);
    --muted:color-mix(in srgb, var(--accent) 12%, #5a6577);
    --faint:color-mix(in srgb, var(--accent) 16%, #939cab);
    --on-navy:color-mix(in srgb, var(--paper) 78%, var(--navy));
    --serif:"Newsreader","DM Serif Display",Georgia,serif;
    --body:"Public Sans","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --mono:"IBM Plex Mono",ui-monospace,SFMono-Regular,monospace;
    --maxw:1320px;
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;scroll-behavior:smooth;}
body{background:var(--paper);color:var(--ink);font-family:var(--body);font-size:15px;line-height:1.65;-webkit-font-smoothing:antialiased;}
svg{width:1em;height:1em;}
img{max-width:100%;}
a{color:var(--navy);}
.wrap{max-width:var(--maxw);margin:0 auto;padding:0 26px;}
.sec{padding:60px 0 56px;}
h1,h2,h3{margin:0;}
.lbl{font:600 11px/1 var(--mono);letter-spacing:.18em;color:var(--bronze);text-transform:uppercase;}
.h2{font:400 clamp(27px,3.7vw,38px)/1.16 var(--serif);margin:16px 0 0;text-wrap:balance;}
.sec-head{max-width:620px;margin-bottom:36px;}
.sec-head.center{margin:0 auto 36px;text-align:center;}
.sec-head p{font-size:15px;line-height:1.7;color:var(--muted);margin:12px 0 0;}

/* ─── Butonlar ─── */
.btn{display:inline-flex;align-items:center;gap:9px;text-decoration:none;font:700 14px/1 var(--body);padding:16px 28px;border-radius:8px;transition:background .15s ease,transform .15s ease,border-color .15s ease;}
.btn-navy{background:var(--navy);color:var(--paper);}
.btn-navy:hover{background:var(--navy-2);transform:translateY(-2px);}
.btn-line{border:1.5px solid var(--line);color:var(--ink);padding:15px 24px;}
.btn-line:hover{border-color:var(--bronze);}
.btn-bronze{background:var(--bronze);color:#fff;}
.btn-bronze:hover{filter:brightness(1.06);transform:translateY(-2px);}

/* ─── Nav ─── */
.nav{position:sticky;top:0;z-index:50;background:color-mix(in srgb, var(--paper) 92%, transparent);backdrop-filter:blur(10px);border-bottom:1px solid var(--line);}
.nav-in{max-width:var(--maxw);margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:16px 26px;}
.brand{display:flex;align-items:center;gap:11px;text-decoration:none;color:var(--ink);}
.brand-mark{width:34px;height:34px;border-radius:8px;background:var(--navy);color:var(--bronze);display:flex;align-items:center;justify-content:center;font:500 17px/1 var(--serif);flex-shrink:0;}
.brand-name{font:400 22px/1 var(--serif);}
.brand img{max-height:40px;width:auto;display:block;}
.nav-links{display:flex;align-items:center;gap:26px;flex-wrap:wrap;}
.nav-links a{text-decoration:none;font:600 13.5px/1 var(--body);color:var(--muted);}
.nav-links a:hover{color:var(--navy);}
.nav-cta{background:var(--navy);color:var(--paper) !important;font-weight:700;padding:12px 19px;border-radius:8px;}
.nav-cta:hover{background:var(--navy-2);}
@media(max-width:760px){.nav-links .nav-link{display:none;}}

/* ─── Hero ─── */
.hero{max-width:var(--maxw);margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:44px;align-items:center;padding:58px 26px 56px;}
.hero h1{font:400 clamp(32px,5vw,52px)/1.1 var(--serif);letter-spacing:-.5px;margin:20px 0;text-wrap:balance;}
.hero p{font-size:16.5px;line-height:1.66;color:var(--muted);margin:0 0 28px;max-width:500px;}
.hero-btns{display:flex;gap:12px;align-items:center;flex-wrap:wrap;}
.hero-fig{aspect-ratio:4/3;overflow:hidden;border-radius:14px;border:1px solid var(--line);max-width:560px;width:100%;justify-self:end;}
.hero-fig img{width:100%;height:100%;object-fit:cover;display:block;}
.hero-facts{display:flex;flex-wrap:wrap;gap:26px;margin-top:30px;padding-top:26px;border-top:1px solid var(--line);}
.hero-facts .v{font:400 26px/1 var(--serif);color:var(--navy);}
.hero-facts .l{font:600 11px/1.3 var(--mono);color:var(--faint);text-transform:uppercase;letter-spacing:.08em;margin-top:6px;}

/* ─── Üniversite satırı ─── */
.unis{display:flex;align-items:center;gap:30px;flex-wrap:wrap;border-top:1px solid var(--line);border-bottom:1px solid var(--line);padding:22px 0;}
.unis-lbl{font:600 11px/1.3 var(--mono);color:var(--faint);text-transform:uppercase;letter-spacing:.08em;}
.unis .u{font:400 18px/1 var(--serif);color:color-mix(in srgb, var(--navy) 42%, var(--faint));}

/* ─── Hizmetler: çerçeveli tablo ızgarası ─── */
.table{background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden;display:flex;flex-wrap:wrap;--cols:3;--min:280px;}
.table>*{flex:0 1 calc(100% / var(--cols));min-width:min(var(--min),100%);padding:30px 32px;border-bottom:1px solid var(--line-2);border-right:1px solid var(--line-2);transition:background .15s ease;}
.table>*:hover{background:var(--paper-2);}
.t-head{display:flex;align-items:center;gap:13px;margin-bottom:10px;}
.t-ic{width:42px;height:42px;border-radius:9px;background:var(--navy);color:var(--bronze);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:21px;}
.t-no{font:500 13px/1 var(--mono);color:var(--bronze);}
.table h3{font:700 17px/1.2 var(--body);margin:3px 0 0;}
.table p{font-size:13.5px;line-height:1.62;color:var(--muted);margin:0 0 12px;}
.ticks{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px;}
.ticks li{display:flex;align-items:flex-start;gap:9px;font-size:13px;line-height:1.45;color:var(--ink-2);}
.ticks svg{color:var(--bronze);flex-shrink:0;margin-top:2px;font-size:15px;}

/* ─── Kart ızgarası ─── */
.grid{display:flex;flex-wrap:wrap;justify-content:center;gap:var(--gap,18px);--cols:3;--min:260px;}
.grid>*{flex:0 1 calc((100% - (var(--cols) - 1) * var(--gap,18px)) / var(--cols));min-width:min(var(--min),100%);}
.card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:26px 24px;display:flex;flex-direction:column;transition:border-color .2s ease,transform .2s ease;}
.card:hover{border-color:var(--bronze);transform:translateY(-3px);}
.card-ic{width:44px;height:44px;border-radius:9px;background:var(--bronze-soft);color:var(--bronze);display:flex;align-items:center;justify-content:center;margin-bottom:14px;font-size:22px;}
.card h3{font:700 16px/1.25 var(--body);margin:0 0 7px;}
.card p{font-size:13.5px;line-height:1.6;color:var(--muted);margin:0;}

/* ─── Lacivert bant (istatistik / CTA) ─── */
.band{background:var(--navy);}
.band-in{max-width:var(--maxw);margin:0 auto;padding:46px 26px;display:flex;flex-wrap:wrap;gap:26px;}
.band-in>div{flex:1 1 190px;}
.band .v{font:400 clamp(28px,3.6vw,38px)/1 var(--serif);color:var(--bronze);}
.band .l{font:600 11px/1.35 var(--mono);color:var(--on-navy);text-transform:uppercase;letter-spacing:.08em;margin-top:8px;}

/* ─── Süreç ─── */
.step{border-top:1px solid var(--line);padding-top:20px;}
.step .n{font:500 14px/1 var(--mono);color:var(--bronze);}
.step h3{font:400 21px/1.25 var(--serif);margin:12px 0 8px;}
.step p{font-size:13.5px;line-height:1.65;color:var(--muted);margin:0;}

/* ─── Yorum / ekip ─── */
.quote p{font:400 16.5px/1.7 var(--serif);color:var(--ink-2);margin:0 0 18px;}
.who{display:flex;align-items:center;gap:12px;}
.avatar{width:44px;height:44px;border-radius:10px;background:var(--navy);color:var(--bronze);display:flex;align-items:center;justify-content:center;font:500 17px/1 var(--serif);flex-shrink:0;overflow:hidden;}
.avatar img{width:100%;height:100%;object-fit:cover;}
.who b{display:block;font:700 14px/1.1 var(--body);}
.who span{font:500 12px/1.2 var(--mono);color:var(--faint);}
.member{background:#fff;border:1px solid var(--line);border-radius:14px;padding:22px;display:flex;align-items:center;gap:14px;}
.member .avatar{width:50px;height:50px;}
.trust{background:var(--navy);border-radius:14px;padding:22px;display:flex;align-items:center;gap:14px;}
.trust-ic{width:50px;height:50px;border-radius:10px;background:color-mix(in srgb, var(--bronze) 28%, transparent);color:var(--bronze);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:24px;}
.trust b{font:400 18px/1.2 var(--serif);color:var(--paper);}
.trust p{font-size:12.5px;line-height:1.5;color:var(--on-navy);margin:4px 0 0;}

/* ─── Paketler ─── */
.pkg{background:#fff;border:1px solid var(--line);border-radius:14px;padding:30px 28px;display:flex;flex-direction:column;transition:border-color .2s ease,transform .2s ease;}
.pkg:hover{border-color:var(--bronze);transform:translateY(-3px);}
.pkg-tag{font:600 10px/1 var(--mono);letter-spacing:.14em;text-transform:uppercase;color:var(--bronze);}
.pkg h3{font:400 25px/1.2 var(--serif);margin:12px 0 10px;}
.pkg p{font-size:13.5px;line-height:1.65;color:var(--muted);margin:0 0 18px;}
.pkg .ticks{margin:0 0 22px;padding-top:18px;border-top:1px solid var(--line-2);}
.pkg-btn{margin-top:auto;text-decoration:none;text-align:center;background:var(--bronze-soft);color:color-mix(in srgb, var(--bronze) 80%, #000);font:700 13.5px/1 var(--body);padding:15px;border-radius:8px;transition:filter .15s ease;}
.pkg-btn:hover{filter:brightness(.97);}
.pkg-hi{background:var(--navy);border-color:var(--navy);}
.pkg-hi h3{color:var(--paper);}
.pkg-hi p{color:var(--on-navy);}
.pkg-hi .ticks{border-color:rgba(255,255,255,.14);}
.pkg-hi .ticks li{color:color-mix(in srgb, var(--paper) 88%, var(--navy));}
.pkg-hi .pkg-btn{background:var(--bronze);color:#fff;}

/* ─── S.S.S. (JS'siz) ─── */
.faq{max-width:900px;margin:0 auto;}
.faq details{background:#fff;border:1px solid var(--line);border-radius:12px;margin-bottom:12px;overflow:hidden;}
.faq summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:20px 24px;font:600 15.5px/1.4 var(--body);transition:background .15s ease;}
.faq summary::-webkit-details-marker{display:none;}
.faq summary:hover{background:var(--paper-2);}
.faq summary .ico{width:26px;height:26px;flex-shrink:0;border-radius:7px;background:var(--bronze-soft);color:var(--bronze);display:flex;align-items:center;justify-content:center;font:700 17px/1 var(--body);transition:transform .15s ease;}
.faq details[open] summary .ico{transform:rotate(45deg);}
.faq p{font-size:14.5px;line-height:1.72;color:var(--muted);margin:0;padding:0 24px 22px;}

/* ─── CTA ─── */
.cta{background:var(--navy);border-radius:16px;padding:52px 46px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:44px;align-items:center;}
.cta .lbl{color:var(--bronze);}
.cta h2{font:400 clamp(26px,3.5vw,36px)/1.16 var(--serif);margin:16px 0 14px;color:var(--paper);text-wrap:balance;}
.cta-l>p{font-size:15px;line-height:1.7;color:var(--on-navy);margin:0 0 22px;}
.cta-ticks{display:flex;flex-direction:column;gap:11px;}
.cta-ticks span{display:inline-flex;align-items:center;gap:11px;font:600 13.5px/1.4 var(--body);color:var(--paper);}
.cta-ticks svg{color:var(--bronze);flex-shrink:0;font-size:17px;}
.cta-r{background:var(--paper);border-radius:12px;padding:30px;}
.cta-r h3{font:400 22px/1.25 var(--serif);margin:0 0 8px;}
.cta-r>p{font-size:13.5px;line-height:1.65;color:var(--muted);margin:0 0 18px;}
.cta-r .btn{width:100%;justify-content:center;}
.contact{list-style:none;padding:18px 0 0;margin:18px 0 0;border-top:1px solid var(--line);display:flex;flex-direction:column;gap:11px;}
.contact li{display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--muted);}
.contact svg{color:var(--bronze);flex-shrink:0;font-size:17px;}
.contact a{color:var(--ink);text-decoration:none;font-weight:600;}
.contact a:hover{color:var(--navy);}

/* ─── Footer ─── */
.foot{background:var(--navy);}
.foot-in{max-width:var(--maxw);margin:0 auto;padding:34px 26px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;}
.foot .brand-name{color:var(--paper);}
.foot .brand-mark{background:color-mix(in srgb, var(--bronze) 26%, transparent);}
.foot-txt{font:500 12.5px/1.5 var(--mono);color:var(--on-navy);}
.foot-dim{font:500 12px/1.5 var(--mono);color:color-mix(in srgb, var(--on-navy) 68%, var(--navy));}
.foot-dim a{color:var(--on-navy);}
.foot-soc a{color:var(--on-navy);display:inline-flex;font-size:19px;transition:color .15s ease,transform .15s ease;}
.foot-soc a:hover{color:var(--bronze);transform:translateY(-2px);}

/* ─── WhatsApp ─── */
.wa-float{position:fixed;right:24px;bottom:24px;z-index:60;text-decoration:none;display:inline-flex;align-items:center;gap:10px;background:var(--navy);color:var(--paper);font:700 13.5px/1 var(--body);padding:15px 19px;border-radius:8px;box-shadow:0 14px 30px color-mix(in srgb, var(--navy) 30%, transparent);transition:transform .18s ease;}
.wa-float:hover{transform:translateY(-3px);}
.wa-float svg{color:var(--bronze);font-size:19px;}
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
            <a href="{{ $applyUrl }}" class="nav-cta" data-track="cta_clicked" data-ph-cta-name="nav_apply" data-ph-location="partner_ufuk_nav">Başvuru</a>
        </div>
    </div>
</div>

{{-- ═══ HERO ═══ --}}
<div class="hero">
    <div>
        <span class="lbl">Almanya · Eğitim Danışmanlığı</span>
        <h1>{{ $heroTitle }}</h1>
        <p>{{ $heroSubtitle }}</p>
        <div class="hero-btns">
            <a href="{{ $applyUrl }}" class="btn btn-navy" data-track="cta_clicked" data-ph-cta-name="hero_apply" data-ph-location="partner_ufuk_hero">Ücretsiz danışmanlık</a>
            @if(!empty($navLinks))<a href="{{ $navLinks[0]['href'] }}" class="btn btn-line">{{ $navLinks[0]['label'] }}</a>@endif
        </div>
        {{-- Sadece partnerin girdiği istatistikler --}}
        @if(!empty($heroTrust))
            <div class="hero-facts">
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
    @includeIf('public.partner-templates.ufuk.sections.' . $sectionKey)
@endforeach

{{-- ═══ BAŞVURU / İLETİŞİM ═══ --}}
<div id="basvuru" class="wrap" style="padding:8px 26px 72px;">
    <div class="cta">
        <div class="cta-l">
            <span class="lbl">Ücretsiz ön görüşme</span>
            <h2>Yolculuğunuza bugün başlayın</h2>
            <p>Başvurun, ekibimiz en kısa sürede sizinle iletişime geçsin. Hiçbir yükümlülük altına girmezsiniz.</p>
            <div class="cta-ticks">
                <span>{!! $icon('check') !!}Uçtan uca, tek elden yönetim</span>
                <span>{!! $icon('check') !!}Her adım panelden şeffaf takip</span>
            </div>
        </div>
        <div class="cta-r">
            <h3>Başvuru</h3>
            <p>Formu doldurun; hedeflerinizi dinleyip size özel bir yol haritası çıkaralım.</p>
            <a href="{{ $applyUrl }}" class="btn btn-bronze" data-track="cta_clicked" data-ph-cta-name="footer_apply" data-ph-location="partner_ufuk_cta">Başvuru formunu aç {!! $icon('arrow') !!}</a>
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
                <span class="brand-mark">{{ mb_strtoupper(mb_substr($siteName, 0, 1)) }}</span>
                <span class="brand-name" style="font-size:19px;">{{ $siteName }}</span>
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
    <a href="{{ $waUrl }}" class="wa-float" target="_blank" rel="noopener" data-track="cta_clicked" data-ph-cta-name="whatsapp_float" data-ph-location="partner_ufuk_float">
        {!! $icon('wa') !!} WhatsApp
    </a>
@endif

</body>
</html>
