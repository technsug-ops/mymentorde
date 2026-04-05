@extends('guest.layouts.app')

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
$firstYearTotal= $calc['firstYearTotal'] ?? 0;
$depositCosts  = $calc['depositCosts'] ?? [];
$depositTotal  = $calc['depositTotal'] ?? 0;
$turkeyTry     = $calc['comparison']['turkey_private_yearly'] ?? 150000;
$cityData      = $cities[$selCity] ?? ($cities['other'] ?? []);
@endphp

{{-- Şehir Seçici --}}
<form method="GET" action="{{ route('guest.cost-calculator') }}"
      style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:20px;">
    <label style="font-weight:600;font-size:var(--tx-sm);">Hedef Şehir:</label>
    <select name="city" onchange="this.form.submit()"
            style="border:1px solid var(--border,#e2e8f0);border-radius:6px;padding:6px 12px;font-size:var(--tx-sm);background:var(--surface,#fff);">
        @foreach($cities as $key => $c)
            <option value="{{ $key }}" {{ $selCity === $key ? 'selected' : '' }}>{{ $c['label'] }}</option>
        @endforeach
    </select>
    @if($rate)
        <span class="badge info" style="font-size:var(--tx-xs);">1 EUR = {{ number_format($rate,2) }} TRY &middot; bugün</span>
    @endif
</form>

