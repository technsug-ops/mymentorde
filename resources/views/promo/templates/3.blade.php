{{-- Template 3 — Premium (Lüks · Şık) — gold flourishes + ornamental frame + Brandenburg silhouette --}}

{{-- Sağ üst altın aksanı --}}
<svg class="promo-deco promo-deco-tr" width="280" height="280" viewBox="0 0 280 280" fill="none" xmlns="http://www.w3.org/2000/svg" opacity="0.4">
    <circle cx="220" cy="60" r="80" stroke="#fbbf24" stroke-width="1" fill="none"/>
    <circle cx="220" cy="60" r="50" stroke="#fbbf24" stroke-width="1" fill="none"/>
    <circle cx="220" cy="60" r="20" stroke="#fbbf24" stroke-width="1" fill="none"/>
</svg>

{{-- Sol alt: Brandenburg Gate altın silüet --}}
<svg class="promo-deco promo-deco-bl" width="260" height="180" viewBox="0 0 260 180" fill="none" xmlns="http://www.w3.org/2000/svg" opacity="0.18">
    <g transform="translate(40 70)" fill="#fbbf24">
        <rect x="0" y="80" width="160" height="40"/>
        <rect x="10" y="20" width="12" height="60"/>
        <rect x="30" y="20" width="12" height="60"/>
        <rect x="50" y="20" width="12" height="60"/>
        <rect x="70" y="20" width="12" height="60"/>
        <rect x="90" y="20" width="12" height="60"/>
        <rect x="110" y="20" width="12" height="60"/>
        <rect x="130" y="20" width="12" height="60"/>
        <rect x="148" y="20" width="12" height="60"/>
        <rect x="0" y="10" width="160" height="14"/>
        <rect x="55" y="-10" width="50" height="22"/>
    </g>
</svg>

<div class="promo-card-inner">
    {{-- Üst süsleme --}}
    <div style="text-align:center; margin-bottom: 12px; opacity: .7;">
        <svg width="120" height="14" viewBox="0 0 120 14" fill="none">
            <line x1="0" y1="7" x2="40" y2="7" stroke="#fbbf24" stroke-width="1"/>
            <circle cx="50" cy="7" r="2" fill="#fbbf24"/>
            <circle cx="60" cy="7" r="3" fill="#fbbf24"/>
            <circle cx="70" cy="7" r="2" fill="#fbbf24"/>
            <line x1="80" y1="7" x2="120" y2="7" stroke="#fbbf24" stroke-width="1"/>
        </svg>
        <div style="font-size: 10px; letter-spacing: 6px; color: #fbbf24; margin-top: 4px; font-weight: 700;">EXCLUSIVE</div>
    </div>

    <div class="promo-logo" style="justify-content:center; margin-bottom: 10px;">
        @if(!empty($logoUrl))
            <img class="logo-img" src="{{ $logoUrl }}" alt="{{ $brandName }}">
        @else
            <div class="logo-mark">M{{ mb_substr($brandAccent, 0, 1) }}</div>
            <div class="logo-text">{{ $brandShort }}<span class="accent">{{ $brandAccent }}</span></div>
        @endif
    </div>
    <div class="promo-tagline" style="text-align:center;">{{ $tagline }}</div>

    {{-- Almanya bayrak şeridi (altın aksanlı) --}}
    <div class="promo-flag-strip" style="opacity:.7;">
        <div class="promo-flag-black"></div>
        <div class="promo-flag-red"></div>
        <div class="promo-flag-gold"></div>
    </div>

    <div style="text-align:center;">
        <div class="promo-discount-bar">✦ {{ $discountText }} ✦</div>
    </div>

    <h1 class="promo-title" style="text-align:center;">{{ $title }}</h1>
    <p class="promo-subtitle" style="text-align:center;">{{ $subtitle }}</p>

    <div class="promo-code-box">
        <div class="promo-code-label">— Davet Kodu —</div>
        <div class="promo-code-value">{{ $code->code }}</div>
    </div>

    @if($expiryStr)
        <div class="promo-expiry" style="justify-content:center;">Geçerlilik <span class="pill">{{ $expiryStr }}</span></div>
    @endif

    <a href="{{ $applyUrl }}" class="promo-cta">{{ $ctaText }}</a>

    {{-- Alt süsleme --}}
    <div style="text-align:center; margin-top: 18px; opacity: .5;">
        <svg width="80" height="10" viewBox="0 0 80 10" fill="none">
            <line x1="0" y1="5" x2="30" y2="5" stroke="#fbbf24" stroke-width="1"/>
            <circle cx="40" cy="5" r="2" fill="#fbbf24"/>
            <line x1="50" y1="5" x2="80" y2="5" stroke="#fbbf24" stroke-width="1"/>
        </svg>
    </div>
    <div class="promo-disclaimer">{{ $disclaimer }}</div>
</div>
