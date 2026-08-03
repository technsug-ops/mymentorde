{{-- ═══ ÖĞRENCİ YORUMLARI ═══
     Sadece partnerin girdiği gerçek yorumlar — boşsa bölüm hiç basılmaz. --}}
@if(!empty($testimonials))
<section class="sec">
    <div class="wrap">
        <div class="sec-head c"><span class="kick c">Öğrenci Yorumları</span><h2>Başarı Hikayeleriyle Büyüyoruz</h2></div>
        <div class="q-grid">
            @foreach($testimonials as $t)
                <div class="qc">
                    <blockquote>{{ $t['text'] }}</blockquote>
                    @if(($t['name'] ?? '') !== '' || ($t['school'] ?? '') !== '')
                        <div class="who">
                            <div class="av">{{ mb_substr($t['name'] !== '' ? $t['name'] : $siteName, 0, 1) }}</div>
                            <div>
                                @if(($t['name'] ?? '') !== '')<div class="nm">{{ $t['name'] }}</div>@endif
                                @if(($t['school'] ?? '') !== '')<div class="rl">{{ $t['school'] }}</div>@endif
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
