{{-- ═══ HİZMETLER ═══ (id=hizmetler → navLinks anchor'ı) --}}
<section id="hizmetler" class="sec-bg-white">
    <div class="container">
        <div class="sec-head center">
            <span class="sec-label">Hizmetler</span>
            <h2 class="sec-title">Almanya Eğitim Sürecinin Her Adımında</h2>
            <p class="sec-lead">Başvurudan yerleşime kadar tüm süreci profesyonel ekibimizle yönetiyoruz.</p>
        </div>
        <div class="svc-grid">
            @foreach($services as $s)
                <div class="svc">
                    <div class="svc-icon">{!! $icon($s['icon'] ?? 'default') !!}</div>
                    <h3>{{ $s['title'] }}</h3>
                    @if(!empty($s['desc']))<p>{{ $s['desc'] }}</p>@endif
                    @if(!empty($s['items']))
                        <ul class="svc-items">
                            @foreach($s['items'] as $it)
                                <li>{!! $icon('check') !!} {{ $it }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
