{{-- ═══ NEDEN BİZ + EKİP + ROZET ═══ --}}
<div class="wrap sec">
    <div class="sec-head">
        <span class="pill-lbl">Neden biz</span>
        <h2 class="h2" style="margin-bottom:0;">Güvenin arkasındaki sistem</h2>
    </div>
    <div class="grid" style="--min:230px;--cols:{{ $cols(count($whyUs)) }};margin-bottom:{{ (!empty($team) || $showBadge) ? '18px' : '0' }};">
        @foreach($whyUs as $w)
            <div class="card" style="padding:26px 24px;">
                <span class="card-ic" style="width:48px;height:48px;border-radius:16px;font-size:24px;margin-bottom:14px;">{!! $icon($w['icon']) !!}</span>
                <h3 style="font-size:16.5px;">{{ $w['title'] }}</h3>
                <p style="font-size:13.5px;margin:0;">{{ $w['desc'] }}</p>
            </div>
        @endforeach
    </div>

    @if(!empty($team) || $showBadge)
        <div class="grid" style="--min:250px;--cols:{{ $cols(count($team) + ($showBadge ? 1 : 0)) }};">
            @foreach($team as $m)
                <div class="member">
                    <span class="avatar">
                        @if(($m['photo'] ?? '') !== '')
                            <img src="{{ $m['photo'] }}" alt="{{ $m['name'] }}">
                        @else
                            {{ mb_strtoupper(mb_substr($m['name'], 0, 1)) }}
                        @endif
                    </span>
                    <div class="who">
                        <div>
                            <b>{{ $m['name'] }}</b>
                            @if(($m['title'] ?? '') !== '')<span>{{ $m['title'] }}</span>@endif
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Rozet kapalıysa MentorDE adı sayfada HİÇ geçmez --}}
            @if($showBadge)
                <div class="trust">
                    <span class="trust-ic">{!! $icon('shield') !!}</span>
                    <div>
                        <b>{{ config('brand.name', 'MentorDE') }} Yetkili Partneri</b>
                        <p>Başvuru, vize ve yerleşim süreçleriniz resmi partner ağı ve dijital altyapı üzerinden yürütülür.</p>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
