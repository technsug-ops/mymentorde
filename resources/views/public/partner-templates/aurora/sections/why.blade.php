{{-- ═══ NEDEN BİZ + EKİP + ROZET ═══
     Kartlar sözleşmeden ($whyUs) gelir. Ekip ve MentorDE rozeti bu bölümün parçası
     (PartnerSiteSections: 'why' => 'Neden biz + ekip'). --}}
<section class="sec-bg-soft">
    <div class="container">
        <div class="sec-head center">
            <span class="sec-label">Neden Biz</span>
            <h2 class="sec-title">Doğru Rehberle Emin Adımlar</h2>
            <p class="sec-lead">Sadece başvuru değil, Almanya'daki ilk gününüze kadar güvenilir bir yol arkadaşı.</p>
        </div>

        <div class="why" style="--n:{{ max(1, min(count($whyUs), 4)) }};">
            @foreach($whyUs as $w)
                <div class="why-card">
                    <div class="why-ic">{!! $icon($w['icon'] ?? 'default') !!}</div>
                    <div>
                        <h3>{{ $w['title'] }}</h3>
                        <p>{{ $w['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Ekip — partner girmediyse hiç basılmaz --}}
        @if(!empty($team))
            <div id="ekip" class="team-grid" style="margin-top:44px;">
                @foreach($team as $m)
                    <div class="team-card">
                        @if(!empty($m['photo']))
                            <img class="team-photo" src="{{ $m['photo'] }}" alt="{{ $m['name'] }}">
                        @else
                            <div class="team-photo">{{ Str::upper(Str::substr($m['name'], 0, 1)) }}</div>
                        @endif
                        <h3>{{ $m['name'] }}</h3>
                        @if(!empty($m['title']))<p>{{ $m['title'] }}</p>@endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Rozet kapalıysa MentorDE adı sayfada HİÇ geçmemeli (tam white-label) --}}
        @if($showBadge)
            <div class="trust" style="margin-top:44px;">
                <div class="trust-badge">
                    {!! $icon('shield') !!}
                    <div>
                        <div class="tb-t">{{ config('brand.name', 'MentorDE') }} Yetkili Partneri</div>
                        <div class="tb-s">Resmi partner ağı üzerinden güvenli süreç</div>
                    </div>
                </div>
                <p style="margin:0;color:var(--muted);font-size:15px;max-width:420px;">
                    {{ $siteName }}, {{ config('brand.name', 'MentorDE') }} altyapısı ve uzman ekibiyle
                    başvuru, vize ve yerleşim süreçlerinizi uçtan uca yönetir.
                </p>
            </div>
        @endif
    </div>
</section>
