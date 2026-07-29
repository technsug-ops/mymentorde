{{-- ═══ PAKETLER (partner girmediyse yok — default paket ÜRETİLMEZ) ═══ --}}
@if(!empty($packages))
<div id="paketler" class="wrap sec">
    <div class="sec-head" style="max-width:600px;">
        <span class="lbl">Paketler</span>
        <h2 class="h2">Size uygun destek seviyesi</h2>
        @if($packageNote !== '')<p>{{ $packageNote }}</p>@endif
    </div>
    <div class="hair" style="--cols:{{ $cols(count($packages)) }};--min:270px;border-radius:18px;">
        @foreach($packages as $p)
            <div class="pkg{{ !empty($p['featured']) ? ' pkg-hi' : '' }}">
                @if(($p['tag'] ?? '') !== '')<span class="pkg-tag">{{ $p['tag'] }}</span>@endif
                <h3>{{ $p['name'] }}</h3>
                @if(($p['desc'] ?? '') !== '')<p>{{ $p['desc'] }}</p>@endif
                @if(!empty($p['items']))
                    <ul class="ticks">
                        @foreach($p['items'] as $item)<li>{!! $icon('check') !!}{{ $item }}</li>@endforeach
                    </ul>
                @endif
                <a href="{{ $applyUrl }}" class="pkg-btn" data-track="cta_clicked" data-ph-cta-name="package_apply" data-ph-location="partner_nane_packages">Bu paketi görüşelim</a>
            </div>
        @endforeach
    </div>
</div>
@endif
