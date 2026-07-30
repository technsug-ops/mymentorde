{{-- ═══ İNCE ÇİZGİ İSTATİSTİK SATIRI (partner girmediyse yok) ═══ --}}
@if(!empty($stats))
<div class="hair-row">
    <div class="hair-row-in">
        @foreach($stats as $st)
            <div>
                <div class="v">{{ $st['value'] }}</div>
                <div class="l">{{ $st['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>
@endif
