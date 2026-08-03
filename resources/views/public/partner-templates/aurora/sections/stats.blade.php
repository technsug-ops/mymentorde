{{-- ═══ İSTATİSTİK BANDI ═══
     Sadece partnerin girdiği sayılar — boşsa bölüm hiç basılmaz (uydurma rakam yok). --}}
@if(!empty($stats))
<section class="sec-bg-soft">
    <div class="container">
        <div class="stat-band" style="--n:{{ min(count($stats), 4) }};">
            @foreach($stats as $st)
                <div class="sb">
                    <div class="sb-v">{{ $st['value'] }}</div>
                    <div class="sb-l">{{ $st['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
