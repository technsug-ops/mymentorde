{{-- ═══ ÖĞRENCİ YORUMLARI ═══
     Sadece partnerin girdiği gerçek yorumlar — boşsa bölüm hiç basılmaz. --}}
@if(!empty($testimonials))
<section class="sec sec-top">
    <div class="wrap">
        <div class="sec-head"><span class="eyebrow acc">Öğrenci Yorumları</span><h2 class="serif">Başarı hikayeleriyle büyüyoruz</h2></div>
        <div class="q-grid">
            @foreach($testimonials as $t)
                <div class="q">
                    <div class="qm">"</div>
                    <blockquote>{{ $t['text'] }}</blockquote>
                    @if(($t['name'] ?? '') !== '' || ($t['school'] ?? '') !== '')
                        <div class="qw">
                            @if(($t['name'] ?? '') !== '')<b>{{ $t['name'] }}</b>@endif
                            @if(($t['school'] ?? '') !== ''){{ ($t['name'] ?? '') !== '' ? ' — ' : '' }}{{ $t['school'] }}@endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
