{{-- ═══ S.S.S. (JS'siz: <details>) ═══ --}}
@if(!empty($faq))
<div id="sss" class="wrap" style="padding:8px 26px 64px;">
    <div class="sec-head" style="max-width:560px;">
        <span class="lbl">S.S.S.</span>
        <h2 class="h2" style="margin-bottom:0;">Aklınızdaki sorular</h2>
    </div>
    <div class="faq">
        @foreach($faq as $i => $f)
            <details @if($i === 0) open @endif>
                <summary>{{ $f['q'] }}<span class="ico">+</span></summary>
                <p>{{ $f['a'] }}</p>
            </details>
        @endforeach
    </div>
</div>
@endif
