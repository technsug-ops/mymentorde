{{-- ═══ S.S.S. (JS YOK: <details>/<summary>) ═══ --}}
@if(!empty($faq))
<div id="sss" class="wrap sec">
    <div class="sec-head center">
        <span class="lbl">S.S.S.</span>
        <h2 class="h2">Aklındaki sorular</h2>
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
