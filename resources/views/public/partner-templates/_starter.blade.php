{{--
    PARTNER TEMPLATE İSKELETİ — kopyala, yeniden adlandır, tasarımını giydir.

    Bu dosya PartnerTemplates::TEMPLATES'a KAYITLI DEĞİL, yani hiçbir zaman public
    olarak render edilmez. Amacı: yeni bir şablon yazarken veri sözleşmesinin tamamını
    tek bakışta görmek ve hiçbir alanı atlamamak.

    Yeni şablon eklemek (2 adım):
      1) cp _starter.blade.php {key}.blade.php + cp -r _starter/sections {key}/sections
         → iskelete tasarımın CSS'ini, her bölüm partial'ına markup'ını giydir
      2) App\Support\PartnerTemplates::TEMPLATES dizisine satır ekle:
         {key} => [name, desc, accent, modular => true, sections => [...]]
    Detaylı kontrol listesi: docs/PARTNER_TEMPLATE_EKLEME.md

    KURALLAR (hepsi zorunlu):
      • JS YOK — inline <script>/onclick kullanma (public sayfa, CSP + basitlik).
      • Font SADECE lokal — Google Fonts CDN'e istek atma (DSGVO). local-fonts.css yeterli.
      • UYDURMA VERİ YOK — örnek yorum/rakam yazma; veri boşsa bölümü hiç basma.
      • İçerik $dealer'dan gelir; sabit metin yalnız genel başlık/etiketler için.
      • Marka: $brandName / $brandLogoUrl — config('brand.name') sadece MentorDE rozetinde.

    SÖZLEŞME (App\Support\PartnerSiteData::forDealer):
      $dealer         Dealer modeli (site_hero_image_path gibi ham alanlar için)
      $brandName      string   partner firma adı
      $brandLogoUrl   ?string  logo URL'i (yoksa null → metin logo kullan)
      $accentColor    string   doğrulanmış hex (#rrggbb)
      $heroTitle      string   her zaman dolu
      $heroSubtitle   string   her zaman dolu
      $aboutText      string   her zaman dolu (satır sonları için white-space:pre-line)
      $services       list     [title, desc, icon, items[]]  — her zaman ≥1 (default set var)
      $stats          list     [value, label]                — BOŞ OLABİLİR
      $team           list     [name, title, photo]          — BOŞ OLABİLİR
      $testimonials   list     [text, name, school]          — BOŞ OLABİLİR
      $heroTrust      list     [value, label] (max 3, $stats'tan) — BOŞ OLABİLİR
      $steps          list     [no, title, desc]              — her zaman 4 (firmadan bağımsız)
      $whyUs          list     [icon, title, desc]            — her zaman 4 (firmadan bağımsız)
      $packages       list     [name, tag, desc, items[], featured] — BOŞ OLABİLİR
      $packageNote    string   paket bölümü açıklaması ('' ise basma; paket yoksa hep '')
      $faq            list     [q, a]                         — her zaman ≥1 (default set var)
      $universities   list     string ("TU München", ...)      — BOŞ OLABİLİR
      $showBadge      bool     false ise MentorDE adı sayfada HİÇ geçmemeli (tam white-label)
      $phone $whatsapp $instagram $address   ?string — BOŞ OLABİLİR
      $applyUrl       string   lead formu (/apply/partner/{code}) — tüm CTA'lar buraya
      $sections       list     açık bölüm anahtarları — partnerin seçtiği SIRAYLA (partial include et)
      $navLinks       list     [href, label] — menüyü BURADAN bas (kapalı bölümün linki olmaz)

    İKONLAR: PartnerSiteData::icon() — cap, passport, coins, home, check, arrow, chart, bolt,
    shield, gear, users, clock, star, work, pin, phone, wa, instagram, default
--}}
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    /** Partner Template · STARTER — tasarımını buraya giydir. */
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
{{-- Fontlar SADECE lokal (DSGVO): Google Fonts CDN'e istek atma --}}
<link rel="stylesheet" href="{{ asset('fonts/local-fonts.css') }}">
<style>
:root{ --accent:{{ $accent }}; }
/* ↓↓↓ Tasarımın CSS'i buraya. Renkleri var(--accent) üzerinden türet ki
       partner kendi kurumsal rengini seçtiğinde tüm site onunla boyansın. ↓↓↓ */
body{margin:0;font-family:"Plus Jakarta Sans",-apple-system,sans-serif;line-height:1.6;}
.wrap{max-width:1080px;margin:0 auto;padding:0 24px;}
svg{width:1em;height:1em;}
</style>
</head>
<body>

{{-- ═══ NAV ═══ --}}
<nav>
    <div class="wrap">
        <a href="#">
            @if($brandLogoUrl)
                <img src="{{ $brandLogoUrl }}" alt="{{ $siteName }}" style="max-height:40px;">
            @else
                {{ $siteName }}
            @endif
        </a>
        {{-- Menü linkleri SÖZLEŞMEDEN gelir: kapalı/boş bölümün linki çıkmaz --}}
        @foreach($navLinks as $nl)<a href="{{ $nl['href'] }}">{{ $nl['label'] }}</a>@endforeach
        <a href="{{ $applyUrl }}" data-track="cta_clicked" data-ph-cta-name="nav_apply" data-ph-location="partner_starter_nav">Başvur</a>
    </div>
</nav>

{{-- ═══ HERO ═══ --}}
<section class="wrap">
    <h1>{{ $heroTitle }}</h1>
    <p>{{ $heroSubtitle }}</p>
    <a href="{{ $applyUrl }}" data-track="cta_clicked" data-ph-cta-name="hero_apply" data-ph-location="partner_starter_hero">Ücretsiz Danışmanlık Al {!! $icon('arrow') !!}</a>

    @if(!empty($heroTrust))
        {{-- Sadece partnerin kendi girdiği istatistikler — uydurma rakam yok. --}}
        <div>
            @foreach($heroTrust as $ht)
                <span><b>{{ $ht['value'] }}</b> {{ $ht['label'] }}</span>
            @endforeach
        </div>
    @endif

    @if(!empty($dealer?->site_hero_image_path))
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($dealer->site_hero_image_path) }}" alt="{{ $siteName }}" style="max-width:100%;">
    @endif
