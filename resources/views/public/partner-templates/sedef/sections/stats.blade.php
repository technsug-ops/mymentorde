{{-- ═══ İSTATİSTİK BLOĞU (partner girmediyse yok) ═══ --}}
@if(!empty($stats))
<div class="wrap sec">
    <div class="stat-block">
        @foreach($stats as $st)
            <div>
                <div class="v">{{ $st['value'] }}</div>
                <div class="l">{{ $st['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>
@endif
