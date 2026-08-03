{{-- ═══ SÜREÇ ═══ (id=surec → navLinks anchor'ı)
     Adımlar sözleşmeden ($steps) gelir — sabit metin yazma. --}}
<section id="surec" class="sec sec-top">
    <div class="wrap">
        <div class="sec-head">
            <span class="eyebrow acc">Nasıl Çalışır</span>
            <h2 class="serif">Adım adım, yanınızda</h2>
        </div>
        <div class="steps" style="--n:{{ max(1, count($steps)) }};">
            @foreach($steps as $st)
                <div class="step">
                    <span class="sn">{{ sprintf('%02d', (int) $st['no']) }}</span>
                    <h3>{{ $st['title'] }}</h3>
                    <p>{{ $st['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
