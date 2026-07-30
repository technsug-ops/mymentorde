{{--
    PARTNER TEMPLATE · MERMER
    Rafine dergi/editoryel düzen: Playfair Display serif, krem-greige zemin, altın ikincil
    vurgu, ince çizgi ayraçlar, yapışkan (sticky) bölüm başlığı + numaralı hizmet satırları.
    Kaynak tasarım: "Mermer" (DC).

    Kurallar: JS YOK (S.S.S. <details>), font SADECE lokal, uydurma veri YOK.
    Bölümler modüler: mermer/sections/*.blade.php — sıra + aç/kapa $sections'tan.
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
    --accent-deep:color-mix(in srgb, var(--accent) 82%, #05100c);
    --accent-soft:color-mix(in srgb, var(--accent) 9%, #f6f4ee);
    /* Altın ikincil vurgu accent'ten türetilir */
    --gold:color-mix(in srgb, var(--accent) 20%, #b5892f);
    --paper:color-mix(in srgb, var(--accent) 3%, #f5f3ed);
    --paper-2:color-mix(in srgb, var(--accent) 5%, #efece2);
    --line:color-mix(in srgb, var(--accent) 8%, #e0dbcd);
    --ink:color-mix(in srgb, var(--accent) 10%, #221f18);
    --ink-2:color-mix(in srgb, var(--accent) 12%, #4b463c);
    --muted:color-mix(in srgb, var(--accent) 12%, #6c665a);
    --faint:color-mix(in srgb, var(--accent) 16%, #9a9384);
    --serif:"Playfair Display","DM Serif Display",Georgia,serif;
    --body:"Public Sans","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --mono:"IBM Plex Mono",ui-monospace,SFMono-Regular,monospace;
    --maxw:1240px;
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;scroll-behavior:smooth;}
body{background:var(--paper);color:var(--ink);font-family:var(--body);font-size:15px;line-height:1.65;-webkit-font-smoothing:antialiased;}
svg{width:1em;height:1em;}
img{max-width:100%;}
a{color:var(--accent-deep);}
.wrap{max-width:var(--maxw);margin:0 auto;padding:0 26px;}
.sec{padding:62px 0 58px;}
h1,h2,h3{margin:0;}
.lbl{font:600 11px/1 var(--mono);letter-spacing:.22em;color:var(--gold);text-transform:uppercase;}
.h2{font:500 clamp(27px,3.7vw,38px)/1.16 var(--serif);margin:16px 0 14px;text-wrap:balance;}
.rule-s{width:64px;height:2px;background:var(--gold);margin:0 0 20px;}
.sec-head{max-width:620px;margin-bottom:38px;}
.sec-head.center{margin:0 auto 38px;text-align:center;}
.sec-head p{font-size:14.5px;line-height:1.68;color:var(--muted);margin:0;}

/* ─── Butonlar (küçük radius = rafine) ─── */
.btn{display:inline-flex;align-items:center;gap:9px;text-decoration:none;font:700 14px/1 var(--body);padding:16px 30px;border-radius:7px;letter-spacing:.02em;transition:filter .15s ease,transform .15s ease,border-color .15s ease;}
.btn-accent{background:var(--accent);color:#fff;}
.btn-accent:hover{filter:brightness(1.08);transform:translateY(-2px);}
.btn-line{border:1px solid var(--line);color:var(--ink);background:transparent;}
.btn-line:hover{border-color:var(--gold);}

/* ─── Nav ─── */
.nav{position:sticky;top:0;z-index:50;background:color-mix(in srgb, var(--paper) 92%, transparent);backdrop-filter:blur(10px);border-bottom:1px solid var(--line);}
.nav-in{max-width:var(--maxw);margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:17px 26px;}
.brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--ink);}
.brand-name{font:500 23px/1 var(--serif);}
.brand img{max-height:40px;width:auto;display:block;}
.nav-links{display:flex;align-items:center;gap:26px;flex-wrap:wrap;}
.nav-links a{text-decoration:none;font:600 13px/1 var(--body);color:var(--muted);letter-spacing:.02em;}
.nav-links a:hover{color:var(--accent-deep);}
.nav-cta{background:var(--accent);color:#fff !important;font-weight:700;padding:11px 18px;border-radius:6px;}
.nav-cta:hover{filter:brightness(1.08);}
@media(max-width:760px){.nav-links .nav-link{display:none;}}

/* ─── Hero (asimetrik editoryel) ─── */
.hero{max-width:var(--maxw);margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:52px;align-items:center;padding:58px 26px 62px;}
.hero h1{font:500 clamp(34px,5.4vw,56px)/1.1 var(--serif);letter-spacing:-.5px;margin:22px 0 20px;text-wrap:balance;}
.hero p{font-size:16.5px;line-height:1.68;color:var(--muted);margin:0 0 30px;max-width:500px;}
.hero-btns{display:flex;gap:13px;align-items:center;flex-wrap:wrap;}
.hero-fig{position:relative;aspect-ratio:4/5;overflow:hidden;max-width:470px;width:100%;justify-self:end;border:1px solid var(--line);}
.hero-fig img{width:100%;height:100%;object-fit:cover;display:block;}
.hero-note{font:600 13px/1.4 var(--body);color:var(--muted);}
.hero-note b{font-family:var(--serif);font-weight:500;font-size:19px;color:var(--accent-deep);}

/* ─── İnce çizgi istatistik satırı ─── */
.hair-row{border-top:1px solid var(--line);border-bottom:1px solid var(--line);background:var(--paper-2);}
.hair-row-in{max-width:var(--maxw);margin:0 auto;padding:34px 26px;display:flex;flex-wrap:wrap;gap:24px;}
.hair-row-in>div{flex:1 1 190px;text-align:center;border-right:1px solid var(--line);padding-right:12px;}
.hair-row-in>div:last-child{border-right:0;}
.hair-row .v{font:500 clamp(27px,3.5vw,36px)/1 var(--serif);color:var(--accent);}
.hair-row .l{font:600 10.5px/1.3 var(--mono);color:var(--muted);text-transform:uppercase;letter-spacing:.1em;margin-top:8px;}

/* ─── Merkez alıntı ─── */
.center-quote{max-width:860px;margin:0 auto;text-align:center;}
.center-quote blockquote{font:400 clamp(21px,2.7vw,30px)/1.45 var(--serif);color:var(--ink);margin:22px 0 20px;text-wrap:balance;}
.center-quote cite{font:600 12px/1 var(--mono);color:var(--gold);font-style:normal;letter-spacing:.12em;text-transform:uppercase;}

/* ─── Hizmetler: sticky başlık + numaralı satırlar ─── */
.split{display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:52px;align-items:start;}
.split-head{position:sticky;top:96px;}
.num-row{display:grid;grid-template-columns:50px minmax(200px,1fr);gap:18px;padding:26px 4px;border-bottom:1px solid var(--line);transition:background .15s ease;}
.num-row:hover{background:var(--paper-2);}
.num-row .no{font:500 17px/1 var(--mono);color:var(--gold);padding-top:4px;}
.num-row h3{font:700 17px/1.25 var(--body);margin:0 0 7px;}
.num-row p{font-size:13.5px;line-height:1.62;color:var(--muted);margin:0 0 10px;}
.num-row .items{display:flex;gap:14px;flex-wrap:wrap;}
.num-row .items span{font:500 12.5px/1.3 var(--mono);color:var(--ink-2);}
.num-row .items i{color:var(--gold);font-style:normal;margin-right:5px;}

/* ─── Kart ızgarası ─── */
.grid{display:flex;flex-wrap:wrap;justify-content:center;gap:var(--gap,20px);--cols:3;--min:260px;}
.grid>*{flex:0 1 calc((100% - (var(--cols) - 1) * var(--gap,20px)) / var(--cols));min-width:min(var(--min),100%);}
.card{background:#fff;border:1px solid var(--line);padding:28px 26px;display:flex;flex-direction:column;transition:border-color .2s ease,transform .2s ease;}
.card:hover{border-color:var(--gold);transform:translateY(-3px);}
.card h3{font:500 21px/1.25 var(--serif);margin:0 0 10px;}
.card p{font-size:14px;line-height:1.65;color:var(--muted);margin:0;}
.card-ic{width:44px;height:44px;border-radius:4px;background:var(--accent-soft);color:var(--accent-deep);display:flex;align-items:center;justify-content:center;margin-bottom:14px;font-size:22px;}

/* ─── Süreç ─── */
.step{border-top:2px solid var(--accent);padding-top:18px;}
.step .n{font:500 15px/1 var(--mono);color:var(--gold);}
.step h3{font:500 20px/1.25 var(--serif);margin:12px 0 8px;}
.step p{font-size:13.5px;line-height:1.65;color:var(--muted);margin:0;}

/* ─── Yorum / ekip ─── */
.quote p{font:400 16.5px/1.65 var(--serif);color:var(--ink-2);margin:0 0 18px;}
.who{display:flex;align-items:center;gap:12px;}
.avatar{width:44px;height:44px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font:500 17px/1 var(--serif);flex-shrink:0;overflow:hidden;}
.avatar img{width:100%;height:100%;object-fit:cover;}
.who b{display:block;font:700 13.5px/1.1 var(--body);}
.who span{font:500 11.5px/1.2 var(--mono);color:var(--faint);}
.member{background:#fff;border:1px solid var(--line);padding:22px;display:flex;align-items:center;gap:14px;}
.member .avatar{width:50px;height:50px;}
.trust{background:var(--ink);padding:22px;display:flex;align-items:center;gap:14px;}
.trust-ic{width:50px;height:50px;border-radius:4px;background:color-mix(in srgb, var(--gold) 30%, transparent);color:color-mix(in srgb, var(--gold) 70%, #fff);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:24px;}
.trust b{font:500 17px/1.2 var(--serif);color:var(--paper);}
.trust p{font-size:12.5px;line-height:1.5;color:color-mix(in srgb, var(--paper) 62%, var(--ink));margin:4px 0 0;}

/* ─── Paketler ─── */
.pkg{background:#fff;border:1px solid var(--line);padding:32px 28px;display:flex;flex-direction:column;transition:border-color .2s ease,transform .2s ease;}
.pkg:hover{border-color:var(--gold);transform:translateY(-3px);}
.pkg-top{display:flex;align-items:baseline;justify-content:space-between;gap:12px;}
.pkg h3{font:500 26px/1.2 var(--serif);margin:0;}
.pkg-tag{font:600 10px/1 var(--mono);letter-spacing:.14em;text-transform:uppercase;color:var(--gold);}
.pkg .rule-s{margin:14px 0 16px;}
.pkg p{font-size:13.5px;line-height:1.65;color:var(--muted);margin:0 0 18px;}
.ticks{list-style:none;padding:0;margin:0 0 22px;display:flex;flex-direction:column;gap:10px;}
.ticks li{display:flex;align-items:flex-start;gap:10px;font-size:13px;line-height:1.5;color:var(--ink-2);}
.ticks svg{color:var(--gold);flex-shrink:0;margin-top:2px;font-size:15px;}
.pkg-btn{margin-top:auto;text-decoration:none;text-align:center;background:var(--accent-soft);color:var(--accent-deep);font:700 13.5px/1 var(--body);padding:15px;border-radius:6px;transition:filter .15s ease;}
.pkg-btn:hover{filter:brightness(.97);}
.pkg-hi{background:var(--ink);border-color:var(--ink);}
.pkg-hi h3{color:var(--paper);}
.pkg-hi p{color:color-mix(in srgb, var(--paper) 62%, var(--ink));}
.pkg-hi .pkg-tag{color:color-mix(in srgb, var(--gold) 76%, #fff);}
.pkg-hi .ticks li{color:color-mix(in srgb, var(--paper) 82%, var(--ink));}
.pkg-hi .ticks svg{color:color-mix(in srgb, var(--gold) 76%, #fff);}
.pkg-hi .pkg-btn{background:var(--gold);color:#fff;}

/* ─── S.S.S. (JS'siz) ─── */
.faq{max-width:880px;margin:0 auto;}
.faq details{border-bottom:1px solid var(--line);}
.faq summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:22px 6px;font:500 19px/1.4 var(--serif);transition:background .15s ease;}
.faq summary::-webkit-details-marker{display:none;}
.faq summary:hover{background:var(--paper-2);}
.faq summary .ico{font:500 20px/1 var(--mono);color:var(--gold);flex-shrink:0;transition:transform .15s ease;}
.faq details[open] summary .ico{transform:rotate(45deg);}
.faq p{font-size:14.5px;line-height:1.75;color:var(--muted);margin:0;padding:0 6px 24px;}

/* ─── CTA ─── */
.cta{background:var(--ink);padding:56px 48px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:44px;align-items:center;}
.cta .lbl{color:color-mix(in srgb, var(--gold) 76%, #fff);}
.cta h2{font:400 clamp(27px,3.6vw,38px)/1.16 var(--serif);margin:16px 0 14px;color:var(--paper);text-wrap:balance;}
.cta-l>p{font-size:15px;line-height:1.7;color:color-mix(in srgb, var(--paper) 62%, var(--ink));margin:0;}
.cta-r{background:var(--paper);padding:30px;}
.cta-r h3{font:500 23px/1.25 var(--serif);margin:0 0 8px;}
.cta-r>p{font-size:13.5px;line-height:1.65;color:var(--muted);margin:0 0 18px;}
.cta-r .btn{width:100%;justify-content:center;}
.contact{list-style:none;padding:18px 0 0;margin:18px 0 0;border-top:1px solid var(--line);display:flex;flex-direction:column;gap:11px;}
.contact li{display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--muted);}
.contact svg{color:var(--gold);flex-shrink:0;font-size:17px;}
.contact a{color:var(--ink);text-decoration:none;font-weight:600;}
.contact a:hover{color:var(--accent-deep);}

/* ─── Footer ─── */
.foot{border-top:1px solid var(--line);}
.foot-in{max-width:var(--maxw);margin:0 auto;padding:32px 26px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;}
.foot-txt{font:500 12.5px/1.5 var(--mono);color:var(--faint);}
.foot-dim{font:500 11.5px/1.5 var(--mono);color:color-mix(in srgb, var(--faint) 76%, var(--paper));}
.foot-soc a{color:var(--faint);display:inline-flex;font-size:19px;transition:color .15s ease,transform .15s ease;}
.foot-soc a:hover{color:var(--gold);transform:translateY(-2px);}

/* ─── WhatsApp ─── */
.wa-float{position:fixed;right:24px;bottom:24px;z-index:60;text-decoration:none;display:inline-flex;align-items:center;gap:10px;background:var(--ink);color:var(--paper);font:700 13.5px/1 var(--body);padding:15px 19px;border-radius:6px;box-shadow:0 14px 30px color-mix(in srgb, var(--ink) 26%, transparent);transition:transform .18s ease;}
.wa-float:hover{transform:translateY(-3px);}
.wa-float svg{color:color-mix(in srgb, var(--gold) 76%, #fff);font-size:19px;}
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
            <a href="{{ $applyUrl }}" class="nav-cta" data-track="cta_clicked" data-ph-cta-name="nav_apply" data-ph-location="partner_mermer_nav">Başvuru</a>
        </div>
    </div>
</div>

{{-- ═══ HERO ═══ --}}
<div class="hero">
    <div>
        <span class="lbl">Almanya · Eğitim Danışmanlığı</span>
        <h1>{{ $heroTitle }}</h1>
        <div class="rule-s"></div>
        <p>{{ $heroSubtitle }}</p>
        <div class="hero-btns">
            <a href="{{ $applyUrl }}" class="btn btn-accent" data-track="cta_clicked" data-ph-cta-name="hero_apply" data-ph-location="partner_mermer_hero">Ücretsiz danışmanlık</a>
            {{-- Ölçülemez iddia yok: yalnız partnerin girdiği ilk istatistik --}}
            @if(!empty($heroTrust))
                <span class="hero-note"><b>{{ $heroTrust[0]['value'] }}</b> {{ $heroTrust[0]['label'] }}</span>
            @endif
        </div>
    </div>
    @if($heroImg)
        <figure class="hero-fig" style="margin:0;"><img src="{{ $heroImg }}" alt="{{ $siteName }}"></figure>
    @endif
</div>

{{-- ═══ SIRALANABİLİR BÖLÜMLER ═══ --}}
@foreach($sections as $sectionKey)
    @includeIf('public.partner-templates.mermer.sections.' . $sectionKey)
@endforeach

{{-- ═══ BAŞVURU / İLETİŞİM ═══ --}}
<div id="basvuru" class="wrap" style="padding:8px 26px 72px;">
    <div class="cta">
        <div class="cta-l">
            <span class="lbl">Ücretsiz ön görüşme</span>
            <h2>Yolculuğunuza bugün başlayın</h2>
            <p>Başvurun, ekibimiz en kısa sürede sizinle iletişime geçsin. Hiçbir yükümlülük altına girmezsiniz.</p>
        </div>
        <div class="cta-r">
            <h3>Başvuru</h3>
            <p>Formu doldurun; hedeflerinizi dinleyip size özel bir yol haritası çıkaralım.</p>
            <a href="{{ $applyUrl }}" class="btn btn-accent" data-track="cta_clicked" data-ph-cta-name="footer_apply" data-ph-location="partner_mermer_cta">Başvuru formunu aç {!! $icon('arrow') !!}</a>
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
                <span class="brand-name" style="font-size:20px;">{{ $siteName }}</span>
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
    <a href="{{ $waUrl }}" class="wa-float" target="_blank" rel="noopener" data-track="cta_clicked" data-ph-cta-name="whatsapp_float" data-ph-location="partner_mermer_float">
        {!! $icon('wa') !!} WhatsApp
    </a>
@endif

</body>
</html>
