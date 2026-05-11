{{-- MentorDE favicon — tüm layout/blade'lerden @include('partials.favicon') ile çağrılır.
     SVG modern tarayıcılarda HD render eder, ICO eski/Chrome'un agresif cache'i için fallback,
     PNG mobile/touch. Cache-busting filemtime ile (dosya değişirse browser yeniden çeker). --}}
@php
    $faviconVer = @filemtime(public_path('favicon.svg')) ?: time();
    $iconVer    = @filemtime(public_path('favicon.ico')) ?: time();
@endphp
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ $faviconVer }}">
<link rel="icon" type="image/x-icon" sizes="32x32" href="{{ asset('favicon.ico') }}?v={{ $iconVer }}">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ $iconVer }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon.svg') }}?v={{ $faviconVer }}">
<meta name="theme-color" content="#7e58bf">
<meta name="msapplication-TileColor" content="#7e58bf">
