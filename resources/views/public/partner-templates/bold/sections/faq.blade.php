{{-- ═══ S.S.S. ═══ (id=sss → navLinks anchor'ı)
     Akordeon <details> ile — public sayfada JS yok (CSP). --}}
@if(!empty($faq))
<section id="sss" class="sec">
    <div class="wrap">
        <div class="sec-head c"><span class="kick c">S.S.S.</span><h2>Sıkça Sorulan Sorular</h2></div>
        <div class="faq">
            @foreach($faq as $f)
                <details class="faq-item">
                    <summary>
                        <span>{{ $f['q'] }}</span>
                        <span class="faq-sign"></span>
                    </summary>
                    <div class="faq-a">{{ $f['a'] }}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif
