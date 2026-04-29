{{-- Template 1 — Classic --}}

{{-- Sağ üst: konsantrik daireler --}}
<svg class="promo-deco promo-deco-tr" width="220" height="220" viewBox="0 0 220 220" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <circle cx="160" cy="60" r="80" fill="rgba(255,255,255,0.07)"/>
    <circle cx="160" cy="60" r="50" fill="rgba(255,255,255,0.06)"/>
    <circle cx="160" cy="60" r="22" fill="rgba(255,255,255,0.05)"/>
</svg>

{{-- Sol alt: brandenburg gate (subtle) --}}
<svg class="promo-deco promo-deco-bl" width="240" height="160" viewBox="0 0 240 160" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <g transform="translate(45 60)" opacity="0.14">
        <rect x="0" y="80" width="160" height="40" fill="white"/>
        <rect x="10" y="20" width="14" height="60" fill="white"/>
        <rect x="32" y="20" width="14" height="60" fill="white"/>
        <rect x="54" y="20" width="14" height="60" fill="white"/>
        <rect x="76" y="20" width="14" height="60" fill="white"/>
        <rect x="98" y="20" width="14" height="60" fill="white"/>
        <rect x="120" y="20" width="14" height="60" fill="white"/>
        <rect x="142" y="20" width="14" height="60" fill="white"/>
        <rect x="0" y="10" width="160" height="14" fill="white"/>
        <rect x="60" y="-12" width="40" height="22" fill="white"/>
    </g>
</svg>

<div class="promo-card-inner">
    {{-- Logo + tagline header --}}
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
