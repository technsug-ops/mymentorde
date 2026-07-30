{{-- ═══ SÜREÇ (büyük beyaz panel içinde) ═══ --}}
<div id="surec" class="wrap sec">
    <div class="panel">
        <div class="sec-head" style="max-width:560px;">
            <span class="pill-lbl">Nasıl çalışır</span>
            <h2 class="h2" style="margin-bottom:0;">Dört adımda Almanya'ya</h2>
        </div>
        <div class="grid" style="--gap:26px;--min:210px;--cols:{{ $cols(count($steps)) }};justify-content:flex-start;">
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
