{{-- ═══ SÜREÇ ═══ --}}
<div id="surec" class="wrap sec">
    <div class="sec-head center">
        <span class="lbl">Nasıl çalışır</span>
        <h2 class="h2">Dört adımda Almanya'ya</h2>
    </div>
    <div class="grid" style="--gap:20px;--min:200px;--cols:{{ $cols(count($steps)) }};">
        @foreach($steps as $st)
            <div class="step">
                <div class="n">{{ $st['no'] }}</div>
                <h3>{{ $st['title'] }}</h3>
                <p>{{ $st['desc'] }}</p>
            </div>
        @endforeach
    </div>
</div>
