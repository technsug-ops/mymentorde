{{-- ═══ HİZMETLER ═══ (id=hizmetler → navLinks anchor'ı) --}}
<section id="hizmetler" class="sec">
    <div class="wrap">
        <div class="sec-head c"><span class="kick c">Hizmetler</span><h2>Almanya Eğitim Sürecinin Her Adımında</h2><p>Başvurudan yerleşime kadar tüm süreci profesyonel ekibimizle yönetiyoruz.</p></div>
        <div class="svc-grid">
            @foreach($services as $i => $s)
                <div class="svc">
                    <span class="svc-n">{{ sprintf('%02d', $i + 1) }}</span>
                    <div class="svc-ic">{!! $icon($s['icon'] ?? 'default') !!}</div>
                    <h3>{{ $s['title'] }}</h3>
                    @if(!empty($s['desc']))<p>{{ $s['desc'] }}</p>@endif
                    @if(!empty($s['items']))<ul class="svc-items">@foreach($s['items'] as $it)<li>{!! $icon('check') !!} {{ $it }}</li>@endforeach</ul>@endif
                </div>
            @endforeach
        </div>
    </div>
</section>
