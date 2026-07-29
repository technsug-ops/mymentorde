{{-- ═══ KOYU BANT: alıntı + istatistik ═══
    Alıntı partnerin GERÇEK ilk yorumundan gelir; yorum da istatistik de yoksa bant basılmaz. --}}
@php $bandQuote = $testimonials[0] ?? null; @endphp
@if(!empty($stats) || $bandQuote)
<div class="band">
    <div class="band-in">
        @if($bandQuote)
            <div>
                <blockquote>“{{ $bandQuote['text'] }}”</blockquote>
                @if(($bandQuote['name'] ?? '') !== '' || ($bandQuote['school'] ?? '') !== '')
                    <cite>— {{ $bandQuote['name'] }}@if(($bandQuote['school'] ?? '') !== '') · {{ $bandQuote['school'] }}@endif</cite>
                @endif
            </div>
        @endif
        @if(!empty($stats))
            <div class="band-stats{{ $bandQuote ? ' band-div' : '' }}">
                @foreach($stats as $st)
                    <div>
                        <div class="v">{{ $st['value'] }}</div>
                        <div class="l">{{ $st['label'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endif
