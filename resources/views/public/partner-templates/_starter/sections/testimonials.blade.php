{{-- ═══ YORUMLAR — sadece partnerin girdiği GERÇEK yorumlar ═══ --}}
@if(!empty($testimonials))
<section class="wrap">
    <h2>Öğrenci Yorumları</h2>
    @foreach($testimonials as $t)
        <figure>
            <blockquote>{{ $t['text'] }}</blockquote>
            @if(($t['name'] ?? '') !== '' || ($t['school'] ?? '') !== '')
                <figcaption>
                    @if(($t['name'] ?? '') !== '')<b>{{ $t['name'] }}</b>@endif
                    @if(($t['school'] ?? '') !== '') — {{ $t['school'] }}@endif
                </figcaption>
            @endif
        </figure>
    @endforeach
</section>
@endif
