{{-- ═══ YORUM SPOTLIGHT + kalanlar — yalnız partnerin GERÇEK yorumları ═══ --}}
@if(!empty($testimonials))
@php $spot = $testimonials[0]; $rest = array_slice($testimonials, 1); @endphp
<div class="wrap sec">
    <div class="spot">
        <div class="glow-blob b"></div>
        <span class="lbl">Öğrenci yorumları</span>
        <blockquote>“{{ $spot['text'] }}”</blockquote>
        @if(($spot['name'] ?? '') !== '' || ($spot['school'] ?? '') !== '')
            <div class="who">
                <span class="avatar">{{ mb_strtoupper(mb_substr($spot['name'] !== '' ? $spot['name'] : $siteName, 0, 1)) }}</span>
                <div style="text-align:left;">
                    @if(($spot['name'] ?? '') !== '')<b>{{ $spot['name'] }}</b>@endif
                    @if(($spot['school'] ?? '') !== '')<span>{{ $spot['school'] }}</span>@endif
                </div>
            </div>
        @endif
    </div>

    @if(!empty($rest))
        <div class="grid" style="--cols:{{ $cols(count($rest)) }};margin-top:40px;">
            @foreach($rest as $t)
                <figure class="quote" style="margin:0;">
                    <p>“{{ $t['text'] }}”</p>
                    @if(($t['name'] ?? '') !== '' || ($t['school'] ?? '') !== '')
                        <figcaption class="who" style="justify-content:flex-start;">
                            <span class="avatar" style="width:38px;height:38px;font-size:14px;">{{ mb_strtoupper(mb_substr($t['name'] !== '' ? $t['name'] : $siteName, 0, 1)) }}</span>
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
