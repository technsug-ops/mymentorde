{{-- ═══ NEDEN BİZ + EKİP + ROZET ═══
     Kartlar sözleşmeden ($whyUs). Ekip ve MentorDE rozeti bu bölümün parçası
     (PartnerSiteSections: 'why' => 'Neden biz + ekip'). --}}
<section class="sec sec-top">
    <div class="wrap">
        <div class="sec-head">
            <span class="eyebrow acc">Neden Biz</span>
            <h2 class="serif">Doğru rehberle emin adımlar</h2>
        </div>

        <div class="why-rows">
            @foreach($whyUs as $w)
                <div class="why-row">
                    <span class="why-ic">{!! $icon($w['icon'] ?? 'default') !!}</span>
                    <div>
                        <h3>{{ $w['title'] }}</h3>
                        <p>{{ $w['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Ekip — partner girmediyse hiç basılmaz --}}
        @if(!empty($team))
            <div id="ekip" style="margin-top:60px;">
                <span class="eyebrow acc">Ekip</span>
                <h2 class="serif" style="font-size:clamp(24px,3vw,34px);margin:14px 0 34px;letter-spacing:-.5px;color:var(--ink);">Danışman kadromuz</h2>
                <div class="team-grid">
                    @foreach($team as $m)
                        <div class="tm">
                            @if(!empty($m['photo']))<img class="tm-ph" src="{{ $m['photo'] }}" alt="{{ $m['name'] }}">@else<div class="tm-ph">{{ Str::upper(Str::substr($m['name'], 0, 1)) }}</div>@endif
                            <h3>{{ $m['name'] }}</h3>
                            @if(!empty($m['title']))<p>{{ $m['title'] }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Rozet kapalıysa MentorDE adı sayfada HİÇ geçmemeli (tam white-label) --}}
        @if($showBadge)
            <div class="badge-line" style="margin-top:56px;">
                {!! $icon('shield') !!}
                <div>
                    <div class="bt">{{ config('brand.name', 'MentorDE') }} Yetkili Partneri</div>
                    <div class="bs">Resmi partner ağı üzerinden güvenli, şeffaf süreç.</div>
                </div>
            </div>
        @endif
    </div>
</section>
