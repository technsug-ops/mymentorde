{{-- ═══ MERKEZ ALINTI + KALAN YORUMLAR — yalnız GERÇEK yorumlar ═══ --}}
@if(!empty($testimonials))
@php $lead = $testimonials[0]; $rest = array_slice($testimonials, 1); @endphp
<div class="wrap sec">
    <div class="center-quote">
        <span class="lbl">Öğrenci yorumları</span>
        <blockquote>“{{ $lead['text'] }}”</blockquote>
        @if(($lead['name'] ?? '') !== '' || ($lead['school'] ?? '') !== '')
            <cite>{{ $lead['name'] }}@if(($lead['school'] ?? '') !== '') · {{ $lead['school'] }}@endif</cite>
        @endif
    </div>
    @if(!empty($rest))
        <div class="grid" style="margin-top:44px;--cols:{{ $cols(count($rest)) }};">

        @foreach($testimonials as $t)
            <figure class="card quote" style="margin:0;">
                <p>“{{ $t['text'] }}”</p>
                @if(($t['name'] ?? '') !== '' || ($t['school'] ?? '') !== '')
                    <figcaption class="who">
                        <span class="avatar">{{ mb_strtoupper(mb_substr($t['name'] !== '' ? $t['name'] : $siteName, 0, 1)) }}</span>
                        <div>
                            @if(($t['name'] ?? '') !== '')<b>{{ $t['name'] }}</b>@endif
                            @if(($t['school'] ?? '') !== '')<span>{{ $t['school'] }}</span>@endif
                        </div>
                    </figcaption>
                @endif
            </figure>
        @endforeach
        </div>
    @endif
</div>
@endif
