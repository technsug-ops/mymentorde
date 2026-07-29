{{-- ═══ İSTATİSTİK BANDI (partner girmediyse yok — uydurma rakam üretilmez) ═══ --}}
@if(!empty($stats))
<div class="band">
    <div class="band-in">
        @foreach($stats as $st)
            <div>
                <div class="v">{{ $st['value'] }}</div>
                <div class="l">{{ $st['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>
@endif