{{-- Ana 2-kolon düzeni (col3-1 = 2fr:1fr) --}}
<div class="col3-1">

    {{-- SOL: EUR Maliyet Dökümü --}}
    <div class="card">
        <div class="card-body" style="padding:20px;">
            <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;color:var(--c-accent);">EUR Maliyetler</div>

            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border,#e2e8f0);">
                <span style="font-size:var(--tx-sm);">
                    Yıllık Yaşam — {{ $calc['cityLabel'] ?? '' }}
                    <span style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-left:4px;">(≈ € {{ number_format($monthlyLiving,0,',','.') }}/ay)</span>
                </span>
                <strong>€ {{ number_format($yearlyLiving,0,',','.') }}</strong>
            </div>
            @foreach($calc['fixedCosts'] ?? [] as $fc)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border,#e2e8f0);">
                <span style="font-size:var(--tx-sm);">
                    @if($fc['required'])<span style="color:var(--c-danger,#dc2626);margin-right:3px;">*</span>@endif
                    {{ $fc['label'] }}
                    <span style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-left:4px;">({{ $fc['type'] === 'one_time' ? 'bir seferlik' : ($fc['type'] === 'yearly' ? 'yıllık' : 'dönemlik') }})</span>
                </span>
                <strong>€ {{ number_format($fc['amount'],0,',','.') }}</strong>
            </div>
            @endforeach

            @if(count($depositCosts) > 0)
            <div style="margin-top:14px;padding:12px;background:var(--info-bg,#eff6ff);border-radius:8px;border-left:3px solid var(--c-info,#3b82f6);">
                <div style="font-size:var(--tx-xs);font-weight:700;color:var(--c-info,#3b82f6);margin-bottom:8px;">&#9432; Vize Zorunlu Depozit (Toplama Dahil Değil)</div>
                @foreach($depositCosts as $dc)
                <div style="display:flex;justify-content:space-between;align-items:center;font-size:var(--tx-sm);">
                    <span>{{ $dc['label'] }}</span>
                    <strong>€ {{ number_format($dc['amount'],0,',','.') }}</strong>
                </div>
                @endforeach
                <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-top:6px;">
                    Bu para vize başvurusu için bloke hesaba yatırılır ve Almanya'ya gelince aylık yaşam giderleriniz için kullanılır. Ayrı bir maliyet değildir.
                </div>
            </div>
            @endif

            <div style="margin-top:16px;padding-top:12px;border-top:2px solid var(--c-accent,#2563eb);display:flex;justify-content:space-between;align-items:baseline;">
                <span style="font-weight:700;font-size:var(--tx-base);">1. Yıl Toplam</span>
                <span style="font-size:var(--tx-2xl);font-weight:800;color:var(--c-accent,#2563eb);">€ {{ number_format($firstYearTotal,0,',','.') }}</span>
            </div>
            <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-top:4px;">
                * Zorunlu giderler dahil. Opsiyonel: € {{ number_format($fixedOptional,0,',','.') }}
            </div>
        </div>
    </div>

    {{-- SAĞ: Özet + Karşılaştırma --}}
    <div style="display:flex;flex-direction:column;gap:14px;">

        {{-- Özet KPI --}}
        <div class="card">
            <div class="card-body" style="padding:20px;">
                <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:14px;">Özet</div>

                <div style="margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid var(--border,#e2e8f0);">
                    <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-bottom:2px;">1. YIL TOPLAM</div>
                    <div style="font-size:var(--tx-xl);font-weight:800;color:var(--c-accent,#2563eb);">€ {{ number_format($firstYearTotal,0,',','.') }}</div>
                </div>
                @if($rate)
                <div style="margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid var(--border,#e2e8f0);">
                    <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-bottom:2px;">TRY KARŞILIĞI</div>
                    <div style="font-size:var(--tx-xl);font-weight:800;color:var(--c-ok,#16a34a);">₺ {{ number_format($firstYearTotal*$rate,0,',','.') }}</div>
                    <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);">1 EUR = {{ number_format($rate,2) }} TRY</div>
                </div>
                @endif
                <div>
                    <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);margin-bottom:2px;">AYLIK YAŞAM</div>
                    <div style="font-size:var(--tx-xl);font-weight:800;">€ {{ number_format($monthlyLiving,0,',','.') }}<span style="font-size:var(--tx-sm);font-weight:400;color:var(--muted,#64748b);">/ay</span></div>
                    <div style="font-size:var(--tx-xs);color:var(--muted,#64748b);">{{ $calc['cityLabel'] ?? '' }}</div>
                </div>
            </div>
        </div>

        {{-- Aylık Dağılım --}}
        <div class="card">
            <div class="card-body" style="padding:20px;">
                <div style="font-weight:700;font-size:var(--tx-base);margin-bottom:12px;">Aylık Dağılım — {{ $calc['cityLabel'] ?? '' }}</div>
                <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border,#e2e8f0);">
                    <span style="font-size:var(--tx-sm);">Kira (ort.)</span>
                    <strong>€ {{ number_format($cityData['rent_avg'] ?? 0,0,',','.') }}/ay</strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border,#e2e8f0);">
                    <span style="font-size:var(--tx-sm);">Gıda</span>
                    <strong>€ {{ number_format($cityData['food_avg'] ?? 0,0,',','.') }}/ay</strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border,#e2e8f0);">
                    <span style="font-size:var(--tx-sm);">Ulaşım</span>
                    <strong>€ {{ number_format($cityData['transport_avg'] ?? 0,0,',','.') }}/ay</strong>
                </div>
                @if(($cityData['misc_avg'] ?? 0) > 0)
                <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border,#e2e8f0);">
                    <span style="font-size:var(--tx-sm);">Diğer (telefon, kişisel)</span>
                    <strong>€ {{ number_format($cityData['misc_avg'],0,',','.') }}/ay</strong>
                </div>
                @endif
                <div style="display:flex;justify-content:space-between;padding:7px 0;font-weight:700;">
                    <span style="font-size:var(--tx-sm);">Toplam</span>
                    <span>€ {{ number_format($monthlyLiving,0,',','.') }}/ay</span>
                </div>
            </div>
        </div>

    </div>{{-- /sağ kolon --}}
</div>{{-- /col3-1 --}}

<p style="font-size:var(--tx-xs);color:var(--muted,#64748b);text-align:center;margin-top:4px;">
    Bu hesaplama tahminidir. Gerçek maliyetler kişisel durumunuza ve seçtiğiniz üniversiteye göre değişebilir. Döviz kuru günlük güncellenmektedir.
</p>

@endsection
