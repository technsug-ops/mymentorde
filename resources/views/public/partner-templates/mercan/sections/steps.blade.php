{{-- ═══ SÜREÇ — kesikli bağlantı çizgisi + gradient daireler ═══ --}}
<div id="surec" class="sec-white">
    <div class="wrap sec">
        <div class="sec-head" style="max-width:560px;">
            <span class="lbl">Nasıl çalışır</span>
            <h2 class="h2">Dört adımda hazırsın</h2>
        </div>
        <div class="timeline" style="--cols:{{ $cols(count($steps)) }};">
            <div class="tl-line"></div>
            @foreach($steps as $st)
                <div class="step">
                    <div class="n">{{ $st['no'] }}</div>
                    <h3>{{ $st['title'] }}</h3>
                    <p>{{ $st['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
