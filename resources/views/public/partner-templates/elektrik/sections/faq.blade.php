{{-- ═══ S.S.S. (JS YOK: <details>/<summary>) ═══ --}}
@if(!empty($faq))
<div id="sss" class="wrap" style="padding:8px 26px 64px;">
    <div class="sec-head" style="margin-bottom:32px;">
        <span class="lbl">S.S.S.</span>
        <h2 class="h2">Sık sorulan sorular</h2>
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
