{{-- ═══ HİZMETLER — yatay cam kartlar (her zaman dolu) ═══ --}}
<div id="hizmetler" class="wrap sec">
    <div class="sec-head">
        <span class="pill-lbl">Hizmetler</span>
        <h2 class="h2">Sürecin tamamı, tek çatı altında</h2>
        <p>Tek plan, tek sorumlu danışman, net bir takvim.</p>
    </div>
    <div class="grid" style="--min:320px;--cols:{{ $cols(count($services)) }};">
        @foreach($services as $s)
            <div class="glass svc">
                <span class="svc-ic">{!! $icon($s['icon'] ?? 'default') !!}</span>
                <div>
                    <h3>{{ $s['title'] }}</h3>
                    <p>{{ $s['desc'] }}</p>
                    @if(!empty($s['items']))
                        <div class="tags">
                            @foreach($s['items'] as $item)<span>{{ $item }}</span>@endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
