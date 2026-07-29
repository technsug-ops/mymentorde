{{-- ═══ S.S.S. — JS YOK: <details>/<summary> ile aç/kapa ═══ --}}
@if(!empty($faq))
<section id="sss" class="wrap">
    <h2>Sıkça Sorulan Sorular</h2>
    @foreach($faq as $f)
        <details><summary>{{ $f['q'] }}</summary><p>{{ $f['a'] }}</p></details>
    @endforeach
</section>
@endif
