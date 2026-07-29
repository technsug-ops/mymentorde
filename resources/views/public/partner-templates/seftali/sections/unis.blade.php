{{-- ═══ ÜNİVERSİTE SATIRI (partner girmediyse yok) ═══ --}}
@if(!empty($universities))
<div class="unis">
    <span class="unis-lbl">Yerleşilen üniversiteler</span>
    @foreach($universities as $u)<span class="u">{{ $u }}</span>@endforeach
</div>
@endif
