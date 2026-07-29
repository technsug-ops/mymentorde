{{--
    PARTNER TEMPLATE · ŞEFTALİ SABAHI
    Editoryal serif (Newsreader), sıcak şeftali/krem paleti, sola dayalı bölüm başlıkları,
    üst-çizgi (rule) kartlar. Kaynak tasarım: "Seftali Sabahi" (DC).

    Kurallar: JS YOK (S.S.S. <details>), font SADECE lokal, uydurma veri YOK.
    Bölümler modüler: seftali/sections/*.blade.php — sıra + aç/kapa $sections'tan.
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
    --accent-deep:color-mix(in srgb, var(--accent) 78%, #2a0f06);
    --soft:color-mix(in srgb, var(--accent) 18%, #fff);
    --tint:color-mix(in srgb, var(--accent) 7%, #fff);
    --line:color-mix(in srgb, var(--accent) 24%, #fff);
    --ink:color-mix(in srgb, var(--accent) 18%, #2e1a14);
    --ink-2:color-mix(in srgb, var(--accent) 26%, #4a352c);
    --muted:color-mix(in srgb, var(--accent) 26%, #7c6559);
    --faint:color-mix(in srgb, var(--accent) 40%, #a3897c);
    --on-ink:color-mix(in srgb, var(--accent) 8%, #fdf6f2);
    --on-ink-soft:color-mix(in srgb, var(--accent) 32%, #bfa79a);
    --serif:"Newsreader","DM Serif Display",Georgia,serif;
    --body:"Public Sans","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --mono:"IBM Plex Mono",ui-monospace,SFMono-Regular,monospace;
    --maxw:1300px;
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;scroll-behavior:smooth;}
body{background:var(--tint);color:var(--ink);font-family:var(--body);font-size:15px;line-height:1.6;-webkit-font-smoothing:antialiased;}
svg{width:1em;height:1em;}
img{max-width:100%;}
a{color:var(--accent-deep);}
.wrap{max-width:var(--maxw);margin:0 auto;padding:0 26px;}
.sec{padding:58px 0 60px;}
.lbl{font:500 12px/1 var(--mono);letter-spacing:.18em;color:var(--accent-deep);text-transform:uppercase;}
h1,h2,h3{margin:0;}
/* Bölüm başlıkları SOLA dayalı — editoryal karakterin özü */
.sec-head{max-width:620px;margin-bottom:40px;}
.h2{font:400 clamp(27px,3.7vw,38px)/1.14 var(--serif);margin:14px 0 0;text-wrap:balance;}
.sec-head p{font-size:15px;line-height:1.65;color:var(--muted);margin:12px 0 0;}

/* ─── Butonlar ─── */
.btn{display:inline-flex;align-items:center;gap:9px;text-decoration:none;font:700 15px/1 var(--body);padding:16px 30px;border-radius:10px;transition:background .15s ease,transform .15s ease,filter .15s ease;}
.btn-dark{background:var(--ink);color:var(--on-ink);}
.btn-dark:hover{background:var(--ink-2);transform:translateY(-2px);}
.btn-accent{background:var(--accent);color:#fff;}
.btn-accent:hover{filter:brightness(.94);transform:translateY(-1px);}

/* ─── Nav ─── */
.nav{position:sticky;top:0;z-index:50;background:color-mix(in srgb, var(--tint) 90%, transparent);backdrop-filter:blur(12px);border-bottom:1px solid var(--line);}
.nav-in{max-width:var(--maxw);margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:17px 26px;}
.brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--ink);}
.brand-name{font:500 24px/1 var(--serif);}
.brand img{max-height:40px;width:auto;display:block;}
.nav-links{display:flex;align-items:center;gap:26px;flex-wrap:wrap;}
.nav-links a{text-decoration:none;font:600 13px/1 var(--body);color:var(--muted);}
.nav-links a:hover{color:var(--accent-deep);}
.nav-cta{background:var(--accent);color:#fff !important;font-weight:700;padding:12px 20px;border-radius:9px;}
.nav-cta:hover{filter:brightness(.94);}
@media(max-width:760px){.nav-links .nav-link{display:none;}}

/* ─── Hero ─── */
.hero{max-width:var(--maxw);margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:44px;padding:56px 26px 62px;align-items:end;}
.hero h1{font:400 clamp(36px,5.8vw,60px)/1.06 var(--serif);letter-spacing:-1px;margin:20px 0;text-wrap:balance;}
.hero p{font-size:17px;line-height:1.65;color:var(--muted);margin:0 0 28px;max-width:520px;}
.hero-btns{display:flex;gap:16px;align-items:center;flex-wrap:wrap;}
.hero-fig{position:relative;aspect-ratio:3/4;border-radius:16px;overflow:hidden;max-width:460px;width:100%;justify-self:end;}
.hero-fig img{width:100%;height:100%;object-fit:cover;display:block;}
.hero-note{font:600 14px/1.4 var(--body);color:var(--muted);}
.hero-note b{color:var(--ink);}

/* ─── Koyu bant (alıntı + istatistik) ─── */
.band{background:var(--ink);color:var(--on-ink);}
.band-in{max-width:var(--maxw);margin:0 auto;padding:52px 26px;display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:44px;align-items:center;}
.band blockquote{font:400 clamp(21px,2.6vw,27px)/1.4 var(--serif);margin:0;text-wrap:balance;}
.band cite{display:block;margin-top:18px;font:600 13px/1 var(--body);color:color-mix(in srgb, var(--accent) 60%, #fff);font-style:normal;}
.band-stats{display:flex;flex-wrap:wrap;gap:22px 30px;}
.band-stats>div{min-width:150px;}
.band-stats .v{font:400 34px/1 var(--serif);color:color-mix(in srgb, var(--accent) 76%, #fff);}
.band-stats .l{font:600 11px/1.3 var(--body);color:var(--on-ink-soft);text-transform:uppercase;letter-spacing:.07em;margin-top:6px;}
.band-div{border-left:1px solid color-mix(in srgb, var(--accent) 40%, transparent);padding-left:36px;}

/* ─── Üniversite satırı ─── */
.unis{max-width:var(--maxw);margin:0 auto;padding:34px 26px;display:flex;align-items:center;gap:32px;flex-wrap:wrap;border-bottom:1px solid var(--line);}
.unis-lbl{font:500 11px/1.3 var(--mono);color:var(--faint);text-transform:uppercase;letter-spacing:.08em;}
.unis .u{font:400 18px/1 var(--serif);color:color-mix(in srgb, var(--accent) 52%, var(--faint));}

/* ─── Izgara + üst-çizgi kart (rule card) ─── */
.grid{display:flex;flex-wrap:wrap;justify-content:center;gap:var(--gap,28px);--cols:3;--min:250px;}
.grid>*{flex:0 1 calc((100% - (var(--cols) - 1) * var(--gap,28px)) / var(--cols));min-width:min(var(--min),100%);}
.rule{border-top:2px solid var(--accent);padding-top:20px;display:flex;flex-direction:column;transition:transform .2s ease;}
.rule:hover{transform:translateY(-3px);}
.rule-ic{width:46px;height:46px;border-radius:12px;background:var(--soft);color:var(--accent-deep);display:flex;align-items:center;justify-content:center;margin-bottom:14px;font-size:24px;}
.rule h3{font:700 18px/1.25 var(--body);margin:0 0 8px;}
.rule p{font-size:13.5px;line-height:1.6;color:var(--muted);margin:0 0 14px;}
.ticks{list-style:none;padding:0;margin:auto 0 0;display:flex;flex-direction:column;gap:8px;}
.ticks li{display:flex;align-items:flex-start;gap:9px;font-size:13px;line-height:1.4;color:var(--ink-2);}
.ticks svg{color:var(--accent);flex-shrink:0;margin-top:2px;font-size:15px;}
.card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:28px;transition:transform .2s ease,border-color .2s ease;}
.card:hover{transform:translateY(-3px);border-color:var(--accent);}

/* ─── Süreç (şeftali zemin, büyük serif numara) ─── */
.proc-wrap{background:var(--soft);}
.step .n{font:400 46px/1 var(--serif);color:color-mix(in srgb, var(--accent) 66%, #fff);margin-bottom:12px;}
.step h3{font:700 16px/1.25 var(--body);margin:0 0 8px;}
.step p{font-size:13.5px;line-height:1.55;color:var(--muted);margin:0;}

/* ─── Yorum / ekip ─── */
.quote p{font:400 16px/1.6 var(--serif);color:var(--ink-2);margin:0 0 20px;}
.who{display:flex;align-items:center;gap:12px;}
.avatar{width:44px;height:44px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font:700 16px/1 var(--body);flex-shrink:0;overflow:hidden;}
.avatar img{width:100%;height:100%;object-fit:cover;}
.who b{display:block;font:700 14px/1.1 var(--body);}
.who span{font:500 12px/1.2 var(--mono);color:var(--faint);}
.member{background:#fff;border:1px solid var(--line);border-radius:14px;padding:22px;display:flex;align-items:center;gap:14px;}
.member .avatar{width:50px;height:50px;font-size:18px;}
.trust{background:var(--ink);border-radius:14px;padding:22px;display:flex;align-items:center;gap:14px;}
.trust-ic{width:50px;height:50px;border-radius:12px;background:color-mix(in srgb, var(--accent) 30%, transparent);color:color-mix(in srgb, var(--accent) 62%, #fff);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:25px;}
.trust b{font:700 15px/1.2 var(--body);color:var(--on-ink);}
.trust p{font-size:12.5px;line-height:1.5;color:var(--on-ink-soft);margin:4px 0 0;}

/* ─── Paketler ─── */
.pkg{background:#fff;border:1px solid var(--line);border-radius:16px;padding:30px 28px;display:flex;flex-direction:column;transition:transform .2s ease,border-color .2s ease;}
.pkg:hover{transform:translateY(-3px);border-color:var(--accent);}
.pkg-top{display:flex;align-items:baseline;justify-content:space-between;gap:12px;}
.pkg h3{font:400 26px/1.2 var(--serif);margin:0;}
.pkg-tag{font:500 10.5px/1 var(--mono);letter-spacing:.12em;text-transform:uppercase;color:var(--faint);}
.pkg-rule{width:44px;height:2px;background:var(--accent);margin:14px 0 16px;}
.pkg p{font-size:13.5px;line-height:1.62;color:var(--muted);margin:0 0 18px;}
.pkg .ticks{margin:0 0 22px;}
.pkg-btn{margin-top:auto;text-decoration:none;text-align:center;background:var(--soft);color:var(--accent-deep);font:700 14px/1 var(--body);padding:15px;border-radius:10px;transition:filter .15s ease;}
.pkg-btn:hover{filter:brightness(.95);}
.pkg-hi{background:var(--ink);border-color:var(--ink);}
.pkg-hi h3{color:var(--on-ink);}
.pkg-hi p{color:var(--on-ink-soft);}
.pkg-hi .pkg-tag{color:color-mix(in srgb, var(--accent) 60%, #fff);}
.pkg-hi .ticks li{color:var(--on-ink);}
.pkg-hi .pkg-btn{background:var(--accent);color:#fff;}

/* ─── S.S.S. (JS'siz) ─── */
.faq{max-width:900px;}
.faq details{border-bottom:1px solid var(--line);}
.faq summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:21px 8px;font:600 16px/1.35 var(--body);transition:background .15s ease;}
.faq summary::-webkit-details-marker{display:none;}
.faq summary:hover{background:var(--soft);}
.faq summary .ico{width:28px;height:28px;flex-shrink:0;border-radius:50%;background:var(--soft);color:var(--accent-deep);display:flex;align-items:center;justify-content:center;font:600 19px/1 var(--body);transition:transform .15s ease;}
.faq details[open] summary .ico{transform:rotate(45deg);}
.faq p{font-size:14.5px;line-height:1.65;color:var(--muted);margin:0;padding:0 8px 22px;}

/* ─── CTA ─── */
.cta{background:var(--ink);border-radius:20px;padding:52px 48px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:44px;align-items:center;}
.cta h2{font:400 clamp(27px,3.7vw,38px)/1.14 var(--serif);margin:16px 0 14px;color:var(--on-ink);text-wrap:balance;}
.cta .lbl{color:color-mix(in srgb, var(--accent) 62%, #fff);}
.cta-l>p{font-size:15.5px;line-height:1.65;color:var(--on-ink-soft);margin:0;}
.cta-r{background:var(--tint);border-radius:14px;padding:28px;}
.cta-r h3{font:400 24px/1.2 var(--serif);margin:0 0 8px;}
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
.foot-dim{font:500 12px/1.5 var(--mono);color:color-mix(in srgb, var(--faint) 76%, #fff);}
.foot-soc a{color:var(--faint);display:inline-flex;font-size:19px;transition:color .15s ease,transform .15s ease;}
.foot-soc a:hover{color:var(--accent);transform:translateY(-2px);}

/* ─── WhatsApp ─── */
.wa-float{position:fixed;right:24px;bottom:24px;z-index:60;text-decoration:none;display:inline-flex;align-items:center;gap:10px;background:var(--accent);color:#fff;font:700 14px/1 var(--body);padding:14px 18px;border-radius:30px;box-shadow:0 14px 30px color-mix(in srgb, var(--accent) 40%, transparent);transition:transform .18s ease;}
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
                <span class="brand-name">{{ $siteName }}</span>
            @endif
        </a>
        <div class="nav-links">
            @foreach($navLinks as $nl)
                <a href="{{ $nl['href'] }}" class="nav-link">{{ $nl['label'] }}</a>
            @endforeach
            <a href="{{ $applyUrl }}" class="nav-cta" data-track="cta_clicked" data-ph-cta-name="nav_apply" data-ph-location="partner_seftali_nav">Başvur</a>
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
            <a href="{{ $applyUrl }}" class="btn btn-dark" data-track="cta_clicked" data-ph-cta-name="hero_apply" data-ph-location="partner_seftali_hero">Ücretsiz danışmanlık al</a>
            {{-- Yıldız/puan iddiası YOK: yalnız partnerin girdiği ilk istatistik --}}
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
    @includeIf('public.partner-templates.seftali.sections.' . $sectionKey)
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
            <a href="{{ $applyUrl }}" class="btn btn-accent" data-track="cta_clicked" data-ph-cta-name="footer_apply" data-ph-location="partner_seftali_cta">Başvuru formunu aç {!! $icon('arrow') !!}</a>
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
                <span class="brand-name" style="font-size:21px;">{{ $siteName }}</span>
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
    <a href="{{ $waUrl }}" class="wa-float" target="_blank" rel="noopener" data-track="cta_clicked" data-ph-cta-name="whatsapp_float" data-ph-location="partner_seftali_float">
        {!! $icon('wa') !!} WhatsApp
    </a>
@endif

</body>
</html>
