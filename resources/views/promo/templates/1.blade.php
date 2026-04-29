{{-- Template 1 — Classic (Sade · Profesyonel) --}}
<div class="promo-brand">{{ $brandName }}</div>
<div class="promo-tagline">{{ $tagline }}</div>

<div class="promo-discount-bar">{{ $discountText }}</div>
<h1 class="promo-title">{{ $title }}</h1>
<p class="promo-subtitle">{{ $subtitle }}</p>

<div class="promo-code-box">
    <div class="promo-code-label">İNDİRİM KODU</div>
    <div class="promo-code-value">{{ $code->code }}</div>
</div>

@if($expiryStr)
    <div class="promo-expiry">⏰ Son kullanma: <strong>{{ $expiryStr }}</strong></div>
@endif

<a href="{{ $applyUrl }}" class="promo-cta">{{ $ctaText }} →</a>
<div class="promo-disclaimer">{{ $disclaimer }}</div>
