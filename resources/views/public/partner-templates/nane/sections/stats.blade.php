{{-- ═══ İSTATİSTİKLER (partner girmediyse yok — uydurma rakam üretilmez) ═══ --}}
@if(!empty($stats))
<div class="wrap" style="padding-bottom:56px;">
    <div class="hair" style="--cols:{{ $cols(count($stats)) }};--min:180px;">
        @foreach($stats as $st)
            <div class="cell" style="padding:26px 22px;text-align:center;align-items:center;">
                <div style="font:600 30px/1 var(--display);letter-spacing:-.5px;">{{ $st['value'] }}</div>
                <div style="font:600 11px/1.3 var(--mono);color:var(--faint);text-transform:uppercase;letter-spacing:.08em;margin-top:7px;">{{ $st['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>
@endif
