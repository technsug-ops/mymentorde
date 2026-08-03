{{-- ═══ ÜNİVERSİTE ŞERİDİ ═══ (partner girmediyse yok) --}}
@if(!empty($universities))
<section class="sec sec-top" style="padding:52px 0;">
    <div class="wrap">
        <span class="eyebrow" style="display:block;margin-bottom:22px;">Öğrencilerimizin yerleştiği üniversiteler</span>
        <div class="uni-list">
            @foreach($universities as $u)
                <span class="uni">{{ $u }}</span>
            @endforeach
        </div>
    </div>
</section>
@endif
