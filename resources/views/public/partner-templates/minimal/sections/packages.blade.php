{{-- ═══ DESTEK PAKETLERİ ═══ (id=paketler → navLinks anchor'ı; partner girmediyse yok) --}}
@if(!empty($packages))
<section id="paketler" class="sec sec-top">
    <div class="wrap">
        <div class="sec-head">
            <span class="eyebrow acc">Paketler</span>
            <h2 class="serif">Size uygun destek seviyesi</h2>
            @if($packageNote !== '')<p>{{ $packageNote }}</p>@endif
        </div>
        <div class="pkg-grid" style="--n:{{ max(1, min(count($packages), 3)) }};">
            @foreach($packages as $p)
                <div class="pkg{{ !empty($p['featured']) ? ' pkg-hi' : '' }}">
                    <div class="pkg-head">
                        <h3 class="serif">{{ $p['name'] }}</h3>
                        @if(($p['tag'] ?? '') !== '')<span class="pkg-tag">{{ $p['tag'] }}</span>@endif
                    </div>
                    @if(($p['desc'] ?? '') !== '')<p class="pkg-desc">{{ $p['desc'] }}</p>@endif
                    @if(!empty($p['items']))
                        <ul class="pkg-items">
                            @foreach($p['items'] as $item)<li>{{ $item }}</li>@endforeach
                        </ul>
                    @endif
                    <a href="{{ $applyUrl }}" class="btn btn-line pkg-btn" data-track="cta_clicked" data-ph-cta-name="package_apply" data-ph-location="partner_minimal_packages">Bu paketi görüşelim {!! $icon('arrow') !!}</a>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
