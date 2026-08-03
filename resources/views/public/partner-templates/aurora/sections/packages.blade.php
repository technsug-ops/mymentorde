{{-- ═══ DESTEK PAKETLERİ ═══ (id=paketler → navLinks anchor'ı; partner girmediyse yok) --}}
@if(!empty($packages))
<section id="paketler" class="sec-bg-soft">
    <div class="container">
        <div class="sec-head center">
            <span class="sec-label">Paketler</span>
            <h2 class="sec-title">Size Uygun Destek Seviyesi</h2>
            @if($packageNote !== '')<p class="sec-lead">{{ $packageNote }}</p>@endif
        </div>
        <div class="pkg-grid" style="--n:{{ max(1, min(count($packages), 3)) }};">
            @foreach($packages as $p)
                <div class="pkg{{ !empty($p['featured']) ? ' pkg-hi' : '' }}">
                    <div class="pkg-top">
                        <h3>{{ $p['name'] }}</h3>
                        @if(($p['tag'] ?? '') !== '')<span class="pkg-tag">{{ $p['tag'] }}</span>@endif
                    </div>
                    @if(($p['desc'] ?? '') !== '')<p class="pkg-desc">{{ $p['desc'] }}</p>@endif
                    @if(!empty($p['items']))
                        <ul class="pkg-items">
                            @foreach($p['items'] as $item)
                                <li>{!! $icon('check') !!} {{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <a href="{{ $applyUrl }}" class="pkg-btn" data-track="cta_clicked" data-ph-cta-name="package_apply" data-ph-location="partner_aurora_packages">Bu paketi görüşelim {!! $icon('arrow') !!}</a>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
