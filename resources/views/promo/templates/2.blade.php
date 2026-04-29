{{-- Template 2 — Bold --}}

{{-- Sağ üst confetti --}}
<svg class="promo-deco promo-deco-tr" width="280" height="240" viewBox="0 0 280 240" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <circle cx="220" cy="50" r="65" fill="rgba(255,255,255,0.13)"/>
    <circle cx="220" cy="50" r="38" fill="rgba(255,255,255,0.1)"/>
    <rect x="180" y="20" width="9" height="3" fill="#fde047" transform="rotate(20 184 22)"/>
    <rect x="240" y="80" width="9" height="3" fill="#fff" transform="rotate(-30 244 82)"/>
    <rect x="200" y="100" width="9" height="3" fill="#fde047" transform="rotate(45 204 102)"/>
    <circle cx="160" cy="60" r="3" fill="white"/>
    <circle cx="250" cy="30" r="4" fill="#fde047"/>
    <circle cx="190" cy="120" r="3" fill="white"/>
    <path d="M260 110 L268 118" stroke="#fde047" stroke-width="2" stroke-linecap="round"/>
    <path d="M155 90 L163 82" stroke="white" stroke-width="2" stroke-linecap="round"/>
</svg>

{{-- Sol alt sun rays --}}
<svg class="promo-deco promo-deco-bl" width="280" height="280" viewBox="0 0 280 280" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <g transform="translate(50 200)" opacity="0.85">
        <circle cx="0" cy="0" r="50" fill="rgba(255,255,255,0.12)"/>
        <circle cx="0" cy="0" r="28" fill="rgba(255,255,255,0.18)"/>
        @for($i = 0; $i < 12; $i++)
            <line x1="0" y1="0" x2="{{ cos($i * M_PI/6) * 78 }}" y2="{{ sin($i * M_PI/6) * 78 }}"
                  stroke="rgba(255,255,255,0.22)" stroke-width="3" stroke-linecap="round"/>
        @endfor
    </g>
</svg>

{{-- Sağ alt: paper plane --}}
<svg class="promo-deco promo-deco-br" width="180" height="160" viewBox="0 0 180 160" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <g transform="translate(50 30)" opacity="0.42">
        <path d="M0 50 L100 0 L80 50 L100 100 Z" fill="white"/>
        <path d="M0 50 L80 50 L100 100" stroke="rgba(255,255,255,0.55)" stroke-width="1" fill="none"/>
        <path d="M-30 70 Q-15 55 0 50" stroke="rgba(255,255,255,0.5)" stroke-width="2" fill="none" stroke-dasharray="3 4"/>
    </g>
</svg>

<div class="promo-card-inner">
    <div class="promo-header">
        <div class="promo-logo">
            @if(!empty($logoUrl))
                <span class="promo-logo-img-wrap"><img class="logo-img" src="{{ $logoUrl }}" alt="{{ $brandName }}"></span>
            @else
                <div class="logo-mark">M{{ mb_substr($brandAccent, 0, 1) }}</div>
                <div class="logo-text">{{ $brandShort }}<span class="accent">{{ $brandAccent }}</span></div>
            @endif
        </div>
        <div class="promo-tagline">{{ $tagline }}</div>
    </div>

    <div class="promo-discount-bar">🔥 {{ $discountText }}</div>
    <h1 class="promo-title">{{ $title }}</h1>
    <p class="promo-subtitle">{{ $subtitle }}</p>

    <div class="promo-code-box">
        <div class="promo-code-label">🎟️ Kuponun</div>
        <div class="promo-code-value">{{ $code->code }}</div>
    </div>

    @if($expiryStr)
        <div class="promo-expiry">⏰ <span class="pill">{{ $expiryStr }}</span> tarihine kadar</div>
    @endif

    <a href="{{ $applyUrl }}" class="promo-cta">🚀 {{ $ctaText }}</a>
    <div class="promo-disclaimer">{{ $disclaimer }}</div>
</div>
