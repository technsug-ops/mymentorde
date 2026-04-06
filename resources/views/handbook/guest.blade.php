@extends('guest.layouts.app')
@section('title', $lang === 'en' ? 'User Guide' : 'Kullanıcı Kılavuzu')

@push('styles')
    @include('handbook._style')
@endpush

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">📖 {{ $lang === 'en' ? 'User Guide' : 'Kullanıcı Kılavuzu' }}</h1>
        <p class="page-subtitle" style="margin:0;">{{ $lang === 'en' ? 'Everything you need to know about your application journey.' : 'Başvuru süreciniz hakkında bilmeniz gereken her şey.' }}</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <div class="handbook-lang">
            <a href="?lang=tr" class="{{ $lang === 'tr' ? 'active' : '' }}">TR</a>
            <a href="?lang=en" class="{{ $lang === 'en' ? 'active' : '' }}">EN</a>
        </div>
        <a href="{{ route('guest.handbook.download') }}?lang={{ $lang }}" class="btn alt" style="padding:7px 16px;font-size:.85rem;">
            ⬇ HTML {{ $lang === 'en' ? 'Download' : 'İndir' }}
        </a>
    </div>
</div>

<div class="handbook-wrap">
    <div class="handbook-body" id="handbookBody">
        {!! $html !!}
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function(){
    var body = document.getElementById('handbookBody');
    if (!body) return;

    // H2'leri akordiyona çevir
    var children = Array.from(body.children);
    var sections = [];
    var currentSection = null;

    children.forEach(function(el){
        if (el.tagName === 'H2') {
            if (currentSection) sections.push(currentSection);
            currentSection = { heading: el, content: [] };
        } else if (currentSection) {
            currentSection.content.push(el);
        }
    });
    if (currentSection) sections.push(currentSection);

    // İlk section öncesi içeriği (başlık, giriş) koru
    var intro = [];
    for (var i = 0; i < children.length; i++) {
        if (children[i].tagName === 'H2') break;
        intro.push(children[i]);
    }

    // Body'yi yeniden oluştur
    body.innerHTML = '';
    intro.forEach(function(el){ body.appendChild(el); });

    sections.forEach(function(sec, idx){
        var wrapper = document.createElement('div');
        wrapper.className = 'hb-section';

        var header = document.createElement('button');
        header.type = 'button';
        header.className = 'hb-section-head';
        header.innerHTML = '<span class="hb-section-icon">▸</span> ' + sec.heading.textContent;

        var content = document.createElement('div');
        content.className = 'hb-section-body';
        content.style.display = 'none';
        sec.content.forEach(function(el){ content.appendChild(el); });

        // H3'leri FAQ akordiyona çevir (SSS bölümü için)
        var h3s = content.querySelectorAll('h3');
        h3s.forEach(function(h3){
            var faqWrap = document.createElement('div');
            faqWrap.className = 'hb-faq-group';

            var faqHead = document.createElement('button');
            faqHead.type = 'button';
            faqHead.className = 'hb-faq-head';
            faqHead.innerHTML = '<span class="hb-faq-icon">▸</span> ' + h3.textContent;

            var faqBody = document.createElement('div');
            faqBody.className = 'hb-faq-body';
            faqBody.style.display = 'none';

            // H3'ten sonraki kardeşleri topla (sonraki H3 veya section sonuna kadar)
            var sibling = h3.nextElementSibling;
            while (sibling && sibling.tagName !== 'H3') {
                var next = sibling.nextElementSibling;
                faqBody.appendChild(sibling);
                sibling = next;
            }

            faqHead.addEventListener('click', function(){
                var open = faqBody.style.display !== 'none';
                faqBody.style.display = open ? 'none' : 'block';
                faqHead.querySelector('.hb-faq-icon').textContent = open ? '▸' : '▾';
                faqHead.classList.toggle('active', !open);
            });

            h3.parentNode.insertBefore(faqWrap, h3);
            faqWrap.appendChild(faqHead);
            faqWrap.appendChild(faqBody);
            h3.remove();
        });

        header.addEventListener('click', function(){
            var open = content.style.display !== 'none';
            content.style.display = open ? 'none' : 'block';
            header.querySelector('.hb-section-icon').textContent = open ? '▸' : '▾';
            header.classList.toggle('active', !open);
        });

        wrapper.appendChild(header);
        wrapper.appendChild(content);
        body.appendChild(wrapper);
    });

    // İlk section'ı otomatik aç
    var first = body.querySelector('.hb-section-head');
    if (first) first.click();
})();
</script>
@endsection
