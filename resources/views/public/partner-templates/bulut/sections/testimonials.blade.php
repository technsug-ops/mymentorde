{{-- ═══ YORUMLAR — yalnız partnerin girdiği GERÇEK yorumlar ═══ --}}
@if(!empty($testimonials))
<div class="wrap sec">
    <div class="sec-head">
        <span class="pill-lbl">Öğrenci yorumları</span>
        <h2 class="h2">Deneyimleriyle anlatıyorlar</h2>
    </div>
    <div class="grid" style="--min:280px;--cols:{{ $cols(count($testimonials)) }};">

        @foreach($testimonials as $t)
            <figure class="glass quote" style="margin:0;">
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
</div>
@endif
