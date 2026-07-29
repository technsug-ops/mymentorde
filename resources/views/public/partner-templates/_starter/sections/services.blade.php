{{-- ═══ HİZMETLER (her zaman dolu) ═══ --}}
<section id="hizmetler" class="wrap">
    <h2>Hizmetlerimiz</h2>
    @foreach($services as $s)
        <article>
            <div style="color:var(--accent);">{!! $icon($s['icon'] ?? 'default') !!}</div>
            <h3>{{ $s['title'] }}</h3>
            <p>{{ $s['desc'] }}</p>
            @if(!empty($s['items']))
                <ul>
                    @foreach($s['items'] as $item)<li>{{ $item }}</li>@endforeach
                </ul>
            @endif
        </article>
    @endforeach
</section>
