{{-- ═══ HİZMETLER ═══ --}}
<div id="hizmetler" class="wrap" style="padding-bottom:64px;">
    <div class="sec-head">
        <span class="lbl">Hizmetler</span>
        <h2 class="h2">Sürecin her adımında yanınızdayız</h2>
        <p class="lead">Başvurudan yerleşime kadar tüm süreci uzman ekibimizle yönetiyoruz.</p>
    </div>
    <div class="grid g-svc" style="--cols:{{ $cols(count($services)) }};">
        @foreach($services as $s)
            <div class="card">
                <span class="card-ic">{!! $icon($s['icon'] ?? 'default') !!}</span>
                <h3>{{ $s['title'] }}</h3>
                <p>{{ $s['desc'] }}</p>
                @if(!empty($s['items']))
                    <ul class="ticks">
                        @foreach($s['items'] as $item)
                            <li>{!! $icon('check') !!}{{ $item }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
</div>
