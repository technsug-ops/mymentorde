{{-- ═══ İSTATİSTİK BANDI ═══
     Sadece partnerin girdiği sayılar — boşsa bölüm hiç basılmaz. --}}
@if(!empty($stats))
<section class="band">
    <div class="wrap">
        <div class="band-grid" style="--n:{{ min(count($stats), 4) }};">
            @foreach($stats as $st)<div class="bi"><div class="bv">{{ $st['value'] }}</div><div class="bl">{{ $st['label'] }}</div></div>@endforeach
        </div>
    </div>
</section>
@endif
