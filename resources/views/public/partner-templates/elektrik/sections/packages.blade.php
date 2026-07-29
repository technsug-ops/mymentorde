{{-- ═══ PAKETLER (partner girmediyse yok — default paket ÜRETİLMEZ) ═══ --}}
@if(!empty($packages))
<div id="paketler" class="wrap" style="padding:62px 26px;">
    <div class="sec-head-row">
        <div class="sec-head" style="max-width:560px;margin-bottom:0;">
            <span class="lbl">Paketler</span>
            <h2 class="h2">Size uygun destek seviyesi</h2>
        </div>
        @if($packageNote !== '')<span class="sec-note">{{ $packageNote }}</span>@endif
    </div>
    <div class="grid" style="--min:280px;--cols:{{ $cols(count($packages)) }};">
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
                <a href="{{ $applyUrl }}" class="pkg-btn" data-track="cta_clicked" data-ph-cta-name="package_apply" data-ph-location="partner_elektrik_packages">Bu paketi görüşelim</a>
            </div>
        @endforeach
    </div>
</div>
@endif
