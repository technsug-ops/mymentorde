{{-- ═══ SÜREÇ ═══ (id=surec → navLinks anchor'ı)
     Adımlar sözleşmeden ($steps) gelir — sabit metin yazma. --}}
<section id="surec" class="sec">
    <div class="wrap">
        <div class="sec-head c"><span class="kick c">Nasıl Çalışır</span><h2>Adım Adım Yanınızdayız</h2></div>
        <div class="steps" style="--n:{{ max(1, count($steps)) }};">
            @foreach($steps as $st)
                <div class="step">
                    <div class="sn">{{ $st['no'] }}</div>
                    <h3>{{ $st['title'] }}</h3>
                    <p>{{ $st['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
