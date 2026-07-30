{{-- ═══ HİZMETLER — çerçeveli tablo ızgarası (her zaman dolu) ═══ --}}
<div id="hizmetler" class="wrap sec">
    <div class="sec-head">
        <span class="lbl">Hizmetler</span>
        <h2 class="h2">Uçtan uca hizmet kapsamı</h2>
    </div>
    <div class="table" style="--cols:{{ $cols(count($services)) }};">
        @foreach($services as $i => $s)
            <div>
                <div class="t-head">
                    <span class="t-ic">{!! $icon($s['icon'] ?? 'default') !!}</span>
                    <div>
                        <span class="t-no">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <h3>{{ $s['title'] }}</h3>
                    </div>
                </div>
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
