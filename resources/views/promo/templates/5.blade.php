{{-- Template 5 — Urgency (Limited · Aciliyet) — uyarı şeritleri + alarm + LIMITED rozet --}}

<div class="promo-ribbon">LIMITED</div>

{{-- Sağ üst: yıldırım + alarm --}}
<svg class="promo-deco promo-deco-tr" width="240" height="240" viewBox="0 0 240 240" fill="none" xmlns="http://www.w3.org/2000/svg" opacity="0.45">
    <circle cx="200" cy="50" r="60" fill="rgba(251,191,36,0.3)"/>
    <circle cx="200" cy="50" r="35" fill="rgba(251,191,36,0.4)"/>
    {{-- Lightning bolt --}}
    <g transform="translate(180 25)" fill="#fbbf24">
        <path d="M20 0 L8 28 L18 28 L10 50 L30 22 L20 22 L28 0 Z"/>
    </g>
</svg>

{{-- Sol alt: patlama efekti --}}
<svg class="promo-deco promo-deco-bl" width="240" height="240" viewBox="0 0 240 240" fill="none" xmlns="http://www.w3.org/2000/svg" opacity="0.3">
    <g transform="translate(60 170)">
        <circle cx="0" cy="0" r="50" fill="rgba(251,191,36,0.5)"/>
        @for($i = 0; $i < 16; $i++)
            <line x1="0" y1="0" x2="{{ cos($i * M_PI/8) * 80 }}" y2="{{ sin($i * M_PI/8) * 80 }}"
                  stroke="#fbbf24" stroke-width="2" opacity="0.5"/>
        @endfor
    </g>
</svg>

{{-- Sağ alt: zaman kaçıyor --}}
<svg class="promo-deco promo-deco-br" width="200" height="200" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg" opacity="0.25">
    <circle cx="120" cy="120" r="60" stroke="white" stroke-width="3" fill="none"/>
    <circle cx="120" cy="120" r="3" fill="white"/>
    <line x1="120" y1="120" x2="120" y2="80" stroke="white" stroke-width="3" stroke-linecap="round"/>
    <line x1="120" y1="120" x2="155" y2="120" stroke="white" stroke-width="2" stroke-linecap="round"/>
    {{-- Saat işaretleri --}}
    <line x1="120" y1="68" x2="120" y2="74" stroke="white" stroke-width="2"/>
    <line x1="172" y1="120" x2="166" y2="120" stroke="white" stroke-width="2"/>
    <line x1="120" y1="172" x2="120" y2="166" stroke="white" stroke-width="2"/>
    <line x1="68" y1="120" x2="74" y2="120" stroke="white" stroke-width="2"/>
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
    <div class="promo-tagline">⚡ {{ $tagline }}</div>

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
