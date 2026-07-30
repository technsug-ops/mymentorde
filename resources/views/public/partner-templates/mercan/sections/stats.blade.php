{{-- ═══ İSTATİSTİK STICKER'LARI (partner girmediyse yok) ═══ --}}
@if(!empty($stats))
<div class="wrap sec">
    <div class="stat-stickers">
        @foreach($stats as $st)
            <div class="stat-sticker">
                <div class="v">{{ $st['value'] }}</div>
                <div class="l">{{ $st['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>
@endif
