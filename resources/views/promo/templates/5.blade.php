{{-- Template 5 — Urgency (Aciliyet · Limited) --}}
<div class="promo-ribbon">LIMITED</div>

<div class="promo-brand">⚡ {{ $brandName }}</div>
<div class="promo-tagline">{{ $tagline }}</div>

@if($expiryStr)
    @php
        $daysLeft = max(0, (int) now()->diffInDays($code->valid_until, false));
    @endphp
    @if($daysLeft > 0)
        <div class="promo-countdown">⏳ SADECE {{ $daysLeft }} GÜN KALDI</div>
    @endif
@endif

<div class="promo-discount-bar">🚨 {{ $discountText }}</div>
<h1 class="promo-title">{{ $title }}</h1>
<p class="promo-subtitle">{{ $subtitle }}</p>

<div class="promo-code-box">
    <div class="promo-code-label">⚡ HIZLI OL — KOD</div>
    <div class="promo-code-value">{{ $code->code }}</div>
</div>

@if($expiryStr)
    <div class="promo-expiry">⏰ Son tarih: <strong>{{ $expiryStr }}</strong></div>
@endif

<a href="{{ $applyUrl }}" class="promo-cta">{{ $ctaText }} →</a>
<div class="promo-disclaimer">{{ $disclaimer }}</div>
