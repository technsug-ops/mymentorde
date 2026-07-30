{{-- ═══ ÜNİVERSİTELER (partner girmediyse yok) ═══ --}}
@if(!empty($universities))
<div class="wrap" style="padding-bottom:64px;">
    <div class="glass" style="padding:22px 28px;">
        <div class="unis">
            <span class="unis-lbl">Öğrencilerimizin yerleştiği üniversiteler</span>
            @foreach($universities as $u)<span class="u">{{ $u }}</span>@endforeach
        </div>
    </div>
</div>
@endif
