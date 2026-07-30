{{-- ═══ İSTATİSTİKLER (partner girmediyse yok) ═══ --}}
@if(!empty($stats))
<div class="wrap" style="padding-bottom:56px;">
    <div class="stat-row">
        @foreach($stats as $st)
            <div>
                <div class="v">{{ $st['value'] }}</div>
                <div class="l">{{ $st['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>
@endif
