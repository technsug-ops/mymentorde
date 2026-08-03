{{-- ═══ S.S.S. ═══ (id=sss → navLinks anchor'ı)
     Akordeon <details> ile — public sayfada JS yok (CSP). --}}
@if(!empty($faq))
<section id="sss" class="sec-bg-white">
    <div class="container">
        <div class="sec-head center">
            <span class="sec-label">S.S.S.</span>
            <h2 class="sec-title">Sıkça Sorulan Sorular</h2>
            <p class="sec-lead">Aklınıza takılanları burada topladık; kalanı için bize yazın.</p>
        </div>
        <div class="faq">
            @foreach($faq as $f)
                <details class="faq-item">
                    <summary>
                        <span>{{ $f['q'] }}</span>
                        <span class="faq-chev">{!! $icon('arrow') !!}</span>
                    </summary>
                    <div class="faq-a">{{ $f['a'] }}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif
