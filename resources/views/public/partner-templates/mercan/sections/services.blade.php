{{-- ═══ HİZMETLER — bento: ilk 2 büyük, kalanlar küçük (her zaman dolu) ═══ --}}
<div id="hizmetler" class="wrap sec">
    <div class="sec-head">
        <span class="lbl">Hizmetler</span>
        <h2 class="h2">Her şey tek pakette</h2>
    </div>
    <div class="grid" style="--min:280px;--cols:{{ $cols(count($svcBig)) }};margin-bottom:16px;">
        @foreach($svcBig as $s)
            <div class="card">
                <span class="card-ic">{!! $icon($s['icon'] ?? 'default') !!}</span>
                <h3>{{ $s['title'] }}</h3>
                <p>{{ $s['desc'] }}</p>
                @if(!empty($s['items']))
                    <ul class="ticks">
                        @foreach($s['items'] as $item)<li>{!! $icon('check') !!}{{ $item }}</li>@endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
    @if(!empty($svcSmall))
        <div class="grid" style="--min:200px;--cols:{{ $cols(count($svcSmall)) }};">
            @foreach($svcSmall as $s)
                <div class="card card-sm">
                    <span class="card-ic card-ic-2">{!! $icon($s['icon'] ?? 'default') !!}</span>
                    <h3>{{ $s['title'] }}</h3>
                    <p>{{ $s['desc'] }}</p>
                </div>
            @endforeach
        </div>
    @endif
</div>
