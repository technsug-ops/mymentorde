{{-- MentorDE favicon — tüm layout/blade'lerden @include('partials.favicon') ile çağrılır.
     Modern tarayıcılar (Chrome 80+, Firefox 41+, Safari 11+, Edge) SVG favicon destekler.
     Eski tarayıcılar için fallback /favicon.ico (boş — tarayıcı sessizce default kullanır). --}}
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=1">
<link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
<meta name="theme-color" content="#7e58bf">
