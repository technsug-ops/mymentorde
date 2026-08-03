{{-- ═══ ÜNİVERSİTE ŞERİDİ ═══ (partner girmediyse yok) --}}
@if(!empty($universities))
<section class="sec-bg-white" style="padding:44px 0;">
    <div class="container">
        <div class="unis">
            <span class="unis-lbl">Öğrencilerimizin yerleştiği üniversiteler</span>
            <div class="unis-row">
                @foreach($universities as $u)
                    <span class="u">{{ $u }}</span>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
