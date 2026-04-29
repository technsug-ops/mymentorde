{{-- Template 3 — Premium (Lüks · Şık) --}}
<div style="text-align:center; margin-bottom: 16px;">
    <div style="font-size: 11px; letter-spacing: 4px; opacity: .5; margin-bottom: 4px;">— EXCLUSIVE —</div>
    <div class="promo-brand" style="text-align:center;">{{ $brandName }}</div>
    <div class="promo-tagline" style="text-align:center;">{{ $tagline }}</div>
</div>

<div style="text-align:center;">
    <div class="promo-discount-bar">{{ $discountText }}</div>
</div>

<h1 class="promo-title" style="text-align:center;">{{ $title }}</h1>
<p class="promo-subtitle" style="text-align:center;">{{ $subtitle }}</p>

<div class="promo-code-box">
    <div class="promo-code-label">DAVET KODU</div>
    <div class="promo-code-value">{{ $code->code }}</div>
</div>

@if($expiryStr)
    <div class="promo-expiry" style="text-align:center;">Son kullanma: <strong>{{ $expiryStr }}</strong></div>
@endif

<a href="{{ $applyUrl }}" class="promo-cta">{{ $ctaText }}</a>
<div class="promo-disclaimer">{{ $disclaimer }}</div>
