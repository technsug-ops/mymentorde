{{-- Template 4 — Playful (Genç · Renkli) — dağılmış şekiller + uçak + sticker rozeti --}}

{{-- Sağ üst: stickers + plane --}}
<svg class="promo-deco promo-deco-tr" width="280" height="240" viewBox="0 0 280 240" fill="none" xmlns="http://www.w3.org/2000/svg">
    {{-- Yumuşak şekiller --}}
    <circle cx="240" cy="40" r="40" fill="rgba(253,224,71,0.6)"/>
    <circle cx="200" cy="80" r="20" fill="rgba(244,114,182,0.5)"/>
    {{-- Yıldızlar --}}
    <g transform="translate(180 30)" fill="#581c87">
        <path d="M0 0 L3 8 L11 8 L5 13 L7 21 L0 16 L-7 21 L-5 13 L-11 8 L-3 8 Z" opacity="0.7"/>
    </g>
    <g transform="translate(160 130)" fill="#fff" opacity="0.8">
        <path d="M0 0 L2 6 L8 6 L4 10 L5 16 L0 12 L-5 16 L-4 10 L-8 6 L-2 6 Z"/>
    </g>
    {{-- Kıvrımlı çizgi (şeker yolu) --}}
    <path d="M100 50 Q120 40 130 60 Q140 80 160 70" stroke="#581c87" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-dasharray="3 4" opacity="0.4"/>
</svg>

{{-- Sol alt: paper plane + dünya--}}
<svg class="promo-deco promo-deco-bl" width="260" height="200" viewBox="0 0 260 200" fill="none" xmlns="http://www.w3.org/2000/svg">
    <circle cx="60" cy="160" r="50" fill="rgba(253,224,71,0.5)"/>
    {{-- Dünya --}}
    <g transform="translate(40 140)" opacity="0.7">
        <circle cx="20" cy="20" r="22" fill="rgba(255,255,255,0.4)"/>
        <path d="M0 18 Q10 12 20 16 Q30 20 40 14" stroke="#581c87" stroke-width="1.5" fill="none"/>
        <path d="M0 26 Q12 22 24 28 Q34 32 42 26" stroke="#581c87" stroke-width="1.5" fill="none"/>
    </g>
    {{-- Paper plane --}}
    <g transform="translate(130 60) rotate(-15)" fill="#581c87">
        <path d="M0 30 L60 0 L48 30 L60 60 Z" opacity="0.85"/>
        <path d="M0 30 L48 30 L60 60" stroke="rgba(255,255,255,0.5)" stroke-width="1" fill="none"/>
    </g>
    {{-- Trail --}}
    <path d="M50 110 Q90 90 130 90" stroke="#581c87" stroke-width="2" fill="none" stroke-dasharray="4 4" opacity="0.4"/>
</svg>

{{-- Sticker rozeti sağ üst --}}
<div style="position:absolute; top: 24px; right: 24px; z-index: 4; transform: rotate(8deg);">
    <div style="background:#fde047; color:#581c87; padding: 8px 14px; border-radius: 999px;
                font-size: 10px; font-weight: 900; letter-spacing: 1.5px; box-shadow: 0 4px 12px rgba(0,0,0,.15);
                border: 2px solid #581c87;">
        ✨ ÖZEL SANA
    </div>
</div>

<div class="promo-card-inner">
    <div class="promo-logo">
        @if(!empty($logoUrl))
            <img class="logo-img" src="{{ $logoUrl }}" alt="{{ $brandName }}">
        @else
            <div class="logo-mark">M{{ mb_substr($brandAccent, 0, 1) }}</div>
            <div class="logo-text">{{ $brandShort }}<span class="accent">{{ $brandAccent }}</span></div>
        @endif
    </div>
    <div class="promo-tagline">✈️ {{ $tagline }}</div>

    <div class="promo-discount-bar">🎁 {{ $discountText }}</div>
    <h1 class="promo-title">{{ $title }}</h1>
    <p class="promo-subtitle">{{ $subtitle }}</p>

    <div class="promo-code-box">
        <div class="promo-code-label">🌟 Sana özel kod</div>
        <div class="promo-code-value">{{ $code->code }}</div>
    </div>

    @if($expiryStr)
        <div class="promo-expiry">📅 <span class="pill">{{ $expiryStr }}</span>'a kadar geçerli</div>
    @endif

    <a href="{{ $applyUrl }}" class="promo-cta">{{ $ctaText }} 🎓</a>
    <div class="promo-disclaimer">{{ $disclaimer }}</div>
</div>
