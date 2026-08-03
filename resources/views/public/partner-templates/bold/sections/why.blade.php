{{-- ═══ NEDEN BİZ + EKİP + ROZET ═══
     Kartlar sözleşmeden ($whyUs). Ekip ve MentorDE rozeti bu bölümün parçası
     (PartnerSiteSections: 'why' => 'Neden biz + ekip'). --}}
<section class="sec">
    <div class="wrap">
        <div class="sec-head c"><span class="kick c">Neden Biz</span><h2>Doğru Rehberle Emin Adımlar</h2></div>

        <div class="why-grid" style="--n:{{ max(1, min(count($whyUs), 4)) }};">
            @foreach($whyUs as $w)
                <div class="wc">
                    <div class="wc-ic">{!! $icon($w['icon'] ?? 'default') !!}</div>
                    <div>
                        <h3>{{ $w['title'] }}</h3>
                        <p>{{ $w['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Ekip — partner girmediyse hiç basılmaz --}}
        @if(!empty($team))
            <div id="ekip" style="margin-top:64px;">
                <div class="sec-head c"><span class="kick c">Ekip</span><h2 style="font-size:clamp(24px,3.4vw,36px);">Danışman Kadromuz</h2></div>
                <div class="team-grid">
                    @foreach($team as $m)
                        <div class="tm">
                            @if(!empty($m['photo']))<img class="tm-ph" src="{{ $m['photo'] }}" alt="{{ $m['name'] }}">@else<div class="tm-ph">{{ Str::upper(Str::substr($m['name'], 0, 1)) }}</div>@endif
                            <h3>{{ $m['name'] }}</h3>@if(!empty($m['title']))<p>{{ $m['title'] }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Rozet kapalıysa MentorDE adı sayfada HİÇ geçmemeli (tam white-label) --}}
        @if($showBadge)
            <div class="trust" style="margin-top:64px;">
                <div class="trust-b">{!! $icon('shield') !!}<div><div class="tt">{{ config('brand.name', 'MentorDE') }} Yetkili Partneri</div><div class="ts">Resmi partner ağı üzerinden güvenli süreç</div></div></div>
                <p>{{ $siteName }}, {{ config('brand.name', 'MentorDE') }} altyapısı ve uzman ekibiyle süreçlerinizi uçtan uca yönetir.</p>
            </div>
        @endif
    </div>
</section>
