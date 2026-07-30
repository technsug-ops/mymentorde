{{-- ═══ ÜNİVERSİTELER (partner girmediyse yok) ═══ --}}
@if(!empty($universities))
<div class="wrap" style="padding-bottom:56px;">
    <div class="unis">
        <span class="unis-lbl">Öğrencilerimizin yerleştiği üniversiteler</span>
        @foreach($universities as $u)<span class="u">{{ $u }}</span>@endforeach
    </div>
</div>
@endif
