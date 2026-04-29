{{-- Template 3 — Premium (Lüks · Ayrıcalıklı) --}}

{{-- Köşe ornamental L-flourishler (4 köşe — daha refined) --}}
<svg style="position:absolute; top:18px; left:18px; z-index:1;" width="44" height="44" viewBox="0 0 44 44" fill="none" aria-hidden="true">
    <path d="M2 22 L2 2 L22 2" stroke="url(#gold-grad-tl)" stroke-width="1.5" fill="none" stroke-linecap="round"/>
    <circle cx="2" cy="2" r="3" fill="#fbbf24"/>
    <circle cx="2" cy="2" r="1.5" fill="#0f172a"/>
    <defs><linearGradient id="gold-grad-tl" x1="2" y1="2" x2="22" y2="22"><stop offset="0%" stop-color="#fde68a"/><stop offset="100%" stop-color="#f59e0b"/></linearGradient></defs>
</svg>
<svg style="position:absolute; top:18px; right:18px; z-index:1;" width="44" height="44" viewBox="0 0 44 44" fill="none" aria-hidden="true">
    <path d="M42 22 L42 2 L22 2" stroke="url(#gold-grad-tr)" stroke-width="1.5" fill="none" stroke-linecap="round"/>
    <circle cx="42" cy="2" r="3" fill="#fbbf24"/>
    <circle cx="42" cy="2" r="1.5" fill="#0f172a"/>
    <defs><linearGradient id="gold-grad-tr" x1="42" y1="2" x2="22" y2="22"><stop offset="0%" stop-color="#fde68a"/><stop offset="100%" stop-color="#f59e0b"/></linearGradient></defs>
</svg>
<svg style="position:absolute; bottom:18px; left:18px; z-index:1;" width="44" height="44" viewBox="0 0 44 44" fill="none" aria-hidden="true">
    <path d="M2 22 L2 42 L22 42" stroke="url(#gold-grad-bl)" stroke-width="1.5" fill="none" stroke-linecap="round"/>
    <circle cx="2" cy="42" r="3" fill="#fbbf24"/>
    <circle cx="2" cy="42" r="1.5" fill="#0f172a"/>
    <defs><linearGradient id="gold-grad-bl" x1="2" y1="42" x2="22" y2="22"><stop offset="0%" stop-color="#fde68a"/><stop offset="100%" stop-color="#f59e0b"/></linearGradient></defs>
</svg>
<svg style="position:absolute; bottom:18px; right:18px; z-index:1;" width="44" height="44" viewBox="0 0 44 44" fill="none" aria-hidden="true">
    <path d="M42 22 L42 42 L22 42" stroke="url(#gold-grad-br)" stroke-width="1.5" fill="none" stroke-linecap="round"/>
    <circle cx="42" cy="42" r="3" fill="#fbbf24"/>
    <circle cx="42" cy="42" r="1.5" fill="#0f172a"/>
    <defs><linearGradient id="gold-grad-br" x1="42" y1="42" x2="22" y2="22"><stop offset="0%" stop-color="#fde68a"/><stop offset="100%" stop-color="#f59e0b"/></linearGradient></defs>
</svg>

