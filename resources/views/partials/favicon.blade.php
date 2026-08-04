{{-- Favicon — tüm layout/blade'lerden @include('partials.favicon') ile çağrılır.
     SVG modern tarayıcılarda HD render eder, ICO eski/Chrome'un agresif cache'i için fallback,
     PNG mobile/touch. Cache-busting filemtime ile (dosya değişirse browser yeniden çeker).

     WHITE-LABEL: public/favicon.* dosyaları PLATFORMUN (MentorDE) ikonlarıdır.
     Kendi markası olan bir şirket için bunları basmak sekme ikonunda marka sızıntısı
     olurdu — o yüzden yalnızca ana şirkette kullanılır. Partner firma kendi ikonunu
     `brand_overrides.favicon_url` ile verir; vermediyse tarayıcının varsayılanı kalır. --}}
@php
    $_favUrl   = trim((string) config('brand.favicon_url', ''));
    $_favOwn   = $_favUrl !== '' && $_favUrl !== '/favicon.ico';
    // Şirket kendi rengini seçtiyse onu kullan; aksi halde platformun mevcut tonu
    // korunur (config/brand.php'nin env varsayılanı marka tercihi değildir).
    $_favTheme = config('brand.theme.primary_source') === 'company'
        ? (string) config('brand.theme.primary')
        : '#7e58bf';

    // Platformun kendi ikon setini yalnızca marka paketi .env'den geldiğinde bas.
    // Tenant'ta Brand::stripPlatformIdentity() favicon_url'i boşaltır.
    $_favPlatform = !$_favOwn && $_favUrl !== '';
@endphp
@if($_favOwn)
    <link rel="icon" href="{{ $_favUrl }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $_favUrl }}">
@elseif($_favPlatform)
    @php
        $faviconVer = @filemtime(public_path('favicon.svg')) ?: time();
        $iconVer    = @filemtime(public_path('favicon.ico')) ?: time();
    @endphp
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ $faviconVer }}">
    <link rel="icon" type="image/x-icon" sizes="32x32" href="{{ asset('favicon.ico') }}?v={{ $iconVer }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ $iconVer }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon.svg') }}?v={{ $faviconVer }}">
@endif
<meta name="theme-color" content="{{ $_favTheme }}">
<meta name="msapplication-TileColor" content="{{ $_favTheme }}">
