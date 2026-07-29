{{-- ═══ HİZMETLER (her zaman dolu) ═══ --}}
<div id="hizmetler" class="wrap sec">
    <div class="sec-head">
        <span class="lbl">Hizmetler</span>
        <h2 class="h2">Uçtan uca, tek çatı altında</h2>
    </div>
    <div class="grid" style="--cols:{{ $cols(count($services)) }};">
        @foreach($services as $s)
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
</div>
