{{-- ═══ SÜREÇ (her zaman dolu) ═══ --}}
<section id="surec" class="wrap">
    <h2>Nasıl Çalışır</h2>
    @foreach($steps as $st)
        <article><div>{{ $st['no'] }}</div><h3>{{ $st['title'] }}</h3><p>{{ $st['desc'] }}</p></article>
    @endforeach
</section>
