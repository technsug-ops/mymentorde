{{-- ═══ ÜNİVERSİTELER (partner girmediyse yok) ═══ --}}
@if(!empty($universities))
<div class="hair-row">
    <div class="hair-row-in" style="align-items:center;justify-content:center;">
        <div style="border:0;flex:0 0 auto;"><span class="lbl">Yerleşilen üniversiteler</span></div>
        @foreach($universities as $u)
            <div style="border:0;flex:0 0 auto;"><span style="font:400 19px/1 var(--serif);color:color-mix(in srgb, var(--accent) 52%, var(--faint));">{{ $u }}</span></div>
        @endforeach
    </div>
</div>
@endif
