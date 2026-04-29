{{-- Template 3 — Premium (lüks, ayrıcalıklı, altın temalı) --}}

{{-- Sağ üst altın halkalar --}}
<svg class="promo-deco promo-deco-tr" width="260" height="260" viewBox="0 0 260 260" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <g opacity="0.5">
        <circle cx="200" cy="60" r="80" stroke="#fbbf24" stroke-width="1" fill="none"/>
        <circle cx="200" cy="60" r="55" stroke="#fbbf24" stroke-width="1" fill="none"/>
        <circle cx="200" cy="60" r="30" stroke="#fbbf24" stroke-width="1" fill="none"/>
    </g>
</svg>

{{-- Sol alt brandenburg gate altın --}}
<svg class="promo-deco promo-deco-bl" width="240" height="160" viewBox="0 0 240 160" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <g transform="translate(45 60)" fill="#fbbf24" opacity="0.14">
        <rect x="0" y="80" width="160" height="40"/>
        <rect x="10" y="20" width="14" height="60"/>
        <rect x="32" y="20" width="14" height="60"/>
        <rect x="54" y="20" width="14" height="60"/>
        <rect x="76" y="20" width="14" height="60"/>
        <rect x="98" y="20" width="14" height="60"/>
        <rect x="120" y="20" width="14" height="60"/>
        <rect x="142" y="20" width="14" height="60"/>
        <rect x="0" y="10" width="160" height="14"/>
        <rect x="60" y="-12" width="40" height="22"/>
    </g>
</svg>

{{-- Köşe ornamental flourishler (altın L şekilleri) --}}
<svg style="position:absolute; top:14px; left:14px; z-index:1;" width="36" height="36" viewBox="0 0 36 36" fill="none" aria-hidden="true">
    <path d="M2 18 L2 2 L18 2" stroke="#fbbf24" stroke-width="1.5" fill="none" stroke-linecap="round" opacity="0.55"/>
    <circle cx="2" cy="2" r="2" fill="#fbbf24" opacity="0.6"/>
</svg>
<svg style="position:absolute; top:14px; right:14px; z-index:1;" width="36" height="36" viewBox="0 0 36 36" fill="none" aria-hidden="true">
    <path d="M34 18 L34 2 L18 2" stroke="#fbbf24" stroke-width="1.5" fill="none" stroke-linecap="round" opacity="0.55"/>
    <circle cx="34" cy="2" r="2" fill="#fbbf24" opacity="0.6"/>
</svg>
<svg style="position:absolute; bottom:14px; left:14px; z-index:1;" width="36" height="36" viewBox="0 0 36 36" fill="none" aria-hidden="true">
    <path d="M2 18 L2 34 L18 34" stroke="#fbbf24" stroke-width="1.5" fill="none" stroke-linecap="round" opacity="0.55"/>
    <circle cx="2" cy="34" r="2" fill="#fbbf24" opacity="0.6"/>
</svg>
<svg style="position:absolute; bottom:14px; right:14px; z-index:1;" width="36" height="36" viewBox="0 0 36 36" fill="none" aria-hidden="true">
    <path d="M34 18 L34 34 L18 34" stroke="#fbbf24" stroke-width="1.5" fill="none" stroke-linecap="round" opacity="0.55"/>
    <circle cx="34" cy="34" r="2" fill="#fbbf24" opacity="0.6"/>
</svg>

<div class="promo-card-inner">
    {{-- EXCLUSIVE üst etiketi --}}
    <div style="text-align:center; margin-bottom: 18px;">
        <div style="font-size: 10px; letter-spacing: 6px; color: #fbbf24; font-weight: 700; opacity:.85;">— EXCLUSIVE —</div>
    </div>

    {{-- Logo center --}}
    <div class="promo-header" style="text-align:center;">
        <div class="promo-logo" style="justify-content:center;">
            @if(!empty($logoUrl))
                <span class="promo-logo-img-wrap" style="background:rgba(251,191,36,.12); border:1px solid rgba(251,191,36,.3);"><img class="logo-img" src="{{ $logoUrl }}" alt="{{ $brandName }}"></span>
            @else
                <div class="logo-mark">M{{ mb_substr($brandAccent, 0, 1) }}</div>
                <div class="logo-text">{{ $brandShort }}<span class="accent">{{ $brandAccent }}</span></div>
            @endif
        </div>
        <div class="promo-tagline" style="margin-top:8px;">{{ $tagline }}</div>
    </div>

    {{-- Premium altın divider — bayrak şeridi yerine --}}
    <div class="promo-gold-divider">
        <span class="line"></span>
        <span class="dot"></span>
        <span class="dot lg"></span>
        <span class="dot"></span>
        <span class="line"></span>
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

    <div class="promo-gold-divider" style="margin-top: 22px; margin-bottom: 8px;">
        <span class="line" style="max-width:60px;"></span>
        <span class="dot"></span>
        <span class="line" style="max-width:60px;"></span>
    </div>
    <div class="promo-disclaimer">{{ $disclaimer }}</div>
</div>
