{{-- ═══ HİZMETLER (her zaman dolu) ═══ --}}
<div id="hizmetler" class="sec-white">
    <div class="wrap sec">
        <div class="sec-head">
            <span class="lbl">Hizmetler</span>
            <h2 class="h2">Uçtan uca, sade ve net</h2>
        </div>
        <div class="hair" style="--cols:{{ $cols(count($services)) }};">
            @foreach($services as $s)
                <div class="cell">
                    <span class="cell-ic">{!! $icon($s['icon'] ?? 'default') !!}</span>
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
    </div>
</div>
