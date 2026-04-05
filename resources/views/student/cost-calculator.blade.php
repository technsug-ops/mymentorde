@extends('student.layouts.app')

@section('title', 'Maliyet Hesaplama')
@section('page_title', 'Maliyet Hesaplama Aracı')

@section('content')
@php
$calc          = $calculator ?? [];
$cities        = $calc['cities'] ?? [];
$selCity       = $calc['city'] ?? 'other';
$rate          = $calc['eurTryRate'] ?? null;
$pkgPrice      = $calc['packagePrice'] ?? 0;
$extraSvc      = $calc['extraServices'] ?? 0;
$yearlyLiving  = $calc['yearlyLiving'] ?? 0;
$monthlyLiving = $calc['monthlyLiving'] ?? 0;
$fixedOptional = $calc['fixedOptional'] ?? 0;
$depositCosts  = $calc['depositCosts'] ?? [];
$depositTotal  = $calc['depositTotal'] ?? 0;
@endphp

<form method="GET" action="/student/cost-calculator"
      style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:20px;">
    <label style="font-weight:600;font-size:var(--tx-sm);">Hedef Şehir:</label>
    <select name="city" onchange="this.form.submit()"
            style="border:1px solid var(--u-line);border-radius:6px;padding:6px 12px;font-size:var(--tx-sm);background:var(--u-card,#fff);">
        @foreach($cities as $key => $c)
            <option value="{{ $key }}" {{ $selCity === $key ? 'selected' : '' }}>{{ $c['label'] }}</option>
        @endforeach
    </select>
    @if($rate)
        <span class="badge info" style="font-size:var(--tx-xs);">1 EUR = {{ number_format($rate,2) }} TRY &middot; bugün</span>
    @endif
</form>

<div class="col3-1">

    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;color:var(--u-brand);">EUR Maliyetler</div>

            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--u-line);">
                <span style="font-size:var(--tx-sm);">
                    Yıllık Yaşam — {{ $calc['cityLabel'] ?? '' }}
                    <span style="font-size:var(--tx-xs);color:var(--u-muted);margin-left:4px;">(≈ €{{ number_format($monthlyLiving,0,',','.') }}/ay)</span>
                </span>
                <strong>€ {{ number_format($yearlyLiving,0,',','.') }}</strong>
            </div>

            @foreach($calc['fixedCosts'] ?? [] as $fc)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--u-line);">
                <span style="font-size:var(--tx-sm);">
                    @if($fc['required'])<span style="color:var(--u-danger,#dc2626);margin-right:3px;">*</span>@endif
                    {{ $fc['label'] }}
                </span>
                <span style="font-size:var(--tx-sm);">€ {{ number_format($fc['amount'],0,',','.') }}</span>
            </div>
            @endforeach

            @if($pkgPrice > 0)
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--u-line);">
                <span style="font-size:var(--tx-sm);">MentorDE Danışmanlık Paketi</span>
                <strong>€ {{ number_format($pkgPrice,0,',','.') }}</strong>
            </div>
            @endif

            @if($extraSvc > 0)
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--u-line);">
                <span style="font-size:var(--tx-sm);">Ek Hizmetler</span>
                <span style="font-size:var(--tx-sm);">€ {{ number_format($extraSvc,0,',','.') }}</span>
            </div>
            @endif

            @foreach($depositCosts as $dc)
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed var(--u-line);">
                <span style="font-size:var(--tx-sm);color:var(--u-muted);">{{ $dc['label'] }} (depozito)</span>
                <span style="font-size:var(--tx-sm);color:var(--u-muted);">€ {{ number_format($dc['amount'],0,',','.') }}</span>
            </div>
            @endforeach

            <div style="display:flex;justify-content:space-between;padding:12px 0 0;font-weight:700;font-size:var(--tx-base);color:var(--u-brand);">
                <span>Toplam (ilk yıl)</span>
                <span>€ {{ number_format($calc['grandTotalEur'] ?? 0,0,',','.') }}</span>
            </div>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px;">
        @if($rate)
        <div class="card" style="padding:20px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:12px;color:var(--u-text);">TRY Karşılığı</div>
            <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:8px;">1 EUR = {{ number_format($rate,2) }} TRY</div>
            <div style="font-size:var(--tx-xl);font-weight:700;color:var(--u-brand);">
                ₺ {{ number_format($calc['grandTotalTry'] ?? 0,0,',','.') }}
            </div>
        </div>
        @endif

        <div class="card" style="padding:20px;">
            <div style="font-weight:700;font-size:var(--tx-sm);margin-bottom:10px;color:var(--u-text);">Şehre Göre Aylık Bütçe</div>
            @foreach($cities as $key => $c)
            @php $monthly = ($c['rent_avg'] ?? 0) + ($c['food_avg'] ?? 0) + ($c['transport_avg'] ?? 0) + ($c['misc_avg'] ?? 0); @endphp
            <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid var(--u-line);font-size:var(--tx-xs);">
                <span style="color:var(--u-text);">{{ $c['label'] }}</span>
                <span style="color:{{ $key === $selCity ? 'var(--u-brand)' : 'var(--u-muted)' }};font-weight:{{ $key === $selCity ? '700' : '400' }};">€{{ number_format($monthly,0) }}</span>
            </div>
            @endforeach
        </div>

        <div class="card" style="padding:16px 20px;text-align:center;">
            <div style="font-size:var(--tx-xs);color:var(--u-muted);margin-bottom:8px;">Paketi değiştirmek mi istiyorsunuz?</div>
            <a href="/student/services" class="btn alt" style="font-size:var(--tx-xs);">Paket & Servisler</a>
        </div>
    </div>
</div>
@endsection
