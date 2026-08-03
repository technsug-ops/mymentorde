{{-- ═══ SÜREÇ ═══ (id=surec → navLinks anchor'ı)
     Adımlar sözleşmeden ($steps) gelir — sabit metin yazma. --}}
<section id="surec" class="sec-bg-soft">
    <div class="container">
        <div class="sec-head center">
            <span class="sec-label">Nasıl Çalışır</span>
            <h2 class="sec-title">Başvurudan Almanya'ya, Adım Adım Yanınızdayız</h2>
            <p class="sec-lead">Süreci sizin için basitleştirdik — siz hedefinize odaklanın, gerisini biz halledelim.</p>
        </div>
        <div class="proc" style="--n:{{ max(1, count($steps)) }};">
            @foreach($steps as $st)
                <div class="proc-step">
                    <div class="proc-num">{{ $st['no'] }}</div>
                    <h3>{{ $st['title'] }}</h3>
                    <p>{{ $st['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
