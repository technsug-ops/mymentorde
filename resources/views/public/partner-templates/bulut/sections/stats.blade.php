{{-- ═══ İSTATİSTİKLER (partner girmediyse yok) ═══ --}}
@if(!empty($stats))
<div class="wrap sec">
    <div class="glass" style="padding:34px 30px;">
        <div class="stat-glass">
            @foreach($stats as $st)
                <div>
                    <div class="v">{{ $st['value'] }}</div>
                    <div class="l">{{ $st['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
