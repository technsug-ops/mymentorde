{{-- ═══ NEDEN BİZ (her zaman dolu) ═══ --}}
<section class="wrap">
    <h2>Neden Biz</h2>
    @foreach($whyUs as $w)
        <article><div style="color:var(--accent);">{!! $icon($w['icon']) !!}</div><h3>{{ $w['title'] }}</h3><p>{{ $w['desc'] }}</p></article>
    @endforeach
</section>

{{-- ═══ EKİP (boşsa hiç basma) ═══ --}}
@if(!empty($team))
<section class="wrap">
    <h2>Ekibimiz</h2>
    @foreach($team as $m)
        <div>
            @if(($m['photo'] ?? '') !== '')
                <img src="{{ $m['photo'] }}" alt="{{ $m['name'] }}" style="width:72px;height:72px;border-radius:50%;object-fit:cover;">
            @else
                <div style="width:72px;height:72px;border-radius:50%;color:var(--accent);">{{ mb_substr($m['name'], 0, 1) }}</div>
            @endif
            <div>{{ $m['name'] }}</div>
            @if(($m['title'] ?? '') !== '')<div>{{ $m['title'] }}</div>@endif
        </div>
    @endforeach
</section>
@endif
