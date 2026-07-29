{{-- ═══ PAKETLER (boşsa hiç basma — default paket ÜRETME) ═══ --}}
@if(!empty($packages))
<section id="paketler" class="wrap">
    <h2>Destek Paketleri</h2>
    @if($packageNote !== '')<p>{{ $packageNote }}</p>@endif
    @foreach($packages as $p)
        <article @if(!empty($p['featured'])) style="border:2px solid var(--accent);" @endif>
            <h3>{{ $p['name'] }}</h3>
            @if(($p['tag'] ?? '') !== '')<span>{{ $p['tag'] }}</span>@endif
            @if(($p['desc'] ?? '') !== '')<p>{{ $p['desc'] }}</p>@endif
            @if(!empty($p['items']))
                <ul>@foreach($p['items'] as $item)<li>{{ $item }}</li>@endforeach</ul>
            @endif
            <a href="{{ $applyUrl }}" data-track="cta_clicked" data-ph-cta-name="package_apply" data-ph-location="partner_starter_packages">Bu paketi görüşelim</a>
        </article>
    @endforeach
</section>
@endif
