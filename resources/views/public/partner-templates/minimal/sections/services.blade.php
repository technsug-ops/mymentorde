{{-- ═══ HİZMETLER ═══ (id=hizmetler → navLinks anchor'ı) --}}
<section id="hizmetler" class="sec sec-top">
    <div class="wrap">
        <div class="sec-head">
            <span class="eyebrow acc">Hizmetler</span>
            <h2 class="serif">Almanya eğitim sürecinin her adımında</h2>
            <p>Başvurudan yerleşime kadar tüm süreci uçtan uca yönetiyoruz.</p>
        </div>
        <div class="svc-grid">
            @foreach($services as $i => $s)
                <div class="svc">
                    <div class="svc-n">{{ sprintf('%02d', $i + 1) }}</div>
                    <div>
                        <h3>{{ $s['title'] }}</h3>
                        @if(!empty($s['desc']))<p>{{ $s['desc'] }}</p>@endif
                        @if(!empty($s['items']))
                            <ul class="svc-items">@foreach($s['items'] as $it)<li>{{ $it }}</li>@endforeach</ul>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
