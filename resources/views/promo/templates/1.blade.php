{{-- Template 1 — Classic (Profesyonel) — Almanya bayrak şeridi + dot pattern + diploma SVG --}}

{{-- Sağ üst dekoratif: diploma + dünya --}}
<svg class="promo-deco promo-deco-tr" width="220" height="220" viewBox="0 0 220 220" fill="none" xmlns="http://www.w3.org/2000/svg">
    <circle cx="160" cy="60" r="80" fill="rgba(255,255,255,0.06)"/>
    <circle cx="160" cy="60" r="50" fill="rgba(255,255,255,0.05)"/>
    <g transform="translate(130 30)" opacity="0.5">
        {{-- Mezuniyet kepi --}}
        <path d="M30 30 L60 22 L90 30 L60 38 Z" fill="rgba(255,255,255,0.5)"/>
        <rect x="55" y="38" width="10" height="20" fill="rgba(255,255,255,0.4)"/>
        <line x1="60" y1="58" x2="60" y2="68" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
        <circle cx="60" cy="70" r="3" fill="rgba(255,255,255,0.5)"/>
    </g>
</svg>

{{-- Sol alt dekoratif: brandenburg gate silhouette --}}
<svg class="promo-deco promo-deco-bl" width="240" height="180" viewBox="0 0 240 180" fill="none" xmlns="http://www.w3.org/2000/svg" opacity="0.18">
    <g transform="translate(40 50)">
        {{-- Brandenburg Gate stylized --}}
        <rect x="0" y="80" width="160" height="50" fill="white"/>
        <rect x="10" y="10" width="14" height="80" fill="white"/>
        <rect x="32" y="10" width="14" height="80" fill="white"/>
        <rect x="54" y="10" width="14" height="80" fill="white"/>
        <rect x="76" y="10" width="14" height="80" fill="white"/>
        <rect x="98" y="10" width="14" height="80" fill="white"/>
        <rect x="120" y="10" width="14" height="80" fill="white"/>
        <rect x="142" y="10" width="14" height="80" fill="white"/>
        <rect x="0" y="0" width="160" height="14" fill="white"/>
        {{-- Quadriga (dört atlı araba simgesi) --}}
        <rect x="60" y="-20" width="40" height="22" fill="white"/>
        <circle cx="78" cy="-8" r="3" fill="rgba(255,255,255,0.6)"/>
    </g>
</svg>

<div class="promo-card-inner">
    {{-- Logo --}}
    <div class="promo-logo">
        @if(!empty($logoUrl))
            <img class="logo-img" src="{{ $logoUrl }}" alt="{{ $brandName }}">
        @else
            <div class="logo-mark">M{{ mb_substr($brandAccent, 0, 1) }}</div>
            <div class="logo-text">{{ $brandShort }}<span class="accent">{{ $brandAccent }}</span></div>
        @endif
    </div>
    <div class="promo-tagline">{{ $tagline }}</div>

    {{-- Almanya bayrak şeridi --}}
    <div class="promo-flag-strip">
        <div class="promo-flag-black"></div>
        <div class="promo-flag-red"></div>
        <div class="promo-flag-gold"></div>
    </div>

    <div class="promo-discount-bar">{{ $discountText }}</div>
    <h1 class="promo-title">{{ $title }}</h1>
    <p class="promo-subtitle">{{ $subtitle }}</p>

    <div class="promo-code-box">
        <div class="promo-code-label">İndirim Kodu</div>
        <div class="promo-code-value">{{ $code->code }}</div>
    </div>

    @if($expiryStr)
        <div class="promo-expiry">⏰ Geçerlilik: <span class="pill">{{ $expiryStr }}</span></div>
    @endif

    <a href="{{ $applyUrl }}" class="promo-cta">{{ $ctaText }} →</a>
    <div class="promo-disclaimer">{{ $disclaimer }}</div>
</div>
