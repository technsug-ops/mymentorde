{{-- ═══ ÜNİVERSİTE ŞERİDİ (partner girmediyse yok) ═══ --}}
@if(!empty($universities))
<div class="unis">
    <div class="unis-in">
        <span class="lbl">Yerleşilen üniversiteler</span>
        @foreach($universities as $u)<span class="u">{{ $u }}</span>@endforeach
    </div>
</div>
@endif
