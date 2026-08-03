{{-- ═══ DESTEK PAKETLERİ ═══ (id=paketler → navLinks anchor'ı; partner girmediyse yok) --}}
@if(!empty($packages))
<section id="paketler" class="sec">
    <div class="wrap">
        <div class="sec-head c">
            <span class="kick c">Paketler</span>
            <h2>Size Uygun Destek Seviyesi</h2>
            @if($packageNote !== '')<p>{{ $packageNote }}</p>@endif
        </div>
        <div class="pkg-grid" style="--n:{{ max(1, min(count($packages), 3)) }};">
            @foreach($packages as $p)
                <div class="pkg{{ !empty($p['featured']) ? ' pkg-hi' : '' }}">
                    @if(!empty($p['featured']))<span class="pkg-flag">Öne çıkan</span>@endif
                    <div class="pkg-head">
                        <h3>{{ $p['name'] }}</h3>
                        @if(($p['tag'] ?? '') !== '')<span class="pkg-tag">{{ $p['tag'] }}</span>@endif
                    </div>
                    @if(($p['desc'] ?? '') !== '')<p class="pkg-desc">{{ $p['desc'] }}</p>@endif
                    @if(!empty($p['items']))
                        <ul class="pkg-items">
                            @foreach($p['items'] as $item)<li>{!! $icon('check') !!} {{ $item }}</li>@endforeach
                        </ul>
                    @endif
                    <a href="{{ $applyUrl }}" class="btn pkg-btn" data-track="cta_clicked" data-ph-cta-name="package_apply" data-ph-location="partner_bold_packages">Bu paketi görüşelim {!! $icon('arrow') !!}</a>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
