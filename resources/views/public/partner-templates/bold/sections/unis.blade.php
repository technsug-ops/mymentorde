{{-- ═══ ÜNİVERSİTE ŞERİDİ ═══ (partner girmediyse yok) --}}
@if(!empty($universities))
<section class="sec" style="padding:56px 0;">
    <div class="wrap">
        <div class="uni-strip">
            <span class="kick">Yerleştiğimiz Üniversiteler</span>
            <div class="uni-row">
                @foreach($universities as $u)
                    <span class="uni">{{ $u }}</span>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
