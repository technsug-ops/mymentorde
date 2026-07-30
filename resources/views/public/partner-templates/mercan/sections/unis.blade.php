{{-- ═══ DÖNDÜRÜLMÜŞ ÜNİVERSİTE ŞERİDİ (partner girmediyse yok) ═══ --}}
@if(!empty($universities))
<div class="uni-strip">
    <div class="uni-strip-in">
        <span class="lbl-w">Yerleşilen üniversiteler</span>
        @foreach($universities as $u)<span class="u">{{ $u }}</span>@endforeach
    </div>
</div>
@endif
