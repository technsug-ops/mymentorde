{{-- ═══ ÖĞRENCİ YORUMLARI ═══
     Sadece partnerin girdiği gerçek yorumlar — boşsa bölüm hiç basılmaz. --}}
@if(!empty($testimonials))
<section class="sec-bg-white">
    <div class="container">
        <div class="sec-head center">
            <span class="sec-label">Öğrenci Yorumları</span>
            <h2 class="sec-title">Başarı Hikayeleriyle Büyüyoruz</h2>
            <p class="sec-lead">Yolculuğunu bizimle tamamlayan öğrencilerin deneyimleri.</p>
        </div>
        <div class="testi-grid">
            @foreach($testimonials as $t)
                <div class="testi">
                    <p class="testi-q">"{{ $t['text'] }}"</p>
                    @if(($t['name'] ?? '') !== '' || ($t['school'] ?? '') !== '')
                        <div class="testi-who">
                            <div class="testi-av">{{ mb_substr($t['name'] !== '' ? $t['name'] : $siteName, 0, 1) }}</div>
                            <div>
                                @if(($t['name'] ?? '') !== '')<div class="testi-name">{{ $t['name'] }}</div>@endif
                                @if(($t['school'] ?? '') !== '')<div class="testi-role">{{ $t['school'] }}</div>@endif
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
