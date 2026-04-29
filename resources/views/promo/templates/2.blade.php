{{-- Template 2 — Bold (Canlı · Genç) — confetti + sun rays + emoji burst --}}

{{-- Sağ üst confetti --}}
<svg class="promo-deco promo-deco-tr" width="280" height="280" viewBox="0 0 280 280" fill="none" xmlns="http://www.w3.org/2000/svg">
    <circle cx="220" cy="50" r="70" fill="rgba(255,255,255,0.12)"/>
    <circle cx="220" cy="50" r="40" fill="rgba(255,255,255,0.1)"/>
    {{-- Confetti pieces --}}
    <rect x="180" y="20" width="8" height="3" fill="#fde047" transform="rotate(20 184 22)"/>
    <rect x="240" y="80" width="8" height="3" fill="#fff" transform="rotate(-30 244 82)"/>
    <rect x="200" y="100" width="8" height="3" fill="#fde047" transform="rotate(45 204 102)"/>
    <circle cx="160" cy="60" r="3" fill="white"/>
    <circle cx="250" cy="30" r="4" fill="#fde047"/>
    <circle cx="190" cy="120" r="3" fill="white"/>
    <path d="M260 110 L268 102 L260 110 L268 118" stroke="#fde047" stroke-width="2" stroke-linecap="round"/>
    <path d="M155 90 L163 82" stroke="white" stroke-width="2" stroke-linecap="round"/>
</svg>

{{-- Sol alt sun rays --}}
<svg class="promo-deco promo-deco-bl" width="280" height="280" viewBox="0 0 280 280" fill="none" xmlns="http://www.w3.org/2000/svg" opacity="0.85">
    <g transform="translate(40 200)">
        <circle cx="0" cy="0" r="60" fill="rgba(255,255,255,0.12)"/>
        <circle cx="0" cy="0" r="35" fill="rgba(255,255,255,0.15)"/>
        @for($i = 0; $i < 12; $i++)
            <line x1="0" y1="0" x2="{{ cos($i * M_PI/6) * 90 }}" y2="{{ sin($i * M_PI/6) * 90 }}"
                  stroke="rgba(255,255,255,0.2)" stroke-width="3" stroke-linecap="round"/>
        @endfor
    </g>
</svg>

{{-- Sağ alt: paper plane --}}
<svg class="promo-deco promo-deco-br" width="180" height="160" viewBox="0 0 180 160" fill="none" xmlns="http://www.w3.org/2000/svg" opacity="0.3">
    <g transform="translate(50 30)">
        <path d="M0 50 L100 0 L80 50 L100 100 Z" fill="white"/>
        <path d="M0 50 L80 50 L100 100" stroke="rgba(255,255,255,0.5)" stroke-width="1" fill="none"/>
        {{-- Trail --}}
        <path d="M-30 70 Q-15 55 0 50" stroke="rgba(255,255,255,0.4)" stroke-width="2" fill="none" stroke-dasharray="3 4"/>
    </g>
</svg>

<div class="promo-card-inner">
    <div class="promo-logo">
        @if(!empty($logoUrl))
            <img class="logo-img" src="{{ $logoUrl }}" alt="{{ $brandName }}">
        @else
            <div class="logo-mark">M{{ mb_substr($brandAccent, 0, 1) }}</div>
            <div class="logo-text">{{ $brandShort }}<span class="accent">{{ $brandAccent }}</span></div>
        @endif
    </div>
    <div class="promo-tagline">🎉 {{ $tagline }}</div>

    <div class="promo-discount-bar">🔥 {{ $discountText }}</div>
    <h1 class="promo-title">{{ $title }}</h1>
    <p class="promo-subtitle">{{ $subtitle }}</p>

    <div class="promo-code-box">
        <div class="promo-code-label">🎟️ Kuponun</div>
        <div class="promo-code-value">{{ $code->code }}</div>
    </div>

    @if($expiryStr)
        <div class="promo-expiry">⏰ <span class="pill">{{ $expiryStr }}</span>'a kadar</div>
    @endif

    <a href="{{ $applyUrl }}" class="promo-cta">🚀 {{ $ctaText }}</a>
    <div class="promo-disclaimer">{{ $disclaimer }}</div>
</div>
