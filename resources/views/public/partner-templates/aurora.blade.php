<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    /** Partner Template · AURORA — canlı gradient + cam efektli süreç paneli.
     *  Veri: App\Support\PartnerSiteData::forDealer() (tüm template'lerle paylaşılan sözleşme).
     *  İkon: App\Support\PartnerSiteData::icon(). CTA → /apply/partner/{code}. */
    $accent   = \App\Support\PartnerSiteData::accent($accentColor ?? null);
    $siteName = $brandName ?? config('brand.name', 'MentorDE');
    $icon     = fn (string $k) => \App\Support\PartnerSiteData::icon($k);

    // WhatsApp → wa.me (sadece rakam). Boşsa null.
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
<style>
:root{
    --primary:{{ $accent }};
    --primary-dark:color-mix(in srgb, {{ $accent }} 82%, #000);
    --primary-deep:color-mix(in srgb, {{ $accent }} 64%, #000);
    --primary-mid:color-mix(in srgb, {{ $accent }} 60%, #fff);
    --primary-soft:color-mix(in srgb, {{ $accent }} 9%, #fff);
    --primary-tint:color-mix(in srgb, {{ $accent }} 5%, #fff);
    --text:#171226; --muted:#6b6377; --line:#eae6f2; --bg:#faf9fc;
    --success:#16a34a; --amber:#f59e0b;
    --font-base:"Space Grotesk","Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,sans-serif;
    --shadow-sm:0 1px 2px rgba(23,18,38,.04),0 4px 16px rgba(23,18,38,.05);
    --shadow-md:0 8px 30px color-mix(in srgb,var(--primary) 12%,transparent);
    --shadow-lg:0 30px 60px color-mix(in srgb,var(--primary) 22%,transparent);
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;scroll-behavior:smooth;}
body{font-family:var(--font-base);color:var(--text);line-height:1.6;font-size:15px;background:var(--bg);
    -webkit-font-smoothing:antialiased;}
a{color:var(--primary);text-decoration:none;}
a:hover{text-decoration:underline;}
.container{max-width:1140px;margin:0 auto;padding:0 22px;}
svg{width:1em;height:1em;}
/* dekoratif nokta/grid deseni + blob yardımcıları */
.dots{position:absolute;inset:0;z-index:0;opacity:.5;pointer-events:none;
    background-image:radial-gradient(color-mix(in srgb,var(--primary) 22%,transparent) 1px,transparent 1px);
    background-size:22px 22px;-webkit-mask-image:radial-gradient(70% 60% at 50% 40%,#000,transparent 75%);mask-image:radial-gradient(70% 60% at 50% 40%,#000,transparent 75%);}
.blob{position:absolute;border-radius:50%;filter:blur(60px);opacity:.55;pointer-events:none;z-index:0;}

/* NAV */
.p-nav{position:sticky;top:0;z-index:50;background:rgba(255,255,255,.92);backdrop-filter:blur(10px);border-bottom:1px solid var(--line);}
.p-nav-inner{max-width:1120px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;padding:14px 22px;gap:16px;}
.p-logo{font-size:26px;color:var(--primary);letter-spacing:-.5px;font-weight:700;display:inline-flex;align-items:center;gap:8px;}
.p-logo img{max-height:40px;width:auto;display:block;}
.p-nav-links{display:flex;gap:24px;font-size:14px;font-weight:600;}
.p-nav-links a{color:var(--muted);}
.p-nav-links a:hover{color:var(--primary);text-decoration:none;}
.p-nav-cta{padding:10px 18px;background:var(--primary);color:#fff !important;border-radius:10px;font-size:13px;font-weight:700;}
.p-nav-cta:hover{background:var(--primary-dark);text-decoration:none !important;}
@media(max-width:760px){.p-nav-links{display:none;}}

/* HERO */
.hero{position:relative;overflow:hidden;padding:66px 0 96px;
    background:
      radial-gradient(60% 55% at 80% 8%,color-mix(in srgb,var(--primary) 20%,transparent),transparent 60%),
      radial-gradient(50% 50% at 8% 92%,color-mix(in srgb,var(--primary-mid) 22%,transparent),transparent 60%),
      linear-gradient(180deg,var(--primary-tint),#fff 70%);
    border-bottom:1px solid var(--line);}
.hero .blob.b1{width:420px;height:420px;top:-120px;right:-60px;background:color-mix(in srgb,var(--primary) 45%,transparent);}
.hero .blob.b2{width:340px;height:340px;bottom:-140px;left:-80px;background:color-mix(in srgb,var(--primary-mid) 55%,transparent);opacity:.4;}
.hero-grid{position:relative;z-index:1;display:grid;grid-template-columns:1.15fr .95fr;gap:48px;align-items:center;}
@media(max-width:920px){.hero-grid{grid-template-columns:1fr;gap:36px;}}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.75);backdrop-filter:blur(8px);
    border:1px solid color-mix(in srgb,var(--primary) 22%,transparent);color:var(--primary-dark);
    padding:7px 15px;border-radius:30px;font-size:12px;font-weight:700;letter-spacing:.03em;margin-bottom:22px;box-shadow:var(--shadow-sm);}
.hero-badge .pd{width:8px;height:8px;border-radius:50%;background:var(--success);box-shadow:0 0 0 0 color-mix(in srgb,var(--success) 60%,transparent);animation:pd 1.8s infinite;}
@keyframes pd{0%{box-shadow:0 0 0 0 color-mix(in srgb,var(--success) 55%,transparent);}70%{box-shadow:0 0 0 9px transparent;}100%{box-shadow:0 0 0 0 transparent;}}
.hero h1{font-weight:700;font-size:clamp(38px,5.4vw,60px);line-height:1.05;letter-spacing:-1.8px;margin:0 0 22px;color:var(--primary-deep);}
.hero h1 .grad{background:linear-gradient(120deg,var(--primary),var(--primary-mid));-webkit-background-clip:text;background-clip:text;color:transparent;}
.hero-lead{font-size:18px;color:var(--muted);margin:0 0 30px;max-width:540px;}
.hero-ctas{display:flex;gap:14px;flex-wrap:wrap;align-items:center;}
.btn-primary{display:inline-flex;align-items:center;gap:9px;padding:16px 32px;background:linear-gradient(120deg,var(--primary),var(--primary-dark));color:#fff !important;
    border-radius:14px;font-size:15px;font-weight:700;border:none;cursor:pointer;box-shadow:0 10px 24px color-mix(in srgb,var(--primary) 36%,transparent);transition:all .2s;}
.btn-primary:hover{transform:translateY(-2px);text-decoration:none !important;box-shadow:0 16px 34px color-mix(in srgb,var(--primary) 44%,transparent);}
.btn-ghost{display:inline-flex;align-items:center;gap:8px;padding:15px 26px;border:1.5px solid color-mix(in srgb,var(--primary) 35%,transparent);color:var(--primary) !important;
    border-radius:14px;font-size:15px;font-weight:700;background:rgba(255,255,255,.7);backdrop-filter:blur(6px);transition:all .2s;}
.btn-ghost:hover{background:#fff;text-decoration:none !important;border-color:var(--primary);}
.hero-trust{display:flex;align-items:center;gap:16px;margin-top:26px;flex-wrap:wrap;}
.hero-stars{color:var(--amber);font-size:16px;letter-spacing:2px;}
.hero-trust .ht-t{font-size:13px;color:var(--muted);}
.hero-trust .ht-t b{color:var(--text);}
.hero-trust .divd{width:1px;height:22px;background:var(--line);}

/* HERO VISUAL — cam efektli kart yığını + yüzen rozetler */
.hero-visual{position:relative;min-height:340px;}
.hero-visual img.photo{width:100%;height:100%;min-height:340px;object-fit:cover;display:block;border-radius:26px;box-shadow:var(--shadow-lg);}
.hv-card{position:relative;z-index:2;background:rgba(255,255,255,.72);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.8);
    border-radius:26px;padding:26px;box-shadow:var(--shadow-lg);}
.hv-card-head{display:flex;align-items:center;gap:12px;margin-bottom:18px;}
.hv-ic{width:46px;height:46px;border-radius:13px;background:linear-gradient(140deg,var(--primary),var(--primary-deep));color:#fff;
    display:flex;align-items:center;justify-content:center;font-size:22px;box-shadow:var(--shadow-md);}
.hv-card-head .hv-t{font-weight:800;color:var(--primary-deep);font-size:16px;line-height:1.15;}
.hv-card-head .hv-s{font-size:12px;color:var(--muted);}
.hv-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:12px;}
.hv-list li{display:flex;align-items:center;gap:11px;font-size:14px;font-weight:600;color:var(--text);
    background:#fff;border:1px solid var(--line);border-radius:12px;padding:11px 13px;box-shadow:var(--shadow-sm);}
.hv-list li .chk{width:24px;height:24px;border-radius:50%;background:color-mix(in srgb,var(--success) 15%,#fff);color:var(--success);
    display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;}
.hv-chip{position:absolute;z-index:3;background:#fff;border:1px solid var(--line);border-radius:14px;padding:11px 15px;
    box-shadow:var(--shadow-lg);display:flex;align-items:center;gap:10px;font-weight:700;font-size:13px;}
.hv-chip .em{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;color:#fff;}
.hv-chip.c1{top:-40px;right:14px;}
.hv-chip.c1 .em{background:linear-gradient(140deg,var(--success),#0f8a3c);}
.hv-chip.c2{bottom:-44px;left:8px;}
.hv-chip.c2 .em{background:linear-gradient(140deg,var(--amber),#d97706);}
.hv-chip .cs{font-size:11px;color:var(--muted);font-weight:600;}
@media(max-width:920px){.hv-chip.c1{right:6px;}.hv-chip.c2{left:6px;}}

/* SECTIONS */
section{padding:64px 0;}
.sec-bg-white{background:#fff;}
.sec-bg-soft{background:radial-gradient(90% 70% at 50% -10%,var(--primary-tint),#fff 65%);}
.sec-label{display:inline-flex;align-items:center;gap:8px;color:var(--primary-dark);background:var(--primary-soft);
    border:1px solid color-mix(in srgb,var(--primary) 16%,transparent);text-transform:uppercase;letter-spacing:.12em;
    font-size:11px;font-weight:800;margin-bottom:16px;padding:6px 13px;border-radius:30px;}
.sec-label::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--primary);}
.sec-title{font-size:clamp(28px,3.6vw,42px);line-height:1.12;color:var(--primary-deep);letter-spacing:-1px;margin:0 0 14px;max-width:760px;font-weight:700;}
.sec-lead{font-size:17px;color:var(--muted);max-width:660px;margin:0 0 44px;}
/* ortalı başlık bloğu */
.sec-head.center{text-align:center;display:flex;flex-direction:column;align-items:center;}
.sec-head.center .sec-title{margin-left:auto;margin-right:auto;}
.sec-head.center .sec-lead{margin-left:auto;margin-right:auto;}

/* SERVICES */
.svc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
@media(max-width:860px){.svc-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:540px){.svc-grid{grid-template-columns:1fr;}}
.svc{background:#fff;border:1px solid var(--line);border-radius:18px;padding:30px 26px;transition:all .2s;
    display:flex;flex-direction:column;position:relative;overflow:hidden;}
.svc::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--primary),var(--primary-deep));
    transform:scaleX(0);transform-origin:left;transition:transform .25s;}
.svc:hover{border-color:var(--primary);transform:translateY(-4px);box-shadow:0 16px 36px color-mix(in srgb,var(--primary) 14%,transparent);}
.svc:hover::before{transform:scaleX(1);}
.svc-icon{width:62px;height:62px;border-radius:17px;background:linear-gradient(140deg,var(--primary),var(--primary-deep));color:#fff;
    display:flex;align-items:center;justify-content:center;font-size:30px;margin-bottom:18px;
    box-shadow:0 8px 20px color-mix(in srgb,var(--primary) 28%,transparent);}
.svc h3{margin:0 0 9px;font-size:18px;color:var(--primary-deep);font-weight:700;}
.svc p{margin:0 0 14px;color:var(--muted);font-size:14px;line-height:1.55;}
.svc-items{list-style:none;padding:16px 0 0;margin:auto 0 0;border-top:1px solid var(--line);display:flex;flex-direction:column;gap:9px;}
.svc-items li{display:flex;align-items:flex-start;gap:9px;font-size:13px;color:var(--text);line-height:1.4;}
.svc-items li svg{width:17px;height:17px;color:var(--primary);flex-shrink:0;margin-top:1px;}

/* ABOUT + STATS */
.about-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:44px;align-items:center;}
@media(max-width:860px){.about-grid{grid-template-columns:1fr;gap:28px;}}
.about-text{font-size:16px;color:var(--text);line-height:1.7;white-space:pre-line;}
.stats{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.stat{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px 20px;text-align:center;position:relative;overflow:hidden;}
.stat::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--primary),var(--primary-deep));}
.stat-val{font-size:36px;font-weight:800;color:var(--primary-deep);line-height:1;letter-spacing:-.5px;}
.stat-lbl{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;font-weight:700;margin-top:8px;}

/* TEAM */
.team-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;}
@media(max-width:920px){.team-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:540px){.team-grid{grid-template-columns:1fr;}}
.team-card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:28px 20px;text-align:center;transition:all .2s;box-shadow:var(--shadow-sm);}
.team-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-md);border-color:color-mix(in srgb,var(--primary) 28%,transparent);}
.team-photo{width:92px;height:92px;border-radius:50%;object-fit:cover;margin:0 auto 15px;
    background:linear-gradient(140deg,var(--primary),var(--primary-deep));color:#fff;
    display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:800;
    box-shadow:0 0 0 4px #fff,0 0 0 6px color-mix(in srgb,var(--primary) 30%,transparent),var(--shadow-md);}
.team-card h3{margin:0 0 4px;font-size:16px;color:var(--primary-deep);}
.team-card p{margin:0;color:var(--muted);font-size:13px;}

/* BADGE / TRUST */
.trust{background:linear-gradient(140deg,var(--primary-soft),#fff);border:1px solid var(--line);border-radius:20px;
    padding:32px;display:flex;gap:20px;align-items:center;flex-wrap:wrap;justify-content:center;text-align:center;}
.trust-badge{display:inline-flex;align-items:center;gap:12px;background:#fff;border:2px solid var(--primary);
    border-radius:16px;padding:16px 24px;box-shadow:0 8px 24px color-mix(in srgb,var(--primary) 14%,transparent);}
.trust-badge svg{width:36px;height:36px;color:var(--primary);}
.trust-badge .tb-t{font-weight:800;color:var(--primary-deep);font-size:16px;line-height:1.2;}
.trust-badge .tb-s{font-size:12px;color:var(--muted);}

/* POWER STRIP — teknik altyapı / sistematik */
.power{background:linear-gradient(140deg,var(--primary-deep),var(--primary));position:relative;overflow:hidden;padding:52px 0;}
.power .dots{opacity:.25;-webkit-mask-image:none;mask-image:none;background-image:radial-gradient(rgba(255,255,255,.35) 1px,transparent 1px);}
.power-head{position:relative;z-index:1;text-align:center;color:#fff;margin-bottom:30px;}
.power-head h2{margin:0 0 8px;font-size:clamp(22px,3vw,30px);letter-spacing:-.6px;}
.power-head p{margin:0;opacity:.85;font-size:15px;}
.power-grid{position:relative;z-index:1;display:grid;grid-template-columns:repeat(4,1fr);gap:16px;}
@media(max-width:860px){.power-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:480px){.power-grid{grid-template-columns:1fr;}}
.power-card{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);border-radius:16px;padding:24px 20px;color:#fff;backdrop-filter:blur(6px);transition:all .2s;}
.power-card:hover{background:rgba(255,255,255,.16);transform:translateY(-3px);}
.power-ic{width:48px;height:48px;border-radius:13px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:14px;}
.power-card h3{margin:0 0 6px;font-size:16px;font-weight:700;}
.power-card p{margin:0;font-size:13px;opacity:.85;line-height:1.5;}

/* TESTIMONIALS */
.testi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
@media(max-width:860px){.testi-grid{grid-template-columns:1fr;}}
.testi{background:#fff;border:1px solid var(--line);border-radius:18px;padding:28px;box-shadow:var(--shadow-sm);position:relative;transition:all .2s;}
.testi:hover{transform:translateY(-3px);box-shadow:var(--shadow-md);border-color:color-mix(in srgb,var(--primary) 30%,transparent);}
.testi-stars{color:var(--amber);font-size:15px;letter-spacing:2px;margin-bottom:12px;}
.testi-q{font-size:14px;color:var(--text);line-height:1.65;margin:0 0 18px;}
.testi-who{display:flex;align-items:center;gap:12px;}
.testi-av{width:44px;height:44px;border-radius:50%;background:linear-gradient(140deg,var(--primary),var(--primary-deep));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;flex-shrink:0;}
.testi-name{font-weight:700;color:var(--primary-deep);font-size:14px;}
.testi-role{font-size:12px;color:var(--muted);}

/* PROCESS STEPS */
.proc{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;counter-reset:step;position:relative;}
/* bağlayıcı çizgi (masaüstü) */
.proc::before{content:'';position:absolute;top:26px;left:12%;right:12%;height:2px;
    background:repeating-linear-gradient(90deg,color-mix(in srgb,var(--primary) 40%,transparent) 0 8px,transparent 8px 16px);z-index:0;}
@media(max-width:920px){.proc{grid-template-columns:repeat(2,1fr);}.proc::before{display:none;}}
@media(max-width:540px){.proc{grid-template-columns:1fr;}}
.proc-step{position:relative;z-index:1;text-align:center;padding:0 10px;}
.proc-num{width:54px;height:54px;margin:0 auto 16px;border-radius:50%;background:linear-gradient(140deg,var(--primary),var(--primary-deep));
    color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:20px;
    box-shadow:0 10px 24px color-mix(in srgb,var(--primary) 34%,transparent);border:4px solid #fff;counter-increment:step;}
.proc-num::after{content:counter(step);}
.proc-step h3{margin:0 0 8px;font-size:16px;color:var(--primary-deep);font-weight:700;}
.proc-step p{margin:0;color:var(--muted);font-size:13px;line-height:1.55;}

/* WHY US */
.why{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;}
@media(max-width:860px){.why{grid-template-columns:1fr;}}
.why-card{display:flex;gap:14px;align-items:flex-start;background:#fff;border:1px solid var(--line);border-left:4px solid var(--primary);border-radius:14px;padding:22px;}
.why-ic{width:44px;height:44px;border-radius:12px;background:var(--primary-soft);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.why-card h3{margin:0 0 5px;font-size:15px;color:var(--primary-deep);}
.why-card p{margin:0;color:var(--muted);font-size:13px;line-height:1.5;}

/* STAT BAND */
.stat-band{background:linear-gradient(140deg,var(--primary),var(--primary-deep));border-radius:22px;padding:36px 28px;
    display:grid;grid-template-columns:repeat(var(--n,4),1fr);gap:20px;color:#fff;box-shadow:0 18px 40px color-mix(in srgb,var(--primary) 26%,transparent);}
@media(max-width:640px){.stat-band{grid-template-columns:1fr 1fr;}}
.stat-band .sb{text-align:center;}
.stat-band .sb-v{font-size:38px;font-weight:800;line-height:1;letter-spacing:-.5px;}
.stat-band .sb-l{font-size:12px;opacity:.9;text-transform:uppercase;letter-spacing:.06em;margin-top:8px;}

/* CONTACT / CTA */
.cta{background:linear-gradient(140deg,var(--primary-deep),var(--primary));color:#fff;padding:92px 0;text-align:center;position:relative;overflow:hidden;}
.cta::before{content:'';position:absolute;inset:0;z-index:0;
    background:radial-gradient(50% 60% at 15% 15%,rgba(255,255,255,.2),transparent 60%),radial-gradient(45% 55% at 85% 85%,rgba(255,255,255,.14),transparent 60%);}
.cta .dots{opacity:.22;-webkit-mask-image:none;mask-image:none;background-image:radial-gradient(rgba(255,255,255,.4) 1px,transparent 1px);}
.cta .container{position:relative;z-index:1;}
.cta-eyebrow{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.25);
    padding:7px 15px;border-radius:30px;font-size:12px;font-weight:700;letter-spacing:.04em;margin-bottom:20px;}
.cta h2{font-size:clamp(28px,4vw,44px);margin:0 0 16px;line-height:1.1;}
.cta p{font-size:18px;opacity:.92;margin:0 auto 34px;max-width:600px;}
.cta .btn-primary{background:#fff;color:var(--primary-deep) !important;font-size:17px;padding:18px 40px;font-weight:800;box-shadow:0 12px 32px rgba(0,0,0,.22);}
.cta .btn-primary:hover{background:#f3eefc;transform:translateY(-2px);}
.contacts{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-top:40px;font-size:14px;}
.contact{display:inline-flex;align-items:center;gap:10px;background:rgba(255,255,255,.12);padding:11px 18px;border-radius:10px;color:#fff;}
.contact a{color:#fff !important;text-decoration:underline;text-decoration-color:rgba(255,255,255,.4);}
.contact svg{width:18px;height:18px;flex-shrink:0;}

/* FOOTER */
footer{background:#1a0f2e;color:rgba(255,255,255,.7);padding:30px 0;font-size:13px;text-align:center;}
footer a{color:#fff;}
footer .pb{opacity:.6;font-size:12px;margin-top:6px;}
</style>
</head>
<body>

{{-- ═══ NAV ═══ --}}
<nav class="p-nav">
    <div class="p-nav-inner">
        <a href="#" class="p-logo" aria-label="{{ $siteName }}">
            @if($brandLogoUrl)
                <img src="{{ $brandLogoUrl }}" alt="{{ $siteName }}">
            @else
                {{ $siteName }}
            @endif
        </a>
        <div class="p-nav-links">
            <a href="#hizmetler">Hizmetler</a>
            <a href="#hakkimizda">Hakkımızda</a>
            @if(!empty($team))<a href="#ekip">Ekip</a>@endif
            <a href="#iletisim">İletişim</a>
        </div>
        <a href="{{ $applyUrl }}" class="p-nav-cta" data-track="cta_clicked" data-ph-cta-name="nav_apply" data-ph-location="partner_site_nav">Başvur →</a>
    </div>
</nav>

{{-- ═══ HERO ═══ --}}
<section class="hero">
    <span class="blob b1"></span><span class="blob b2"></span>
    <div class="dots"></div>
    <div class="container hero-grid">
        <div>
            <span class="hero-badge"><span class="pd"></span> {{ $siteName }} · Almanya Eğitim Danışmanlığı</span>
            <h1>{{ $heroTitle }}</h1>
            <p class="hero-lead">{{ $heroSubtitle }}</p>
            <div class="hero-ctas">
                <a href="{{ $applyUrl }}" class="btn-primary" data-track="cta_clicked" data-ph-cta-name="hero_apply" data-ph-location="partner_site_hero">Ücretsiz Danışmanlık Al {!! $icon('arrow') !!}</a>
                <a href="#hizmetler" class="btn-ghost">Hizmetlerimiz</a>
            </div>
            <div class="hero-trust">
                <span class="hero-stars">★★★★★</span>
                <span class="ht-t"><b>{{ $heroTrust['rating'] }}</b> memnuniyet</span>
                <span class="divd"></span>
                <span class="ht-t"><b>{{ $heroTrust['students'] }}</b> öğrenci yönlendirildi</span>
                <span class="divd"></span>
                <span class="ht-t"><b>{{ $heroTrust['success'] }}</b> vize başarısı</span>
            </div>
        </div>
        <div class="hero-visual">
            @if(!empty($dealer?->site_hero_image_path))
                <img class="photo" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($dealer->site_hero_image_path) }}" alt="{{ $siteName }}">
            @else
                {{-- Sistematik süreç takip paneli — güçlü teknik altyapının yansıması --}}
                <div class="hv-card">
                    <div class="hv-card-head">
                        <div class="hv-ic">{!! $icon('chart') !!}</div>
                        <div>
                            <div class="hv-t">Süreç Takip Paneli</div>
                            <div class="hv-s">Her adım dijital, anlık ve şeffaf</div>
                        </div>
                        <span style="margin-left:auto;display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:800;color:var(--success);background:color-mix(in srgb,var(--success) 12%,#fff);padding:5px 10px;border-radius:20px;"><span class="pd" style="background:var(--success)"></span>CANLI</span>
                    </div>
                    <ul class="hv-list">
                        <li><span class="chk">{!! $icon('check') !!}</span> Başvuru hazırlığı tamamlandı</li>
                        <li><span class="chk">{!! $icon('check') !!}</span> Üniversite kabulü alındı</li>
                        <li><span class="chk">{!! $icon('check') !!}</span> Vize dosyası onaylandı</li>
                    </ul>
                    <div style="margin-top:16px;">
                        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);font-weight:700;margin-bottom:7px;"><span>Genel ilerleme</span><span style="color:var(--primary-deep)">%86</span></div>
                        <div style="height:9px;border-radius:20px;background:var(--primary-soft);overflow:hidden;"><div style="width:86%;height:100%;border-radius:20px;background:linear-gradient(90deg,var(--primary),var(--primary-mid));"></div></div>
                    </div>
                </div>
                <div class="hv-chip c1"><span class="em">{!! $icon('check') !!}</span><span>Vize Onaylandı<br><span class="cs">systematik takip</span></span></div>
                <div class="hv-chip c2"><span class="em">{!! $icon('bolt') !!}</span><span>%100 Dijital<br><span class="cs">güçlü altyapı</span></span></div>
            @endif
        </div>
    </div>
</section>

{{-- ═══ GÜÇ / TEKNİK ALTYAPI — sistematik & başarı ═══ --}}
<section class="power">
    <div class="dots"></div>
    <div class="container">
        <div class="power-head">
            <h2>Güçlü Altyapı, Sistematik Süreç</h2>
            <p>Başarımızın arkasında teknoloji destekli, şeffaf ve ölçülebilir bir operasyon var.</p>
        </div>
        <div class="power-grid">
            <div class="power-card"><div class="power-ic">{!! $icon('chart') !!}</div><h3>Dijital Süreç Takibi</h3><p>Her öğrencinin başvuru, vize ve yerleşim adımı anlık olarak sistemde izlenir.</p></div>
            <div class="power-card"><div class="power-ic">{!! $icon('gear') !!}</div><h3>Sistematik Operasyon</h3><p>Standart adımlar ve kontrol listeleriyle hiçbir detay atlanmaz.</p></div>
            <div class="power-card"><div class="power-ic">{!! $icon('users') !!}</div><h3>Uzman & Deneyimli Ekip</h3><p>Almanya eğitim sistemine hakim, alanında uzman danışman kadrosu.</p></div>
            <div class="power-card"><div class="power-ic">{!! $icon('bolt') !!}</div><h3>Hızlı & Şeffaf İletişim</h3><p>Sorularınıza hızlı yanıt, sürecin her aşamasında net bilgilendirme.</p></div>
        </div>
    </div>
</section>

{{-- ═══ HİZMETLER ═══ --}}
<section id="hizmetler" class="sec-bg-white">
    <div class="container">
        <div class="sec-head center">
            <span class="sec-label">Hizmetler</span>
            <h2 class="sec-title">Almanya Eğitim Sürecinin Her Adımında</h2>
            <p class="sec-lead">Başvurudan yerleşime kadar tüm süreci profesyonel ekibimizle yönetiyoruz.</p>
        </div>
        <div class="svc-grid">
            @foreach($services as $s)
                <div class="svc">
                    <div class="svc-icon">{!! $icon($s['icon'] ?? 'default') !!}</div>
                    <h3>{{ $s['title'] }}</h3>
                    @if(!empty($s['desc']))<p>{{ $s['desc'] }}</p>@endif
                    @if(!empty($s['items']))
                        <ul class="svc-items">
                            @foreach($s['items'] as $it)
                                <li>{!! $icon('check') !!} {{ $it }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ SÜREÇ / NASIL ÇALIŞIR ═══ --}}
<section class="sec-bg-soft">
    <div class="container">
        <div class="sec-head center">
            <span class="sec-label">Nasıl Çalışır</span>
            <h2 class="sec-title">Başvurudan Almanya'ya, Adım Adım Yanınızdayız</h2>
            <p class="sec-lead">Süreci sizin için basitleştirdik — siz hedefinize odaklanın, gerisini biz halledelim.</p>
        </div>
        <div class="proc">
            <div class="proc-step"><div class="proc-num"></div><h3>Ücretsiz Değerlendirme</h3><p>Hedeflerinizi dinler, size en uygun üniversite ve program seçeneklerini çıkarırız.</p></div>
            <div class="proc-step"><div class="proc-num"></div><h3>Başvuru & Belgeler</h3><p>Üniversite ve dil okulu başvurularınızı, evrak hazırlığınızı uçtan uca yönetiriz.</p></div>
            <div class="proc-step"><div class="proc-num"></div><h3>Vize & Finans</h3><p>Vize randevusu, bloke hesap ve sigorta işlemlerinde adım adım rehberlik ederiz.</p></div>
            <div class="proc-step"><div class="proc-num"></div><h3>Almanya'da Yerleşim</h3><p>Konaklama, Anmeldung ve günlük yaşam desteğiyle yeni hayatınıza sorunsuz başlarsınız.</p></div>
        </div>
    </div>
</section>

{{-- ═══ HAKKIMIZDA ═══ --}}
<section id="hakkimizda" class="sec-bg-white">
    <div class="container">
        <span class="sec-label">Hakkımızda</span>
        <h2 class="sec-title">{{ $siteName }}</h2>
        <div class="about-text" style="max-width:760px;">{{ $aboutText }}</div>
        @if(!empty($stats))
            <div class="stat-band" style="--n:{{ min(count($stats), 4) }};margin-top:36px;">
                @foreach($stats as $st)
                    <div class="sb">
                        <div class="sb-v">{{ $st['value'] }}</div>
                        <div class="sb-l">{{ $st['label'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- ═══ NEDEN BİZ ═══ --}}
<section class="sec-bg-soft">
    <div class="container">
        <div class="sec-head center">
            <span class="sec-label">Neden Biz</span>
            <h2 class="sec-title">Doğru Rehberle Emin Adımlar</h2>
            <p class="sec-lead">Sadece başvuru değil, Almanya'daki ilk gününüze kadar güvenilir bir yol arkadaşı.</p>
        </div>
        <div class="why">
            <div class="why-card"><div class="why-ic">{!! $icon('cap') !!}</div><div><h3>Uçtan Uca Süreç</h3><p>Başvurudan yerleşime kadar tek elden, kesintisiz takip.</p></div></div>
            <div class="why-card"><div class="why-ic">{!! $icon('passport') !!}</div><div><h3>Uzman Ekip</h3><p>Almanya eğitim sistemine hakim, deneyimli danışman kadrosu.</p></div></div>
            <div class="why-card"><div class="why-ic">{!! $icon('home') !!}</div><div><h3>Yerinde Destek</h3><p>Almanya'ya vardığınızda da yanınızdayız — yalnız kalmazsınız.</p></div></div>
        </div>
    </div>
</section>

{{-- ═══ EKİP (opsiyonel) ═══ --}}
@if(!empty($team))
<section id="ekip" class="sec-bg-white">
    <div class="container">
        <div class="sec-head center">
            <span class="sec-label">Ekip</span>
            <h2 class="sec-title">Danışman Kadromuz</h2>
            <p class="sec-lead">Alanında uzman, deneyimli ve size özel ilgilenen bir ekip.</p>
        </div>
        <div class="team-grid">
            @foreach($team as $m)
                <div class="team-card">
                    @if(!empty($m['photo']))
                        <img class="team-photo" src="{{ $m['photo'] }}" alt="{{ $m['name'] }}">
                    @else
                        <div class="team-photo">{{ Str::upper(Str::substr($m['name'], 0, 1)) }}</div>
                    @endif
                    <h3>{{ $m['name'] }}</h3>
                    @if(!empty($m['title']))<p>{{ $m['title'] }}</p>@endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ═══ GÜVEN / ROZET ═══ --}}
@if($showBadge)
<section class="sec-bg-soft">
    <div class="container">
        <div class="trust">
            <div class="trust-badge">
                {!! $icon('default') !!}
                <div>
                    <div class="tb-t">MentorDE Yetkili Partneri</div>
                    <div class="tb-s">Resmi partner ağı üzerinden güvenli süreç</div>
                </div>
            </div>
            <p style="margin:0;color:var(--muted);font-size:15px;max-width:420px;">
                {{ $siteName }}, {{ config('brand.name', 'MentorDE') }} altyapısı ve uzman ekibiyle
                başvuru, vize ve yerleşim süreçlerinizi uçtan uca yönetir.
            </p>
        </div>
    </div>
</section>
@endif

{{-- ═══ REFERANSLAR / YORUMLAR ═══ --}}
<section class="sec-bg-white">
    <div class="container">
        <div class="sec-head center">
            <span class="sec-label">Öğrenci Yorumları</span>
            <h2 class="sec-title">Başarı Hikayeleriyle Büyüyoruz</h2>
            <p class="sec-lead">Yolculuğunu bizimle tamamlayan öğrencilerin deneyimleri.</p>
        </div>
        <div class="testi-grid">
            <div class="testi">
                <div class="testi-stars">★★★★★</div>
                <p class="testi-q">"Başvurudan vizeye kadar her adımda yanımdalardı. Süreç o kadar düzenliydi ki hiç stres yaşamadım — şu an Münih'te okuyorum."</p>
                <div class="testi-who"><div class="testi-av">E</div><div><div class="testi-name">Elif K.</div><div class="testi-role">TU München · Mühendislik</div></div></div>
            </div>
            <div class="testi">
                <div class="testi-stars">★★★★★</div>
                <p class="testi-q">"Panelden her aşamayı görebiliyordum, sistemli çalışıyorlar. Vize dosyam ilk seferde onaylandı. Kesinlikle tavsiye ederim."</p>
                <div class="testi-who"><div class="testi-av">B</div><div><div class="testi-name">Burak S.</div><div class="testi-role">RWTH Aachen · Informatik</div></div></div>
            </div>
            <div class="testi">
                <div class="testi-stars">★★★★★</div>
                <p class="testi-q">"Konaklama ve Anmeldung dahil her şeyde destek oldular. Almanya'ya geldiğimde yalnız hissetmedim. Teşekkürler!"</p>
                <div class="testi-who"><div class="testi-av">Z</div><div><div class="testi-name">Zeynep A.</div><div class="testi-role">Uni Köln · İşletme</div></div></div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ İLETİŞİM / BAŞVURU ═══ --}}
<section id="iletisim" class="cta">
    <div class="dots"></div>
    <div class="container">
        <span class="cta-eyebrow">{!! $icon('bolt') !!} Ücretsiz Ön Değerlendirme</span>
        <h2>Almanya Eğitim Yolculuğunuza Bugün Başlayın</h2>
        <p>Başvurun, ekibimiz en kısa sürede sizinle iletişime geçsin. İlk görüşme tamamen ücretsizdir.</p>
        <a href="{{ $applyUrl }}" class="btn-primary" data-track="cta_clicked" data-ph-cta-name="footer_apply" data-ph-location="partner_site_cta">Ücretsiz Danışmanlık Başvurusu {!! $icon('arrow') !!}</a>
        @if($waUrl || $phone || $address)
            <div class="contacts">
                @if($waUrl)
                    <span class="contact">{!! $icon('default') !!} <a href="{{ $waUrl }}" target="_blank" rel="noopener">WhatsApp</a></span>
                @endif
                @if($phone)
                    <span class="contact">{!! $icon('passport') !!} <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a></span>
                @endif
                @if($address)
                    <span class="contact">{!! $icon('home') !!} {{ $address }}</span>
                @endif
                @if($instagram)
                    <span class="contact">{!! $icon('default') !!} <a href="https://instagram.com/{{ ltrim($instagram, '@') }}" target="_blank" rel="noopener">{{ '@' . ltrim($instagram, '@') }}</a></span>
                @endif
            </div>
        @endif
    </div>
</section>

{{-- ═══ FOOTER ═══ --}}
<footer>
    <div class="container">
        © {{ now()->year }} {{ $siteName }} — Tüm hakları saklıdır.
        @if($showBadge)
            <div class="pb">Powered by <a href="https://panel.mentorde.com" target="_blank" rel="noopener">{{ config('brand.name', 'MentorDE') }}</a></div>
        @endif
    </div>
</footer>

</body>
</html>
