{{--
    PARTNER TEMPLATE İSKELETİ — kopyala, yeniden adlandır, tasarımını giydir.

    Bu dosya PartnerTemplates::TEMPLATES'a KAYITLI DEĞİL, yani hiçbir zaman public
    olarak render edilmez. Amacı: yeni bir şablon yazarken veri sözleşmesinin tamamını
    tek bakışta görmek ve hiçbir alanı atlamamak.

    Yeni şablon eklemek (2 adım):
      1) cp _starter.blade.php {key}.blade.php  → içine tasarımın CSS + markup'ını giydir
      2) App\Support\PartnerTemplates::TEMPLATES dizisine satır ekle: {key} => [name, desc, accent]
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

{{-- ═══ HİZMETLER (her zaman dolu) ═══ --}}
<section id="hizmetler" class="wrap">
    <h2>Hizmetlerimiz</h2>
    @foreach($services as $s)
        <article>
            <div style="color:var(--accent);">{!! $icon($s['icon'] ?? 'default') !!}</div>
            <h3>{{ $s['title'] }}</h3>
            <p>{{ $s['desc'] }}</p>
            @if(!empty($s['items']))
                <ul>
                    @foreach($s['items'] as $item)<li>{{ $item }}</li>@endforeach
                </ul>
            @endif
        </article>
    @endforeach
</section>

{{-- ═══ ÜNİVERSİTE ŞERİDİ (boşsa hiç basma — uydurma üniversite yazma) ═══ --}}
@if(!empty($universities))
<section class="wrap">
    <span>Öğrencilerimizin yerleştiği üniversiteler</span>
    @foreach($universities as $u)<span>{{ $u }}</span>@endforeach
</section>
@endif

{{-- ═══ SÜREÇ (her zaman dolu) ═══ --}}
<section id="surec" class="wrap">
    <h2>Nasıl Çalışır</h2>
    @foreach($steps as $st)
        <article><div>{{ $st['no'] }}</div><h3>{{ $st['title'] }}</h3><p>{{ $st['desc'] }}</p></article>
    @endforeach
</section>

{{-- ═══ NEDEN BİZ (her zaman dolu) ═══ --}}
<section class="wrap">
    <h2>Neden Biz</h2>
    @foreach($whyUs as $w)
        <article><div style="color:var(--accent);">{!! $icon($w['icon']) !!}</div><h3>{{ $w['title'] }}</h3><p>{{ $w['desc'] }}</p></article>
    @endforeach
</section>

{{-- ═══ HAKKIMIZDA + İSTATİSTİK ═══ --}}
<section class="wrap">
    <h2>Hakkımızda</h2>
    <p style="white-space:pre-line;">{{ $aboutText }}</p>

    @if(!empty($stats))
        <div>
            @foreach($stats as $st)
                <div><b>{{ $st['value'] }}</b> <span>{{ $st['label'] }}</span></div>
            @endforeach
        </div>
    @endif
</section>

{{-- ═══ EKİP (boşsa hiç basma) ═══ --}}
@if(!empty($team))
<section class="wrap">
    <h2>Ekibimiz</h2>
    @foreach($team as $m)
        <div>
            @if(($m['photo'] ?? '') !== '')
                <img src="{{ $m['photo'] }}" alt="{{ $m['name'] }}" style="width:72px;height:72px;border-radius:50%;object-fit:cover;">
            @else
                <div style="width:72px;height:72px;border-radius:50%;color:var(--accent);">{{ mb_substr($m['name'], 0, 1) }}</div>
            @endif
            <div>{{ $m['name'] }}</div>
            @if(($m['title'] ?? '') !== '')<div>{{ $m['title'] }}</div>@endif
        </div>
    @endforeach
</section>
@endif

{{-- ═══ YORUMLAR — sadece partnerin girdiği GERÇEK yorumlar ═══ --}}
@if(!empty($testimonials))
<section class="wrap">
    <h2>Öğrenci Yorumları</h2>
    @foreach($testimonials as $t)
        <figure>
            <blockquote>{{ $t['text'] }}</blockquote>
            @if(($t['name'] ?? '') !== '' || ($t['school'] ?? '') !== '')
                <figcaption>
                    @if(($t['name'] ?? '') !== '')<b>{{ $t['name'] }}</b>@endif
                    @if(($t['school'] ?? '') !== '') — {{ $t['school'] }}@endif
                </figcaption>
            @endif
        </figure>
    @endforeach
</section>
@endif

{{-- ═══ PAKETLER (boşsa hiç basma — default paket ÜRETME) ═══ --}}
@if(!empty($packages))
<section id="paketler" class="wrap">
    <h2>Destek Paketleri</h2>
    @if($packageNote !== '')<p>{{ $packageNote }}</p>@endif
    @foreach($packages as $p)
        <article @if(!empty($p['featured'])) style="border:2px solid var(--accent);" @endif>
            <h3>{{ $p['name'] }}</h3>
            @if(($p['tag'] ?? '') !== '')<span>{{ $p['tag'] }}</span>@endif
            @if(($p['desc'] ?? '') !== '')<p>{{ $p['desc'] }}</p>@endif
            @if(!empty($p['items']))
                <ul>@foreach($p['items'] as $item)<li>{{ $item }}</li>@endforeach</ul>
            @endif
            <a href="{{ $applyUrl }}" data-track="cta_clicked" data-ph-cta-name="package_apply" data-ph-location="partner_starter_packages">Bu paketi görüşelim</a>
        </article>
    @endforeach
</section>
@endif

{{-- ═══ S.S.S. — JS YOK: <details>/<summary> ile aç/kapa ═══ --}}
@if(!empty($faq))
<section id="sss" class="wrap">
    <h2>Sıkça Sorulan Sorular</h2>
    @foreach($faq as $f)
        <details><summary>{{ $f['q'] }}</summary><p>{{ $f['a'] }}</p></details>
    @endforeach
</section>
@endif

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
