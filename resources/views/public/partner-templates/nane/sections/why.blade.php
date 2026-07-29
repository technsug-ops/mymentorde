{{-- ═══ NEDEN BİZ + EKİP + ROZET ═══ --}}
<div class="wrap sec">
    <div class="sec-head">
        <span class="lbl">Neden biz</span>
        <h2 class="h2">Sade yolun arkasındaki sistem</h2>
    </div>
    <div class="hair" style="--cols:{{ $cols(count($whyUs)) }};--min:200px;margin-bottom:{{ (!empty($team) || $showBadge) ? '40px' : '0' }};">
        @foreach($whyUs as $w)
            <div class="cell" style="padding:26px 22px;">
                <span class="cell-ic" style="width:42px;height:42px;border-radius:12px;background:var(--soft);align-items:center;justify-content:center;font-size:21px;">{!! $icon($w['icon']) !!}</span>
                <h3 style="font-size:15px;">{{ $w['title'] }}</h3>
                <p style="font-size:13px;margin:0;">{{ $w['desc'] }}</p>
            </div>
        @endforeach
    </div>

    @if(!empty($team) || $showBadge)
        <div class="grid" style="--gap:16px;--min:240px;--cols:{{ $cols(count($team) + ($showBadge ? 1 : 0)) }};">
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
                            <b style="font-size:15px;">{{ $m['name'] }}</b>
                            @if(($m['title'] ?? '') !== '')<span>{{ $m['title'] }}</span>@endif
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Rozet kapalıysa MentorDE adı sayfada HİÇ geçmez (tam white-label) --}}
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
