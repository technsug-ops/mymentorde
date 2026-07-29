{{-- ═══ SÜREÇ (hairline ızgara, büyük accent numara) ═══ --}}
<div id="surec" class="wrap sec">
    <div class="sec-head" style="max-width:560px;">
        <span class="lbl">Nasıl çalışır</span>
        <h2 class="h2">Dört adımda Almanya'ya</h2>
    </div>
    <div class="proc" style="--cols:{{ $cols(count($steps)) }};">
        @foreach($steps as $st)
            <div>
                <div class="n">{{ $st['no'] }}</div>
                <h3>{{ $st['title'] }}</h3>
                <p>{{ $st['desc'] }}</p>
            </div>
        @endforeach
    </div>
</div>
