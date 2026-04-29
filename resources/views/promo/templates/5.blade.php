{{-- Template 5 — Urgency --}}

<div class="promo-ribbon">LIMITED</div>

{{-- Sağ üst lightning + halka --}}
<svg class="promo-deco promo-deco-tr" width="220" height="220" viewBox="0 0 220 220" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <circle cx="180" cy="60" r="55" fill="rgba(251,191,36,0.32)"/>
    <circle cx="180" cy="60" r="32" fill="rgba(251,191,36,0.42)"/>
    <g transform="translate(166 35)" fill="#fbbf24" opacity="0.85">
        <path d="M16 0 L4 28 L14 28 L6 50 L26 22 L16 22 L24 0 Z"/>
    </g>
</svg>

{{-- Sol alt patlama --}}
<svg class="promo-deco promo-deco-bl" width="240" height="240" viewBox="0 0 240 240" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <g transform="translate(60 180)" opacity="0.32">
        <circle cx="0" cy="0" r="42" fill="rgba(251,191,36,0.5)"/>
        @for($i = 0; $i < 16; $i++)
            <line x1="0" y1="0" x2="{{ cos($i * M_PI/8) * 70 }}" y2="{{ sin($i * M_PI/8) * 70 }}"
                  stroke="#fbbf24" stroke-width="2" opacity="0.6"/>
        @endfor
    </g>
</svg>

{{-- Sağ alt saat illüstrasyonu --}}
<svg class="promo-deco promo-deco-br" width="180" height="180" viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <g transform="translate(40 40)" opacity="0.28">
        <circle cx="60" cy="60" r="50" stroke="white" stroke-width="3" fill="none"/>
        <circle cx="60" cy="60" r="3" fill="white"/>
        <line x1="60" y1="60" x2="60" y2="28" stroke="white" stroke-width="3" stroke-linecap="round"/>
        <line x1="60" y1="60" x2="88" y2="60" stroke="white" stroke-width="2" stroke-linecap="round"/>
        <line x1="60" y1="14" x2="60" y2="20" stroke="white" stroke-width="2"/>
        <line x1="106" y1="60" x2="100" y2="60" stroke="white" stroke-width="2"/>
        <line x1="60" y1="106" x2="60" y2="100" stroke="white" stroke-width="2"/>
        <line x1="14" y1="60" x2="20" y2="60" stroke="white" stroke-width="2"/>
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
        <div class="promo-tagline">⚡ {{ $tagline }}</div>
    </div>

    @if($daysLeft && $daysLeft > 0)
        <div class="promo-countdown">
            ⏳ Sadece <span class="num">{{ $daysLeft }}</span> gün kaldı
        </div>
    @endif

    <div class="promo-discount-bar">🚨 {{ $discountText }}</div>
    <h1 class="promo-title">{{ $title }}</h1>
    <p class="promo-subtitle">{{ $subtitle }}</p>

    <div class="promo-code-box">
        <div class="promo-code-label">⚡ Hızlı ol — kod</div>
        <div class="promo-code-value">{{ $code->code }}</div>
    </div>

    @if($expiryStr)
        <div class="promo-expiry">⏰ Son tarih: <span class="pill">{{ $expiryStr }}</span></div>
    @endif

    <a href="{{ $applyUrl }}" class="promo-cta">{{ $ctaText }} →</a>
    <div class="promo-disclaimer">{{ $disclaimer }}</div>
</div>
