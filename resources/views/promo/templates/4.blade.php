{{-- Template 4 — Playful --}}

{{-- Sağ üst yıldız + halka --}}
<svg class="promo-deco promo-deco-tr" width="240" height="220" viewBox="0 0 240 220" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <circle cx="220" cy="40" r="42" fill="rgba(253,224,71,0.55)"/>
    <circle cx="180" cy="80" r="22" fill="rgba(244,114,182,0.5)"/>
    <g transform="translate(180 30)" fill="#581c87" opacity="0.7">
        <path d="M0 0 L3 8 L11 8 L5 13 L7 21 L0 16 L-7 21 L-5 13 L-11 8 L-3 8 Z"/>
    </g>
    <g transform="translate(160 130)" fill="#fff" opacity="0.85">
        <path d="M0 0 L2 6 L8 6 L4 10 L5 16 L0 12 L-5 16 L-4 10 L-8 6 L-2 6 Z"/>
    </g>
    <path d="M100 50 Q120 40 130 60 Q140 80 160 70" stroke="#581c87" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-dasharray="3 4" opacity="0.4"/>
</svg>

{{-- Sol alt: paper plane + dünya --}}
<svg class="promo-deco promo-deco-bl" width="240" height="200" viewBox="0 0 240 200" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <circle cx="50" cy="160" r="40" fill="rgba(253,224,71,0.45)"/>
    <g transform="translate(30 140)" opacity="0.65">
        <circle cx="20" cy="20" r="22" fill="rgba(255,255,255,0.4)"/>
        <path d="M0 18 Q10 12 20 16 Q30 20 40 14" stroke="#581c87" stroke-width="1.5" fill="none"/>
        <path d="M0 26 Q12 22 24 28 Q34 32 42 26" stroke="#581c87" stroke-width="1.5" fill="none"/>
    </g>
    <g transform="translate(120 60) rotate(-15)" fill="#581c87" opacity="0.85">
        <path d="M0 30 L60 0 L48 30 L60 60 Z"/>
        <path d="M0 30 L48 30 L60 60" stroke="rgba(255,255,255,0.5)" stroke-width="1" fill="none"/>
    </g>
    <path d="M40 110 Q80 90 120 90" stroke="#581c87" stroke-width="2" fill="none" stroke-dasharray="4 4" opacity="0.45"/>
</svg>

{{-- ÖZEL SANA sticker — content'le çakışmayacak konum --}}
<div class="promo-sticker">✨ ÖZEL SANA</div>

<div class="promo-card-inner" style="padding-top: 50px;">
    <div class="promo-header">
        <div class="promo-logo">
            @if(!empty($logoUrl))
                <span class="promo-logo-img-wrap"><img class="logo-img" src="{{ $logoUrl }}" alt="{{ $brandName }}"></span>
            @else
                <div class="logo-mark">M{{ mb_substr($brandAccent, 0, 1) }}</div>
                <div class="logo-text">{{ $brandShort }}<span class="accent">{{ $brandAccent }}</span></div>
            @endif
        </div>
        <div class="promo-tagline">✈️ {{ $tagline }}</div>
    </div>

    <div class="promo-discount-bar">🎁 {{ $discountText }}</div>
    <h1 class="promo-title">{{ $title }}</h1>
    <p class="promo-subtitle">{{ $subtitle }}</p>

    <div class="promo-code-box">
        <div class="promo-code-label">🌟 Sana Özel Kod</div>
        <div class="promo-code-value">{{ $code->code }}</div>
    </div>

    @if($expiryStr)
        <div class="promo-expiry">📅 <span class="pill">{{ $expiryStr }}</span> tarihine kadar geçerli</div>
    @endif

    <a href="{{ $applyUrl }}" class="promo-cta">{{ $ctaText }} 🎓</a>
    <div class="promo-disclaimer">{{ $disclaimer }}</div>
</div>
