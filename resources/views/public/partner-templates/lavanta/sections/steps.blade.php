{{-- ═══ SÜREÇ ═══ --}}
<div id="surec" class="proc">
    <div class="wrap sec">
        <div class="sec-head" style="max-width:560px;margin-bottom:42px;">
            <span class="lbl">Nasıl çalışır</span>
            <h2 class="h2" style="margin-bottom:0;">Dört adımda Almanya'ya</h2>
        </div>
        <div class="grid g-step" style="--cols:{{ $cols(count($steps)) }};">
            @foreach($steps as $st)
                <div class="step">
                    <div class="step-n">{{ $st['no'] }}</div>
                    <h3>{{ $st['title'] }}</h3>
                    <p>{{ $st['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