<div class="promo-card-inner" style="padding-top: 56px;">
    {{-- ── Hero Crest: tag + laurel wreath + monogram ─────────────── --}}
    <div class="promo-crest">
        <div class="promo-crest-tag">— A n n i v e r s a r y —</div>

        {{-- Laurel wreath etrafında MD monogram --}}
        <svg class="promo-crest-icon" viewBox="0 0 100 100" fill="none" aria-hidden="true">
            <defs>
                <linearGradient id="laurelGold" x1="0" y1="0" x2="100" y2="100">
                    <stop offset="0%" stop-color="#fde68a"/>
                    <stop offset="50%" stop-color="#fbbf24"/>
                    <stop offset="100%" stop-color="#d97706"/>
                </linearGradient>
            </defs>

            {{-- Sol laurel dalı --}}
            <g stroke="url(#laurelGold)" stroke-width="1.5" fill="none" stroke-linecap="round">
                <path d="M22 50 Q12 35 22 22"/>
                <path d="M22 50 Q22 30 30 18"/>
                {{-- Yapraklar --}}
                <ellipse cx="16" cy="28" rx="3.5" ry="6" transform="rotate(-30 16 28)" fill="url(#laurelGold)" opacity="0.85"/>
                <ellipse cx="20" cy="38" rx="3.5" ry="6" transform="rotate(-25 20 38)" fill="url(#laurelGold)" opacity="0.85"/>
                <ellipse cx="22" cy="48" rx="3.5" ry="6" transform="rotate(-15 22 48)" fill="url(#laurelGold)" opacity="0.85"/>
                <ellipse cx="26" cy="22" rx="3" ry="5" transform="rotate(-40 26 22)" fill="url(#laurelGold)" opacity="0.85"/>
                <ellipse cx="30" cy="32" rx="3" ry="5" transform="rotate(-30 30 32)" fill="url(#laurelGold)" opacity="0.85"/>
            </g>

            {{-- Sağ laurel dalı (mirror) --}}
            <g stroke="url(#laurelGold)" stroke-width="1.5" fill="none" stroke-linecap="round">
                <path d="M78 50 Q88 35 78 22"/>
                <path d="M78 50 Q78 30 70 18"/>
                <ellipse cx="84" cy="28" rx="3.5" ry="6" transform="rotate(30 84 28)" fill="url(#laurelGold)" opacity="0.85"/>
                <ellipse cx="80" cy="38" rx="3.5" ry="6" transform="rotate(25 80 38)" fill="url(#laurelGold)" opacity="0.85"/>
                <ellipse cx="78" cy="48" rx="3.5" ry="6" transform="rotate(15 78 48)" fill="url(#laurelGold)" opacity="0.85"/>
                <ellipse cx="74" cy="22" rx="3" ry="5" transform="rotate(40 74 22)" fill="url(#laurelGold)" opacity="0.85"/>
                <ellipse cx="70" cy="32" rx="3" ry="5" transform="rotate(30 70 32)" fill="url(#laurelGold)" opacity="0.85"/>
            </g>

            {{-- Üst yıldız --}}
            <g transform="translate(50 12)" fill="url(#laurelGold)">
                <path d="M0 -5 L1.5 -1.5 L5 0 L1.5 1.5 L0 5 L-1.5 1.5 L-5 0 L-1.5 -1.5 Z"/>
            </g>

            {{-- Merkez monogram MD --}}
            <text x="50" y="58" font-family="Playfair Display, serif" font-size="22" font-weight="900"
                  fill="url(#laurelGold)" text-anchor="middle">{{ mb_substr($brandShort, 0, 1) }}{{ mb_substr($brandAccent, 0, 1) }}</text>

            {{-- Alt süs çizgi --}}
            <line x1="35" y1="68" x2="65" y2="68" stroke="url(#laurelGold)" stroke-width="1"/>
            <circle cx="50" cy="68" r="1.5" fill="#fbbf24"/>
        </svg>

        <div class="promo-crest-tag" style="font-size: 10px; letter-spacing: 5px; opacity: .65;">— EXCLUSIVE OFFER —</div>
    </div>

    {{-- ── Logo bloğu (centered) ──────────────────────────────────── --}}
    <div class="promo-header" style="text-align: center;">
        <div class="promo-logo" style="justify-content: center;">
            @if(!empty($logoUrl))
                <span class="promo-logo-img-wrap" style="background: white; border: 1px solid rgba(251,191,36,.55); padding: 8px 16px; box-shadow: 0 6px 18px rgba(251,191,36,.22);"><img class="logo-img" src="{{ $logoUrl }}" alt="{{ $brandName }}" style="height: 36px;"></span>
            @else
                <div class="logo-mark">{{ mb_substr($brandShort, 0, 1) }}{{ mb_substr($brandAccent, 0, 1) }}</div>
                <div class="logo-text">{{ $brandShort }}<span class="accent">{{ $brandAccent }}</span></div>
            @endif
        </div>
        <div class="promo-tagline" style="margin-top: 10px;">{{ $tagline }}</div>
    </div>

    {{-- ── Refined altın divider ──────────────────────────────────── --}}
    <div class="promo-gold-divider">
        <span class="line"></span>
        <span class="dot"></span>
        <span class="dot lg"></span>
        <span class="dot"></span>
        <span class="line"></span>
    </div>

    <div style="text-align: center;">
        <div class="promo-discount-bar">✦ {{ $discountText }} ✦</div>
    </div>

    <h1 class="promo-title" style="text-align: center;">{{ $title }}</h1>
    <p class="promo-subtitle" style="text-align: center;">{{ $subtitle }}</p>

    {{-- ── Davet kodu kutusu ─────────────────────────────────────── --}}
    <div class="promo-code-box">
        <div class="promo-code-label" style="font-style: italic; letter-spacing: 4px;">— Davet Kodu —</div>
        <div class="promo-code-value">{{ $code->code }}</div>
    </div>

    @if($expiryStr)
        <div class="promo-expiry" style="justify-content: center;">
            <span style="font-style: italic; opacity: .8;">Geçerlilik</span>
            <span class="pill">{{ $expiryStr }}</span>
        </div>
    @endif

    <a href="{{ $applyUrl }}" class="promo-cta">{{ $ctaText }}</a>

    {{-- Alt divider --}}
    <div class="promo-gold-divider" style="margin-top: 26px; margin-bottom: 10px; opacity: .55;">
        <span class="line" style="max-width: 60px;"></span>
        <span class="dot"></span>
        <span class="line" style="max-width: 60px;"></span>
    </div>

    <div class="promo-disclaimer">{{ $disclaimer }}</div>
</div>