</section>

{{-- ═══ SIRALANABİLİR BÖLÜMLER ═══
    Partnerin seçtiği sıra + aç/kapa: $sections (App\Support\PartnerSiteSections).
    Her bölüm ayrı partial: _starter/sections/{key}.blade.php → kendi şablonunda
    {key}/sections/*.blade.php olarak kopyala. @includeIf: eksik partial sayfayı düşürmez.
    Partial'lar buradaki @php değişkenlerini ($icon, $accent, ...) otomatik görür. --}}
@foreach($sections as $sectionKey)
    @includeIf('public.partner-templates._starter.sections.' . $sectionKey)
@endforeach

{{-- ═══ MENTORDE ROZETİ — kapalıysa MentorDE adı sayfada hiç geçmemeli ═══ --}}
@if($showBadge)
<section class="wrap">
    {!! $icon('shield') !!}
    <b>{{ config('brand.name', 'MentorDE') }} Yetkili Partneri</b>
    <span>Resmi partner ağı üzerinden güvenli, şeffaf süreç.</span>
</section>
@endif

{{-- ═══ İLETİŞİM / BAŞVURU ═══ --}}
<section id="iletisim" class="wrap">
    <h2>Almanya eğitim yolculuğunuza bugün başlayın</h2>
    <p>Başvurun, ekibimiz en kısa sürede sizinle iletişime geçsin. İlk görüşme tamamen ücretsizdir.</p>
    <a href="{{ $applyUrl }}" data-track="cta_clicked" data-ph-cta-name="footer_apply" data-ph-location="partner_starter_cta">Ücretsiz Danışmanlık Başvurusu {!! $icon('arrow') !!}</a>

    <div>
        @if($waUrl)<span>{!! $icon('default') !!} <a href="{{ $waUrl }}" target="_blank" rel="noopener">WhatsApp</a></span>@endif
        @if($phone)<span>{!! $icon('passport') !!} <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a></span>@endif
        @if($address)<span>{!! $icon('home') !!} {{ $address }}</span>@endif
        @if($instagram)<span>{!! $icon('default') !!} <a href="https://instagram.com/{{ ltrim($instagram, '@') }}" target="_blank" rel="noopener">{{ '@' . ltrim($instagram, '@') }}</a></span>@endif
    </div>
</section>

{{-- ═══ FOOTER ═══ --}}
<footer class="wrap">
    <span>© {{ now()->year }} {{ $siteName }}</span>
    @if($showBadge)<span>Powered by <a href="https://panel.mentorde.com" target="_blank" rel="noopener">{{ config('brand.name', 'MentorDE') }}</a></span>@endif
</footer>

</body>
</html>
